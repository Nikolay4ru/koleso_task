require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const helmet = require('helmet');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const { v4: uuidv4 } = require('uuid');
const crypto = require('crypto');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const mongoose = require('mongoose');

// MongoDB Connection
const MONGODB_URI = process.env.MONGODB_URI || 'mongodb://localhost:27017/task-messenger';

mongoose.connect(MONGODB_URI)
.then(() => console.log('✅ MongoDB connected'))
.catch(err => {
  console.error('❌ MongoDB connection error:', err.message);
  console.log('⚠️  Server will continue without MongoDB (using in-memory storage)');
});

// MongoDB Models - ИСПРАВЛЕНО: Добавлено поле createdBy, убрана ссылка на chatId
const UserSchema = new mongoose.Schema({
  id: { type: String, required: true, unique: true },
  username: { type: String, required: true, unique: true },
  password: { type: String, required: true },
  name: { type: String, required: true },
  email: String,
  avatar: String,
  createdAt: { type: Date, default: Date.now }
});

const ChatSchema = new mongoose.Schema({
  id: { type: String, required: true, unique: true },
  type: { type: String, enum: ['private', 'group', 'task'], required: true },
  name: String,
  participants: [String],
  taskId: String,
  encrypted: { type: Boolean, default: false },
  unreadCount: mongoose.Schema.Types.Mixed,
  createdBy: { type: String, required: true }, // ИСПРАВЛЕНО: Добавлено обязательное поле
  createdAt: { type: Date, default: Date.now },
  updatedAt: { type: Date, default: Date.now }
});

const MessageSchema = new mongoose.Schema({
  id: { type: String, required: true, unique: true },
  chatId: { type: String, required: true, index: true },
  senderId: { type: String, required: true },
  text: { type: String, required: true },
  type: { type: String, default: 'text' },
  metadata: mongoose.Schema.Types.Mixed,
  encrypted: { type: Boolean, default: false },
  createdAt: { type: Date, default: Date.now }
});

const User = mongoose.model('User', UserSchema);
const Chat = mongoose.model('Chat', ChatSchema);
const Message = mongoose.model('Message', MessageSchema);

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  },
  maxHttpBufferSize: 1e8, // 100 MB for file transfers
  pingTimeout: 60000,
  pingInterval: 25000
});

// Security middleware
app.use(helmet({
  contentSecurityPolicy: false
}));
app.use(cors());
app.use(express.json({ limit: '1024mb' }));
app.use(express.urlencoded({ extended: true, limit: '1024mb' }));
app.use(express.static('public'));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 1024
});
app.use('/api/', limiter);

// JWT Secret
const JWT_SECRET = process.env.JWT_SECRET || 'your-super-secret-jwt-key-change-in-production';
const ENCRYPTION_KEY = process.env.ENCRYPTION_KEY || crypto.randomBytes(32).toString('hex');

// In-memory cache for performance (synced with MongoDB)
const users = new Map();
const chats = new Map();
const messages = new Map();
const conferences = new Map();
const onlineUsers = new Map();

// Initialize data from MongoDB on startup - ИСПРАВЛЕНО: Добавлено поле createdBy
async function initializeData() {
  try {
    // Load users from MongoDB
    const dbUsers = await User.find({});
    dbUsers.forEach(user => {
      users.set(user.username, {
        id: user.id,
        username: user.username,
        email: user.email,
        password: user.password,
        name: user.name,
        avatar: user.avatar,
        createdAt: user.createdAt
      });
    });
    console.log(`✅ Loaded ${dbUsers.length} users from MongoDB`);
    
    // Load chats from MongoDB
    const dbChats = await Chat.find({});
    dbChats.forEach(chat => {
      chats.set(chat.id, {
        id: chat.id,
        type: chat.type,
        name: chat.name,
        participants: chat.participants,
        taskId: chat.taskId,
        encrypted: chat.encrypted,
        unreadCount: chat.unreadCount,
        createdBy: chat.createdBy, // ИСПРАВЛЕНО: Добавлено поле
        createdAt: chat.createdAt,
        updatedAt: chat.updatedAt
      });
    });
    console.log(`✅ Loaded ${dbChats.length} chats from MongoDB`);
    
    // Load messages from MongoDB (last 10000)
    const dbMessages = await Message.find({})
      .sort({ createdAt: -1 })
      .limit(10000);
    
    dbMessages.forEach(msg => {
      if (!messages.has(msg.chatId)) {
        messages.set(msg.chatId, []);
      }
      messages.get(msg.chatId).unshift({
        id: msg.id,
        chatId: msg.chatId,
        senderId: msg.senderId,
        text: msg.text,
        type: msg.type,
        metadata: msg.metadata,
        encrypted: msg.encrypted,
        createdAt: msg.createdAt
      });
    });
    console.log(`✅ Loaded ${dbMessages.length} messages from MongoDB`);
    
  } catch (error) {
    console.error('❌ Error loading data from MongoDB:', error);
  }
}

// Initialize admin user
async function initializeAdminUser() {
  const adminPassword = bcrypt.hashSync('admin123', 10);
  const adminData = {
    id: 'user_admin',
    username: 'admin',
    email: 'admin@company.com',
    password: adminPassword,
    name: 'System Admin',
    avatar: null,
    createdAt: new Date()
  };
  
  // Add to cache
  users.set('admin', adminData);
  
  // Save to MongoDB
  try {
    const existingAdmin = await User.findOne({ username: 'admin' });
    if (!existingAdmin) {
      await User.create(adminData);
      console.log('✅ Admin user created in MongoDB');
    }
  } catch (error) {
    console.error('❌ Error creating admin user:', error);
  }
}

// Call initialization
initializeAdminUser();
initializeData();

// Encryption helpers
function encryptMessage(text, key = ENCRYPTION_KEY) {
  const iv = crypto.randomBytes(16);
  const cipher = crypto.createCipheriv('aes-256-cbc', Buffer.from(key.slice(0, 64), 'hex'), iv);
  let encrypted = cipher.update(text, 'utf8', 'hex');
  encrypted += cipher.final('hex');
  return iv.toString('hex') + ':' + encrypted;
}

function decryptMessage(encrypted, key = ENCRYPTION_KEY) {
  try {
    const parts = encrypted.split(':');
    const iv = Buffer.from(parts[0], 'hex');
    const encryptedText = parts[1];
    const decipher = crypto.createDecipheriv('aes-256-cbc', Buffer.from(key.slice(0, 64), 'hex'), iv);
    let decrypted = decipher.update(encryptedText, 'hex', 'utf8');
    decrypted += decipher.final('utf8');
    return decrypted;
  } catch (error) {
    return encrypted;
  }
}

// Authentication middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Access denied' });
  }

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Invalid token' });
    }
    req.user = user;
    next();
  });
};

// API Routes
app.post('/api/register', async (req, res) => {
  try {
    const { username, email, password, name } = req.body;

    if (!username || !email || !password) {
      return res.status(400).json({ error: 'All fields are required' });
    }

    if (users.has(username)) {
      return res.status(400).json({ error: 'Username already exists' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    const userId = `user_${uuidv4()}`;

    const userData = {
      id: userId,
      username,
      email,
      password: hashedPassword,
      name: name || username,
      avatar: null,
      createdAt: new Date()
    };

    // Save to cache
    users.set(username, userData);
    
    // Save to MongoDB
    try {
      await User.create(userData);
      console.log(`✅ User ${username} saved to MongoDB`);
    } catch (dbError) {
      console.error('❌ Error saving user to MongoDB:', dbError);
      // Continue even if MongoDB fails
    }

    const token = jwt.sign({ id: userId, username }, JWT_SECRET, { expiresIn: '7d' });

    res.json({
      token,
      user: {
        id: userId,
        username,
        email,
        name: userData.name,
        avatar: userData.avatar
      }
    });
  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({ error: 'Registration failed' });
  }
});

app.post('/api/login', async (req, res) => {
  try {
    const { username, password } = req.body;

    const user = users.get(username);
    if (!user) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const token = jwt.sign({ id: user.id, username }, JWT_SECRET, { expiresIn: '7d' });

    res.json({
      token,
      user: {
        id: user.id,
        username,
        email: user.email,
        name: user.name,
        avatar: user.avatar
      }
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Login failed' });
  }
});

app.get('/api/users', authenticateToken, (req, res) => {
  const userList = Array.from(users.values()).map(u => ({
    id: u.id,
    username: u.username,
    name: u.name,
    avatar: u.avatar,
    online: Array.from(onlineUsers.values()).includes(u.id)
  }));
  res.json(userList);
});

app.get('/api/chats', authenticateToken, (req, res) => {
  const userId = req.user.id;
  const userChats = Array.from(chats.values())
    .filter(chat => chat.participants.includes(userId))
    .map(chat => {
      const lastMessage = messages.has(chat.id) 
        ? messages.get(chat.id)[messages.get(chat.id).length - 1] 
        : null;
      
      // Create preview of last message with decrypted text
      let lastMessagePreview = null;
      if (lastMessage) {
        const messageText = lastMessage.encrypted 
          ? decryptMessage(lastMessage.text)
          : lastMessage.text;
        
        lastMessagePreview = {
          id: lastMessage.id,
          senderId: lastMessage.senderId,
          text: messageText,
          type: lastMessage.type,
          createdAt: lastMessage.createdAt
        };
      }
      
      return {
        ...chat,
        lastMessage: lastMessagePreview,
        unreadCount: chat.unreadCount?.[userId] || 0
      };
    });
  
  res.json(userChats);
});

// ИСПРАВЛЕНО: Улучшенное создание чатов с валидацией и обработкой ошибок
app.post('/api/chats', authenticateToken, async (req, res) => {
  try {
    const { type, participants, name } = req.body;
    const userId = req.user.id;

    if (!participants || participants.length === 0) {
      return res.status(400).json({ error: 'Participants required' });
    }

    const chatId = `chat_${uuidv4()}`;
    const allParticipants = [...new Set([userId, ...participants])];

    const chatData = {
      id: chatId,
      type: type || 'private',
      name: name || null,
      participants: allParticipants,
      createdBy: userId, // ИСПРАВЛЕНО: Обязательное поле
      createdAt: new Date(),
      updatedAt: new Date(),
      encrypted: true,
      unreadCount: {}
    };

    // Валидация данных
    if (!chatData.id || !chatData.type || !chatData.createdBy) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Save to cache
    chats.set(chatId, chatData);
    messages.set(chatId, []);
    
    // Save to MongoDB with error handling
    try {
      await Chat.create(chatData);
      console.log(`✅ Chat ${chatId} saved to MongoDB`);
    } catch (dbError) {
      console.error('❌ Error saving chat to MongoDB:', dbError);
      
      // Специальная обработка ошибки дублирования ключа chatId
      if (dbError.code === 11000 && dbError.message.includes('chatId')) {
        console.log('🔧 Detected chatId index issue. Please run: db.chats.dropIndex("chatId_1")');
        // Продолжаем работу - чат сохранен в кеше
      } else if (dbError.code === 11000) {
        console.error('❌ Duplicate key error:', dbError.keyValue);
        // Проверяем, не существует ли уже такой чат
        const existingChat = await Chat.findOne({ id: chatId });
        if (existingChat) {
          console.log('📋 Using existing chat from MongoDB');
        }
      }
    }

    // Notify participants
    allParticipants.forEach(participantId => {
      const socketId = Array.from(onlineUsers.entries())
        .find(([sid, uid]) => uid === participantId)?.[0];
      
      if (socketId) {
        io.to(socketId).emit('chat:created', chatData);
      }
    });

    res.json(chatData);
  } catch (error) {
    console.error('❌ Chat creation failed:', error);
    res.status(500).json({ error: 'Failed to create chat' });
  }
});

app.get('/api/messages/:chatId', authenticateToken, (req, res) => {
  const { chatId } = req.params;
  const userId = req.user.id;

  const chat = chats.get(chatId);
  if (!chat || !chat.participants.includes(userId)) {
    return res.status(403).json({ error: 'Access denied' });
  }

  const chatMessages = messages.get(chatId) || [];
  
  // Decrypt messages before sending to client
  const decryptedMessages = chatMessages.map(msg => ({
    ...msg,
    text: msg.encrypted ? decryptMessage(msg.text) : msg.text
  }));
  
  res.json(decryptedMessages);
});

// File upload configuration
const uploadDir = path.join(__dirname, 'public', 'uploads');
if (!fs.existsSync(uploadDir)) {
  fs.mkdirSync(uploadDir, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const uniqueName = `${uuidv4()}${path.extname(file.originalname)}`;
    cb(null, uniqueName);
  }
});

const upload = multer({
  storage,
  limits: {
    fileSize: 1024 * 1024 * 1024// 1024MB max
  },
  fileFilter: (req, file, cb) => {
    // Allow all file types for now
    cb(null, true);
  }
});

// Upload file endpoint
app.post('/api/upload', authenticateToken, upload.single('file'), (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' });
    }
    
    const fileUrl = `/uploads/${req.file.filename}`;
    
    res.json({
      fileUrl,
      fileName: req.file.originalname,
      fileSize: req.file.size,
      mimeType: req.file.mimetype
    });
  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ error: 'Upload failed' });
  }
});


// Upload avatar endpoint - ИСПРАВЛЕНО с broadcast
app.post('/api/upload-avatar', authenticateToken, upload.single('file'), async (req, res) => {
  try {
    console.log('📸 Avatar upload request from user:', req.user.id);
    console.log('File:', req.file);
    
    if (!req.file) {
      console.error('❌ No file in request');
      return res.status(400).json({ error: 'No file uploaded' });
    }
    
    const avatarUrl = `/uploads/${req.file.filename}`;
    const userId = req.user.id;
    
    console.log('Avatar URL:', avatarUrl);
    
    // Update user avatar in cache
    const userEntry = Array.from(users.entries()).find(([username, user]) => user.id === userId);
    
    if (userEntry) {
      const [username, userData] = userEntry;
      userData.avatar = avatarUrl;
      users.set(username, userData);
      
      console.log('✅ Avatar updated in cache for user:', username);
      
      // Update in MongoDB
      try {
        await User.findOneAndUpdate(
          { id: userId },
          { avatar: avatarUrl }
        );
        console.log(`✅ Avatar updated in MongoDB for user ${userId}`);
      } catch (dbError) {
        console.error('❌ Error updating avatar in MongoDB:', dbError);
        // Continue even if MongoDB fails
      }
      
      // НОВОЕ: Уведомляем всех пользователей об обновлении аватара
      io.emit('user:avatar-updated', {
        userId: userId,
        username: username,
        avatar: avatarUrl
      });
      console.log('📢 Broadcast avatar update to all users');
      
    } else {
      console.error('❌ User not found in cache:', userId);
      return res.status(404).json({ error: 'User not found' });
    }
    
    res.json({
      avatarUrl,
      message: 'Avatar uploaded successfully'
    });
    
  } catch (error) {
    console.error('❌ Avatar upload error:', error);
    res.status(500).json({ error: 'Avatar upload failed: ' + error.message });
  }
});

// Get current user profile
app.get('/api/profile', authenticateToken, (req, res) => {
  try {
    const userId = req.user.id;
    const userEntry = Array.from(users.entries()).find(([username, user]) => user.id === userId);
    
    if (!userEntry) {
      return res.status(404).json({ error: 'User not found' });
    }
    
    const [username, userData] = userEntry;
    
    res.json({
      id: userData.id,
      username: userData.username,
      email: userData.email,
      name: userData.name,
      avatar: userData.avatar
    });
    
  } catch (error) {
    console.error('Get profile error:', error);
    res.status(500).json({ error: 'Failed to get profile' });
  }
});

// Update user profile - ИСПРАВЛЕНО: правильный путь
app.put('/api/profile/update', authenticateToken, async (req, res) => {
  try {
    const userId = req.user.id;
    const { name, email } = req.body;
    
    console.log('📝 Profile update request:', { userId, name, email });
    
    const userEntry = Array.from(users.entries()).find(([username, user]) => user.id === userId);
    
    if (!userEntry) {
      return res.status(404).json({ error: 'User not found' });
    }
    
    const [username, userData] = userEntry;
    
    // Update user data
    if (name) userData.name = name;
    if (email) userData.email = email;
    
    users.set(username, userData);
    
    console.log('✅ Profile updated in cache');
    
    // Update in MongoDB
    try {
      await User.findOneAndUpdate(
        { id: userId },
        { 
          name: userData.name,
          email: userData.email
        }
      );
      console.log(`✅ Profile updated in MongoDB for user ${userId}`);
    } catch (dbError) {
      console.error('❌ Error updating profile in MongoDB:', dbError);
      // Continue even if MongoDB fails
    }
    
    res.json({
      user: {
        id: userData.id,
        username: userData.username,
        email: userData.email,
        name: userData.name,
        avatar: userData.avatar
      },
      message: 'Profile updated successfully'
    });
    
  } catch (error) {
    console.error('❌ Update profile error:', error);
    res.status(500).json({ error: 'Failed to update profile: ' + error.message });
  }
});

// Socket.IO connection handling
io.use((socket, next) => {
  const token = socket.handshake.auth.token;
  
  if (!token) {
    return next(new Error('Authentication error'));
  }

  jwt.verify(token, JWT_SECRET, (err, decoded) => {
    if (err) {
      return next(new Error('Authentication error'));
    }
    socket.userId = decoded.id;
    socket.username = decoded.username;
    next();
  });
});

io.on('connection', (socket) => {
  console.log(`User connected: ${socket.username} (${socket.userId})`);
  
  onlineUsers.set(socket.id, socket.userId);

  // Notify all users about online status
  io.emit('users:online', {
    userId: socket.userId,
    online: true
  });

  // Send online users list
  const onlineUserIds = Array.from(new Set(onlineUsers.values()));
  socket.emit('users:list', onlineUserIds);

  // Join user's chat rooms
  const userChats = Array.from(chats.values())
    .filter(chat => chat.participants.includes(socket.userId));
  
  userChats.forEach(chat => {
    socket.join(chat.id);
  });

  // Chat message
  socket.on('message:send', async (data) => {
    const { chatId, text, type, metadata } = data;
    const chat = chats.get(chatId);

    if (!chat || !chat.participants.includes(socket.userId)) {
      return socket.emit('error', { message: 'Access denied' });
    }

    const messageId = `msg_${uuidv4()}`;
    const encryptedText = chat.encrypted ? encryptMessage(text) : text;

    const messageData = {
      id: messageId,
      chatId,
      senderId: socket.userId,
      text: encryptedText,
      type: type || 'text',
      metadata: metadata || {},
      createdAt: new Date(),
      encrypted: chat.encrypted
    };

    // Save to cache
    if (!messages.has(chatId)) {
      messages.set(chatId, []);
    }
    messages.get(chatId).push(messageData);
    
    // Save to MongoDB
    try {
      await Message.create(messageData);
      console.log(`✅ Message ${messageId} saved to MongoDB`);
    } catch (dbError) {
      console.error('❌ Error saving message to MongoDB:', dbError);
    }

    // Update unread counts
    chat.participants.forEach(participantId => {
      if (participantId !== socket.userId) {
        chat.unreadCount = chat.unreadCount || {};
        chat.unreadCount[participantId] = (chat.unreadCount[participantId] || 0) + 1;
      }
    });
    
    // Update chat in MongoDB
    try {
      await Chat.findOneAndUpdate(
        { id: chatId },
        { 
          unreadCount: chat.unreadCount,
          updatedAt: new Date()
        }
      );
    } catch (dbError) {
      console.error('❌ Error updating chat in MongoDB:', dbError);
    }

    // Send to all participants
    io.to(chatId).emit('message:new', {
      ...messageData,
      text: text // Send original (decrypted) text to all
    });
  });

  // Typing indicator
  socket.on('typing:start', (chatId) => {
    socket.to(chatId).emit('typing:user', {
      chatId,
      userId: socket.userId,
      typing: true
    });
  });

  socket.on('typing:stop', (chatId) => {
    socket.to(chatId).emit('typing:user', {
      chatId,
      userId: socket.userId,
      typing: false
    });
  });

  // Mark messages as read
  socket.on('messages:read', (chatId) => {
    const chat = chats.get(chatId);
    if (chat && chat.unreadCount) {
      chat.unreadCount[socket.userId] = 0;
      socket.emit('chat:updated', chat);
    }
  });

  // WebRTC signaling for conference
socket.on('conference:join', (conferenceId) => {
  console.log('📞 User joining conference:', {
    userId: socket.userId,
    username: socket.username,
    conferenceId
  });
  
  socket.join(`conference:${conferenceId}`);
  
  if (!conferences.has(conferenceId)) {
    conferences.set(conferenceId, {
      id: conferenceId,
      participants: new Map(), // userId -> username
      startedAt: new Date()
    });
  }

  const conference = conferences.get(conferenceId);
  
  // Получаем список существующих участников ДО добавления нового
  const existingParticipants = Array.from(conference.participants.entries())
    .map(([userId, username]) => ({ userId, username }));
  
  console.log('📞 Existing participants:', existingParticipants);
  
  // Добавляем нового участника
  conference.participants.set(socket.userId, socket.username);

  // Отправляем новому участнику список существующих участников
  if (existingParticipants.length > 0) {
    socket.emit('conference:participants', existingParticipants);
    console.log('📤 Sent participant list to new joiner:', socket.username);
  }

  // Уведомляем всех СУЩЕСТВУЮЩИХ участников о новом участнике
  socket.to(`conference:${conferenceId}`).emit('conference:participant:joined', {
    userId: socket.userId,
    username: socket.username
  });
  
  console.log('📢 Notified existing participants about:', socket.username);
  console.log('📊 Total participants in conference:', conference.participants.size);
});

socket.on('conference:leave', (conferenceId) => {
  console.log('📞 User leaving conference:', {
    userId: socket.userId,
    username: socket.username,
    conferenceId
  });
  
  socket.leave(`conference:${conferenceId}`);
  
  const conference = conferences.get(conferenceId);
  if (conference) {
    conference.participants.delete(socket.userId);
    
    // Уведомляем всех участников
    socket.to(`conference:${conferenceId}`).emit('conference:participant:left', socket.userId);
    
    console.log('📊 Remaining participants:', conference.participants.size);

    if (conference.participants.size === 0) {
      conferences.delete(conferenceId);
      console.log('🏁 Conference ended:', conferenceId);
    }
  }
});

  // WebRTC signaling
socket.on('webrtc:offer', (data) => {
  console.log('📞 WebRTC offer:', {
    from: socket.username,
    fromId: socket.userId,
    to: data.to,
    conferenceId: data.conferenceId
  });
  
  // Находим socket ID получателя
  const recipientSocketId = Array.from(onlineUsers.entries())
    .find(([sid, uid]) => uid === data.to)?.[0];
  
  if (recipientSocketId) {
    io.to(recipientSocketId).emit('webrtc:offer', {
      from: socket.userId,
      offer: data.offer,
      conferenceId: data.conferenceId
    });
    console.log('✅ Offer sent to:', data.to);
  } else {
    console.log('⚠️ Recipient not online:', data.to);
  }
});

socket.on('webrtc:answer', (data) => {
  console.log('📞 WebRTC answer:', {
    from: socket.username,
    fromId: socket.userId,
    to: data.to,
    conferenceId: data.conferenceId
  });
  
  // Находим socket ID получателя
  const recipientSocketId = Array.from(onlineUsers.entries())
    .find(([sid, uid]) => uid === data.to)?.[0];
  
  if (recipientSocketId) {
    io.to(recipientSocketId).emit('webrtc:answer', {
      from: socket.userId,
      answer: data.answer,
      conferenceId: data.conferenceId
    });
    console.log('✅ Answer sent to:', data.to);
  } else {
    console.log('⚠️ Recipient not online:', data.to);
  }
});

socket.on('webrtc:ice-candidate', (data) => {
  console.log('🧊 ICE candidate:', {
    from: socket.username,
    fromId: socket.userId,
    to: data.to,
    conferenceId: data.conferenceId
  });
  
  // Находим socket ID получателя
  const recipientSocketId = Array.from(onlineUsers.entries())
    .find(([sid, uid]) => uid === data.to)?.[0];
  
  if (recipientSocketId) {
    io.to(recipientSocketId).emit('webrtc:ice-candidate', {
      from: socket.userId,
      candidate: data.candidate,
      conferenceId: data.conferenceId
    });
    console.log('✅ ICE candidate sent to:', data.to);
  } else {
    console.log('⚠️ Recipient not online:', data.to);
  }
});

  // Disconnect
  socket.on('disconnect', () => {
    console.log(`User disconnected: ${socket.username} (${socket.userId})`);
    
    onlineUsers.delete(socket.id);

    // Notify all users about offline status
    io.emit('users:online', {
      userId: socket.userId,
      online: false
    });

    // Leave all conferences
    conferences.forEach((conference, conferenceId) => {
      if (conference.participants.has(socket.userId)) {
        conference.participants.delete(socket.userId);
        socket.to(`conference:${conferenceId}`).emit('conference:user-left', {
          userId: socket.userId,
          conferenceId
        });

        if (conference.participants.size === 0) {
          conferences.delete(conferenceId);
        }
      }
    });
  });
});

// Start server
const PORT = process.env.PORT || 3010;
server.listen(PORT, () => {
  console.log(`🚀 Server running on port ${PORT}`);
  console.log(`📱 Access the app at http://localhost:${PORT}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully');
  server.close(() => {
    mongoose.connection.close(false, () => {
      console.log('MongoDB connection closed');
      process.exit(0);
    });
  });
});

module.exports = { app, server, io };