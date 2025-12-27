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
    name: { type: String, required: true },
    email: { type: String, required: true, unique: true },
    username: { type: String, required: true, unique: true },
    password: { type: String, required: true },
    avatar: { type: String, default: null },
    isAdmin: { type: Boolean, default: false },
    isActive: { type: Boolean, default: true },
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
  createdAt: { type: Date, default: Date.now },

  delivered: { type: Boolean, default: false },
    deliveredAt: { type: Date },
    read: { type: Boolean, default: false },
    readAt: { type: Date }
});

const User = mongoose.model('User', UserSchema);
const Chat = mongoose.model('Chat', ChatSchema);
const Message = mongoose.model('Message', MessageSchema);

let Task, Department;
let useMongoose = true;
if (useMongoose) {
    try {
        Task = require('./models/Task');
        Department = require('./models/Department');
        console.log('✅ Task and Department models loaded');
    } catch (error) {
        console.warn('⚠️ Task/Department models not found, using in-memory storage');
    }
}

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
const chatsMap = new Map();
const messages = new Map();
const conferences = new Map();
const userSockets = new Map(); 
const onlineUsers = new Map();
const tasksMap = new Map();

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
        isAdmin: user.isAdmin || false,
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
// ==================== CREATE DEFAULT ADMIN ====================

async function createDefaultAdmin() {
    if (useMongoose && User) {
        try {
            const adminExists = await User.findOne({ username: 'admin' });
            
            if (!adminExists) {
                const hashedPassword = await bcrypt.hash('admin123', 10);
                const adminUser = new User({
                    id: generateId(),
                    name: 'Администратор',
                    email: 'admin@example.com',
                    username: 'admin',
                    password: hashedPassword,
                    isAdmin: true,
                    isActive: true,
                    createdAt: new Date()
                });
                
                await adminUser.save();
                console.log('✅ Default admin created (username: admin, password: admin123)');
                console.log('⚠️  ВАЖНО: Измените пароль администратора после первого входа!');
            }
        } catch (error) {
            console.error('Error creating default admin:', error);
        }
    } else {
        // In-memory fallback
        const adminExists = Array.from(users.values()).find(u => u.username === 'admin');
        
        if (!adminExists) {
            const hashedPassword = await bcrypt.hash('admin123', 10);
            const adminUser = {
                id: generateId(),
                name: 'Администратор',
                email: 'admin@example.com',
                username: 'admin',
                password: hashedPassword,
                isAdmin: true,
                isActive: true,
                avatar: null,
                createdAt: new Date()
            };
            
            users.set('admin', adminUser);
            console.log('✅ Default admin created (username: admin, password: admin123)');
            console.log('⚠️  ВАЖНО: Измените пароль администратора после первого входа!');
        }
    }
}

// Вызовите эту функцию при старте сервера
createDefaultAdmin();

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


function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}


// Authentication middleware
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
        return res.status(401).json({ error: 'Access token required' });
    }

    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        
        console.log('🔐 Token decoded:', decoded);
        
        // ВАЖНО: Проверяем что decoded содержит id
        if (!decoded.id) {
            console.error('❌ Token missing id field:', decoded);
            return res.status(403).json({ error: 'Invalid token structure' });
        }
        
        req.user = decoded;
        console.log('✅ User authenticated:', req.user.id);
        next();
    } catch (error) {
        console.error('❌ Token decode error:', error);
        return res.status(403).json({ error: 'Invalid token' });
    }
}


function isAdmin(req, res, next) {
    if (useMongoose && User) {
        User.findOne({ id: req.user.id }).then(user => {
            if (!user || !user.isAdmin) {
                return res.status(403).json({ error: 'Admin access required' });
            }
            req.user.isAdmin = user.isAdmin;
            next();
        }).catch(error => {
            console.error('Admin check error:', error);
            res.status(500).json({ error: 'Server error' });
        });
    } else {
        // In-memory fallback
        const user = Array.from(users.values()).find(u => u.id === req.user.id);
        if (!user || !user.isAdmin) {
            return res.status(403).json({ error: 'Admin access required' });
        }
        next();
    }
}

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
      isAdmin: null,
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

        if (!username || !password) {
            return res.status(400).json({ error: 'Username and password are required' });
        }

        if (useMongoose && User) {
            const user = await User.findOne({ username });

            if (!user) {
                return res.status(401).json({ error: 'Invalid credentials' });
            }
            
            if (user.isActive === false) {
                return res.status(403).json({ error: 'Account is disabled' });
            }

            const validPassword = await bcrypt.compare(password, user.password);

            if (!validPassword) {
                return res.status(401).json({ error: 'Invalid credentials' });
            }

            // ВАЖНО: Создаем токен с правильной структурой
            const tokenPayload = {
                id: user.id,           // Используем user.id, не _id
                username: user.username
            };
            
            console.log('Creating token with payload:', tokenPayload);
            
            const token = jwt.sign(tokenPayload, JWT_SECRET, { expiresIn: '7d' });

            res.json({
                token,
                user: {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    username: user.username,
                    avatar: user.avatar,
                    isAdmin: user.isAdmin || false
                }
            });
        } else {
            // In-memory fallback
            const user = users.get(username);

            if (!user) {
                return res.status(401).json({ error: 'Invalid credentials' });
            }
            
            if (user.isActive === false) {
                return res.status(403).json({ error: 'Account is disabled' });
            }

            const validPassword = await bcrypt.compare(password, user.password);

            if (!validPassword) {
                return res.status(401).json({ error: 'Invalid credentials' });
            }

            // ВАЖНО: Создаем токен с правильной структурой
            const tokenPayload = {
                id: user.id,
                username: user.username
            };
            
            console.log('Creating token with payload:', tokenPayload);
            
            const token = jwt.sign(tokenPayload, JWT_SECRET, { expiresIn: '7d' });

            res.json({
                token,
                user: {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    username: user.username,
                    avatar: user.avatar,
                    isAdmin: user.isAdmin || false
                }
            });
        }
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
    isAdmin: u.isAdmin || false,
    online: Array.from(onlineUsers.values()).includes(u.id)
  }));
  res.json(userList);
});

// Get specific user by ID
app.get('/api/users/:userId', authenticateToken, async (req, res) => {
    try {
        const { userId } = req.params;
        
        console.log('Getting user by ID:', userId);
        
        let user;
        
        if (useMongoose && User) {
            user = await User.findOne({ id: userId }).select('-password');
        }
        
        if (!user) {
            user = Array.from(users.values()).find(u => u.id === userId);
        }
        
        if (!user) {
            return res.status(404).json({ error: 'User not found' });
        }
        
        res.json(user);
        
    } catch (error) {
        console.error('Get user error:', error);
        res.status(500).json({ error: 'Failed to get user' });
    }
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

app.get('/api/messages/:chatId', authenticateToken, async (req, res) => {
    try {
        const { chatId } = req.params;
        const userId = req.user.id;
        
        console.log('📨 Getting messages for chat:', chatId, 'User:', userId);
        
        // Сначала проверяем что чат существует и пользователь имеет к нему доступ
        let chat;
        
        if (useMongoose && Chat) {
            try {
                chat = await Chat.findOne({ id: chatId });
                console.log('Chat from MongoDB:', chat ? 'found' : 'not found');
            } catch (err) {
                console.warn('⚠️ Could not find chat in MongoDB:', err.message);
            }
        }
        
        // Если не нашли в MongoDB, ищем в памяти
        if (!chat) {
            chat = chatsMap.get(chatId) || Array.from(chats.values()).find(c => c.id === chatId);
            if (chat) {
                console.log('Chat from memory:', chat.id);
            }
        }
        
        if (!chat) {
            console.error('❌ Chat not found:', chatId);
            return res.status(404).json({ error: 'Chat not found' });
        }
        
        // Проверяем что пользователь является участником чата
        if (!chat.participants || !chat.participants.includes(userId)) {
            console.error('❌ User not a participant:', userId, 'Chat participants:', chat.participants);
            return res.status(403).json({ error: 'Access denied to this chat' });
        }
        
        console.log('✅ User has access to chat');
        
        let chatMessages = [];
        
        // Получаем сообщения
        if (useMongoose && Message) {
            try {
                chatMessages = await Message.find({ chatId })
                    .sort({ createdAt: 1 })
                    .lean();
                
                console.log('✅ Found', chatMessages.length, 'messages in MongoDB');
            } catch (err) {
                console.warn('⚠️ Could not get messages from MongoDB:', err.message);
            }
        }
        
        // In-memory fallback если не нашли в MongoDB
        if (chatMessages.length === 0) {
            const allMessages = Array.from(messages.values()).flat();
            chatMessages = allMessages
                .filter(msg => msg.chatId === chatId)
                .sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
            
            console.log('✅ Found', chatMessages.length, 'messages in memory');
        }
        
        // ВАЖНО: Расшифровываем зашифрованные сообщения перед отправкой
        const decryptedMessages = chatMessages.map(msg => {
            if (msg.encrypted && msg.text) {
                try {
                    return {
                        ...msg,
                        text: decryptMessage(msg.text), // Расшифровываем
                        encrypted: false // Помечаем что уже расшифровано для клиента
                    };
                } catch (decryptError) {
                    console.error('Error decrypting message:', msg.id, decryptError);
                    return {
                        ...msg,
                        text: '[Ошибка расшифровки]'
                    };
                }
            }
            return msg;
        });
        
        console.log('📤 Returning', decryptedMessages.length, 'messages (decrypted)');
        res.json(decryptedMessages);
        
    } catch (error) {
        console.error('❌ Get messages error:', error);
        console.error('Error stack:', error.stack);
        res.status(500).json({ error: 'Failed to get messages' });
    }
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
      avatar: userData.avatar,
      isAdmin: userData.isAdmin || false
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






// ==================== TASKS API ====================

// Get all tasks
app.get('/api/tasks', authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        
        if (useMongoose && Task) {
            const tasks = await Task.find({
                $or: [
                    { creatorId: userId },
                    { assigneeId: userId },
                    { watchers: userId }
                ]
            }).sort({ createdAt: -1 });
            
            res.json(tasks);
        } else {
            // In-memory fallback
            const allTasks = Array.from(tasksMap.values());
            const userTasks = allTasks.filter(task => 
                task.creatorId === userId || 
                task.assigneeId === userId ||
                (task.watchers && task.watchers.includes(userId))
            );
            res.json(userTasks);
        }
    } catch (error) {
        console.error('Get tasks error:', error);
        res.status(500).json({ error: 'Failed to get tasks' });
    }
});

// Create task
// Create task
app.post('/api/tasks', authenticateToken, async (req, res) => {
    console.log('=== CREATE TASK REQUEST ===');
    console.log('User:', req.user);
    console.log('Body:', req.body);
    
    try {
        const { title, description, departmentId, assigneeId, priority, dueDate, watchers } = req.body;
        const userId = req.user.id;
        
        console.log('Parsed data:', { title, departmentId, assigneeId, userId, watchers });
        
        if (!title || !departmentId) {
            console.log('❌ Validation failed: missing title or department');
            return res.status(400).json({ error: 'Title and department are required' });
        }
        
        const taskId = generateId();
        console.log('Generated task ID:', taskId);
        
        const taskData = {
            id: taskId,
            title,
            description: description || '',
            departmentId,
            assigneeId: assigneeId || null,
            creatorId: userId,
            priority: priority || 'normal',
            status: 'todo',
            dueDate: dueDate || null,
            watchers: watchers || [],
            watchersCount: watchers ? watchers.length : 0,
            commentsCount: 0,
            hasUnread: false,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };
        
        console.log('Task data prepared:', taskData);
        
        let savedTask;
        let taskChat;
        
        // Всегда используем in-memory как запасной вариант
        console.log('Saving to in-memory storage...');
        tasksMap.set(taskData.id, taskData);
        savedTask = taskData;
        console.log('✅ Task saved to memory');
        
        // Создаем чат для задачи
        const chatId = generateId();
        console.log('Generated chat ID:', chatId);
        
        const taskChatData = {
    id: chatId,
    type: 'task',
    taskId: savedTask.id,
    name: `Задача: ${title}`,
    participants: Array.from(new Set([
        userId,                    // Создатель задачи
        assigneeId,                // Исполнитель
        ...(watchers || [])        // Наблюдатели
    ].filter(Boolean))),           // Убираем null/undefined и дубликаты
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
};
        
        console.log('Chat data prepared:', taskChatData);
        
        chatsMap.set(taskChatData.id, taskChatData);
        taskChat = taskChatData;
        console.log('✅ Task chat saved to memory');
        
        // Пробуем сохранить в MongoDB если доступен
        if (useMongoose && Task) {
            try {
                console.log('Attempting to save to MongoDB...');
                const mongoTask = new Task(taskData);
                await mongoTask.save();
                console.log('✅ Task saved to MongoDB');
                
                if (Chat) {
                    const mongoChat = new Chat(taskChatData);
                    await mongoChat.save();
                    console.log('✅ Chat saved to MongoDB');
                }
            } catch (dbError) {
                console.warn('⚠️ MongoDB save failed (using in-memory):', dbError.message);
                // Уже сохранено в памяти, продолжаем
            }
        } else {
            console.log('MongoDB not available, using in-memory only');
        }
        
        // Broadcast task creation
        console.log('Broadcasting task:created event');
        io.emit('task:created', savedTask);
        
        // Broadcast chat creation to participants
        if (taskChat && taskChat.participants) {
            console.log('Broadcasting chat to participants:', taskChat.participants);
            taskChat.participants.forEach(participantId => {
                const socketId = userSockets.get(participantId);
                if (socketId) {
                    io.to(socketId).emit('chat:created', taskChat);
                }
            });
        }
        
        // Возвращаем задачу с информацией о чате
        const response = {
            ...savedTask,
            chatId: taskChat ? taskChat.id : null
        };
        
        console.log('✅ Sending response:', response);
        res.status(201).json(response);
        
    } catch (error) {
        console.error('❌❌❌ CREATE TASK ERROR ❌❌❌');
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        console.error('Error details:', error);
        
        res.status(500).json({ 
            error: 'Failed to create task',
            message: error.message,
            stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
        });
    }
});

// Update task
app.put('/api/tasks/:taskId', authenticateToken, async (req, res) => {
    try {
        const { taskId } = req.params;
        const { title, description, departmentId, assigneeId, priority, dueDate, watchers } = req.body;
        const userId = req.user.id;
        
        if (useMongoose && Task) {
            const task = await Task.findOne({ id: taskId });
            
            if (!task) {
                return res.status(404).json({ error: 'Task not found' });
            }
            
            // Check permissions
            if (task.creatorId !== userId) {
                return res.status(403).json({ error: 'Not authorized' });
            }
            
            task.title = title;
            task.description = description || '';
            task.departmentId = departmentId;
            task.assigneeId = assigneeId || null;
            task.priority = priority || 'normal';
            task.dueDate = dueDate || null;
            task.watchers = watchers || [];
            task.updatedAt = new Date().toISOString();
            
            await task.save();
            
            // Update task chat participants
            const taskChat = await Chat.findOne({ taskId: task.id });
            if (taskChat) {
                taskChat.participants = [task.creatorId, task.assigneeId, ...task.watchers].filter(Boolean);
                await taskChat.save();
            }
            
            io.emit('task:updated', task);
            
            res.json(task);
        } else {
            // In-memory fallback
            const task = tasksMap.get(taskId);
            
            if (!task) {
                return res.status(404).json({ error: 'Task not found' });
            }
            
            if (task.creatorId !== userId) {
                return res.status(403).json({ error: 'Not authorized' });
            }
            
            task.title = title;
            task.description = description || '';
            task.departmentId = departmentId;
            task.assigneeId = assigneeId || null;
            task.priority = priority || 'normal';
            task.dueDate = dueDate || null;
            task.watchers = watchers || [];
            task.updatedAt = new Date().toISOString();
            
            tasksMap.set(taskId, task);
            
            io.emit('task:updated', task);
            
            res.json(task);
        }
    } catch (error) {
        console.error('Update task error:', error);
        res.status(500).json({ error: 'Failed to update task' });
    }
});

// Update task status
app.patch('/api/tasks/:taskId/status', authenticateToken, async (req, res) => {
    try {
        const { taskId } = req.params;
        const { status } = req.body;
        const userId = req.user.id;
        
        if (!['todo', 'in_progress', 'review', 'done'].includes(status)) {
            return res.status(400).json({ error: 'Invalid status' });
        }
        
        if (useMongoose && Task) {
            const task = await Task.findOne({ id: taskId });
            
            if (!task) {
                return res.status(404).json({ error: 'Task not found' });
            }
            
            task.status = status;
            task.updatedAt = new Date().toISOString();
            
            if (status === 'done') {
                task.completedAt = new Date().toISOString();
            }
            
            await task.save();
            
            // Send notification to task chat
            const taskChat = await Chat.findOne({ taskId: task.id });
            if (taskChat) {
                const statusNames = {
                    'todo': 'К выполнению',
                    'in_progress': 'В работе',
                    'review': 'На проверке',
                    'done': 'Выполнено'
                };
                
                const systemMessage = {
                    id: generateId(),
                    chatId: taskChat.id,
                    senderId: userId,
                    type: 'system',
                    text: `Статус задачи изменен на: ${statusNames[status]}`,
                    createdAt: new Date().toISOString()
                };
                
                const message = new Message(systemMessage);
                await message.save();
                
                io.to(taskChat.id).emit('message:new', message);
            }
            
            io.emit('task:updated', task);
            
            res.json(task);
        } else {
            // In-memory fallback
            const task = tasksMap.get(taskId);
            
            if (!task) {
                return res.status(404).json({ error: 'Task not found' });
            }
            
            task.status = status;
            task.updatedAt = new Date().toISOString();
            
            if (status === 'done') {
                task.completedAt = new Date().toISOString();
            }
            
            tasksMap.set(taskId, task);
            
            io.emit('task:updated', task);
            
            res.json(task);
        }
    } catch (error) {
        console.error('Update task status error:', error);
        res.status(500).json({ error: 'Failed to update task status' });
    }
});

// Delete task
app.delete('/api/tasks/:taskId', authenticateToken, async (req, res) => {
    try {
        const { taskId } = req.params;
        const userId = req.user.id;
        
        if (useMongoose && Task) {
            const task = await Task.findOne({ id: taskId });
            
            if (!task) {
                return res.status(404).json({ error: 'Task not found' });
            }
            
            if (task.creatorId !== userId) {
                return res.status(403).json({ error: 'Not authorized' });
            }
            
            await Task.deleteOne({ id: taskId });
            
            // Delete task chat
            await Chat.deleteOne({ taskId: task.id });
            
            io.emit('task:deleted', taskId);
            
            res.json({ message: 'Task deleted' });
        } else {
            // In-memory fallback
            const task = tasksMap.get(taskId);
            
            if (!task) {
                return res.status(404).json({ error: 'Task not found' });
            }
            
            if (task.creatorId !== userId) {
                return res.status(403).json({ error: 'Not authorized' });
            }
            
            tasksMap.delete(taskId);
            
            io.emit('task:deleted', taskId);
            
            res.json({ message: 'Task deleted' });
        }
    } catch (error) {
        console.error('Delete task error:', error);
        res.status(500).json({ error: 'Failed to delete task' });
    }
});

// ==================== DEPARTMENTS API ====================

// Get all departments
app.get('/api/departments', authenticateToken, async (req, res) => {
    try {
        if (useMongoose && Department) {
            const departments = await Department.find().sort({ name: 1 });
            res.json(departments);
        } else {
            // In-memory fallback with default departments
            const departments = [
                { id: 'dept-1', name: 'Разработка', createdAt: new Date().toISOString() },
                { id: 'dept-2', name: 'Дизайн', createdAt: new Date().toISOString() },
                { id: 'dept-3', name: 'Маркетинг', createdAt: new Date().toISOString() },
                { id: 'dept-4', name: 'Продажи', createdAt: new Date().toISOString() },
                { id: 'dept-5', name: 'Поддержка', createdAt: new Date().toISOString() }
            ];
            res.json(departments);
        }
    } catch (error) {
        console.error('Get departments error:', error);
        res.status(500).json({ error: 'Failed to get departments' });
    }
});

// Create department
app.post('/api/departments', authenticateToken, async (req, res) => {
    try {
        const { name } = req.body;
        
        if (!name) {
            return res.status(400).json({ error: 'Name is required' });
        }
        
        const departmentData = {
            id: generateId(),
            name,
            createdAt: new Date().toISOString()
        };
        
        if (useMongoose && Department) {
            const department = new Department(departmentData);
            await department.save();
            res.status(201).json(department);
        } else {
            res.status(201).json(departmentData);
        }
    } catch (error) {
        console.error('Create department error:', error);
        res.status(500).json({ error: 'Failed to create department' });
    }
});

// ==================== TASK CHATS API ====================


// Create task chat
app.post('/api/task-chats', authenticateToken, async (req, res) => {
    try {
        const { taskId } = req.body;
        const userId = req.user.id;
        
        console.log('📞 Creating task chat for task:', taskId, 'User:', userId);
        
        if (!taskId) {
            return res.status(400).json({ error: 'Task ID is required' });
        }
        
        // Сначала проверяем существует ли чат
        if (useMongoose && Chat) {
            try {
                let taskChat = await Chat.findOne({ taskId });
                
                if (taskChat) {
                    console.log('✅ Task chat already exists in MongoDB:', taskChat.id);
                    
                    // Проверяем что текущий пользователь в участниках
                    if (!taskChat.participants.includes(userId)) {
                        taskChat.participants.push(userId);
                        await taskChat.save();
                        console.log('✅ Added user to chat participants');
                    }
                    
                    return res.json(taskChat);
                }
            } catch (err) {
                console.warn('⚠️ Could not check MongoDB for existing chat:', err.message);
            }
        }
        
        // Проверяем в памяти
        let taskChat = Array.from(chatsMap.values()).find(c => c.taskId === taskId);
        if (taskChat) {
            console.log('✅ Task chat already exists in memory:', taskChat.id);
            
            // Проверяем что текущий пользователь в участниках
            if (!taskChat.participants.includes(userId)) {
                taskChat.participants.push(userId);
                console.log('✅ Added user to chat participants');
            }
            
            return res.json(taskChat);
        }
        
        // Ищем задачу
        let task;
        
        if (useMongoose && Task) {
            try {
                task = await Task.findOne({ id: taskId });
                if (task) {
                    console.log('✅ Task found in MongoDB:', task.id);
                }
            } catch (err) {
                console.warn('⚠️ Could not find task in MongoDB:', err.message);
            }
        }
        
        // Если не нашли в MongoDB, ищем в памяти
        if (!task) {
            task = tasksMap.get(taskId);
            if (task) {
                console.log('✅ Task found in memory:', task.id);
            }
        }
        
        if (!task) {
            console.error('❌ Task not found anywhere:', taskId);
            console.log('Available tasks in memory:', Array.from(tasksMap.keys()));
            return res.status(404).json({ error: 'Task not found' });
        }
        
        // Создаем чат с правильными участниками
        const participants = Array.from(new Set([
            userId,                         // Текущий пользователь
            task.creatorId,                 // Создатель задачи
            task.assigneeId,                // Исполнитель
            ...(task.watchers || [])        // Наблюдатели
        ].filter(Boolean)));                // Убираем null/undefined и дубликаты
        
        console.log('Creating chat with participants:', participants);
        
        const chatData = {
            id: generateId(),
            type: 'task',
            taskId: task.id,
            name: `Задача: ${task.title}`,
            participants: participants,
            createdBy: userId,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };
        
        if (useMongoose && Chat) {
            try {
                const chat = new Chat(chatData);
                await chat.save();
                taskChat = chat.toObject();
                console.log('✅ Task chat created in MongoDB:', taskChat.id);
            } catch (err) {
                console.warn('⚠️ Could not save chat to MongoDB, using memory:', err.message);
                chatsMap.set(chatData.id, chatData);
                taskChat = chatData;
            }
        } else {
            chatsMap.set(chatData.id, chatData);
            taskChat = chatData;
            console.log('✅ Task chat created in memory:', taskChat.id);
        }
        
        // Emit to all participants
        participants.forEach(participantId => {
            const socketId = userSockets.get(participantId);
            if (socketId) {
                io.to(socketId).emit('chat:created', taskChat);
            }
        });
        
        res.status(201).json(taskChat);
        
    } catch (error) {
        console.error('❌ Create task chat error:', error);
        console.error('Error stack:', error.stack);
        res.status(500).json({ 
            error: 'Failed to create task chat',
            message: error.message
        });
    }
});



// Get chat by ID
app.get('/api/chats/:chatId', authenticateToken, async (req, res) => {
    try {
        const { chatId } = req.params;
        
        if (useMongoose && Chat) {
            const chat = await Chat.findOne({ id: chatId });
            if (chat) {
                return res.json(chat);
            }
        }
        
        // In-memory fallback
        const chat = chatsMap.get(chatId) || Array.from(chats.values()).find(c => c.id === chatId);
        
        if (!chat) {
            return res.status(404).json({ error: 'Chat not found' });
        }
        
        res.json(chat);
    } catch (error) {
        console.error('Get chat error:', error);
        res.status(500).json({ error: 'Failed to get chat' });
    }
});




// ==================== ADMIN ROUTES ====================

// Get admin statistics
app.get('/api/admin/stats', authenticateToken, isAdmin, async (req, res) => {
    try {
        let stats = {
            totalUsers: 0,
            newUsersThisMonth: 0,
            totalTasks: 0,
            activeTasks: 0,
            totalDepartments: 0,
            totalMessages: 0,
            messagesToday: 0,
            recentActivity: [],
            departmentStats: []
        };
        
        if (useMongoose && User && Task && Department && Message) {
            const now = new Date();
            const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
            const dayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            
            // Basic stats
            stats.totalUsers = await User.countDocuments();
            stats.newUsersThisMonth = await User.countDocuments({ 
                createdAt: { $gte: monthStart } 
            });
            stats.totalTasks = await Task.countDocuments();
            stats.activeTasks = await Task.countDocuments({ 
                status: { $in: ['todo', 'in_progress', 'review'] } 
            });
            stats.totalDepartments = await Department.countDocuments();
            stats.totalMessages = await Message.countDocuments();
            stats.messagesToday = await Message.countDocuments({ 
                createdAt: { $gte: dayStart } 
            });
            
            // Department stats
            const departments = await Department.find();
            stats.departmentStats = await Promise.all(departments.map(async dept => {
                const tasksCount = await Task.countDocuments({ departmentId: dept.id });
                const completedTasks = await Task.countDocuments({ 
                    departmentId: dept.id, 
                    status: 'done' 
                });
                
                // Count employees in department (tasks assigned to users in department)
                const departmentTasks = await Task.find({ departmentId: dept.id });
                const employeeIds = new Set(departmentTasks.map(t => t.assigneeId).filter(Boolean));
                
                return {
                    id: dept.id,
                    name: dept.name,
                    employeeCount: employeeIds.size,
                    tasksCount,
                    completedTasks
                };
            }));
            
            // Recent activity (last 10 messages)
            const recentMessages = await Message.find()
                .sort({ createdAt: -1 })
                .limit(10)
                .lean();
            
            stats.recentActivity = recentMessages.map(msg => ({
                userId: msg.senderId,
                action: 'Отправил сообщение',
                timestamp: msg.createdAt
            }));
            
        } else {
            // In-memory fallback
            stats.totalUsers = users.size;
            stats.totalTasks = tasksMap.size;
            stats.activeTasks = Array.from(tasksMap.values()).filter(t => 
                ['todo', 'in_progress', 'review'].includes(t.status)
            ).length;
            stats.totalDepartments = 5; // Default departments
        }
        
        res.json(stats);
    } catch (error) {
        console.error('Get admin stats error:', error);
        res.status(500).json({ error: 'Failed to get statistics' });
    }
});

// Get all users (admin)
app.get('/api/admin/users', authenticateToken, isAdmin, async (req, res) => {
    try {
        if (useMongoose && User) {
            const allUsers = await User.find({}, '-password').sort({ createdAt: -1 }).lean();
            res.json(allUsers);
        } else {
            const allUsers = Array.from(users.values()).map(({ password, ...user }) => user);
            res.json(allUsers);
        }
    } catch (error) {
        console.error('Get admin users error:', error);
        res.status(500).json({ error: 'Failed to get users' });
    }
});

// Create user (admin)
app.post('/api/admin/users', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { name, email, username, password, isAdmin: isUserAdmin } = req.body;
        
        if (!name || !email || !username || !password) {
            return res.status(400).json({ error: 'All fields are required' });
        }
        
        const hashedPassword = await bcrypt.hash(password, 10);
        
        if (useMongoose && User) {
            const existingUser = await User.findOne({ 
                $or: [{ username }, { email }] 
            });
            
            if (existingUser) {
                return res.status(400).json({ error: 'User already exists' });
            }
            
            const userId = generateId();
            const user = new User({
                id: userId,
                name,
                email,
                username,
                password: hashedPassword,
                isAdmin: isUserAdmin || false,
                isActive: true,
                createdAt: new Date()
            });
            
            await user.save();
            
            const { password: _, ...userWithoutPassword } = user.toObject();
            res.status(201).json(userWithoutPassword);
        } else {
            // In-memory fallback
            const existingUser = Array.from(users.values()).find(
                u => u.username === username || u.email === email
            );
            
            if (existingUser) {
                return res.status(400).json({ error: 'User already exists' });
            }
            
            const userId = generateId();
            const user = {
                id: userId,
                name,
                email,
                username,
                password: hashedPassword,
                isAdmin: isUserAdmin || false,
                isActive: true,
                createdAt: new Date()
            };
            
            users.set(username, user);
            
            const { password: _, ...userWithoutPassword } = user;
            res.status(201).json(userWithoutPassword);
        }
    } catch (error) {
        console.error('Create user error:', error);
        res.status(500).json({ error: 'Failed to create user' });
    }
});

// Update user (admin)
app.put('/api/admin/users/:userId', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { userId } = req.params;
        const { name, email, username, password, isAdmin: isUserAdmin } = req.body;
        
        if (useMongoose && User) {
            const user = await User.findOne({ id: userId });
            
            if (!user) {
                return res.status(404).json({ error: 'User not found' });
            }
            
            user.name = name;
            user.email = email;
            user.username = username;
            user.isAdmin = isUserAdmin;
            
            if (password) {
                user.password = await bcrypt.hash(password, 10);
            }
            
            await user.save();
            
            const { password: _, ...userWithoutPassword } = user.toObject();
            res.json(userWithoutPassword);
        } else {
            // In-memory fallback
            const user = Array.from(users.values()).find(u => u.id === userId);
            
            if (!user) {
                return res.status(404).json({ error: 'User not found' });
            }
            
            user.name = name;
            user.email = email;
            user.username = username;
            user.isAdmin = isUserAdmin;
            
            if (password) {
                user.password = await bcrypt.hash(password, 10);
            }
            
            users.set(username, user);
            
            const { password: _, ...userWithoutPassword } = user;
            res.json(userWithoutPassword);
        }
    } catch (error) {
        console.error('Update user error:', error);
        res.status(500).json({ error: 'Failed to update user' });
    }
});

// Toggle user role (admin)
app.post('/api/admin/users/:userId/toggle-role', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { userId } = req.params;
        
        if (useMongoose && User) {
            const user = await User.findOne({ id: userId });
            
            if (!user) {
                return res.status(404).json({ error: 'User not found' });
            }
            
            user.isAdmin = !user.isAdmin;
            await user.save();
            
            res.json({ message: 'User role updated' });
        } else {
            const user = Array.from(users.values()).find(u => u.id === userId);
            
            if (!user) {
                return res.status(404).json({ error: 'User not found' });
            }
            
            user.isAdmin = !user.isAdmin;
            res.json({ message: 'User role updated' });
        }
    } catch (error) {
        console.error('Toggle user role error:', error);
        res.status(500).json({ error: 'Failed to toggle user role' });
    }
});

// Toggle user status (admin)
app.post('/api/admin/users/:userId/toggle-status', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { userId } = req.params;
        
        if (useMongoose && User) {
            const user = await User.findOne({ id: userId });
            
            if (!user) {
                return res.status(404).json({ error: 'User not found' });
            }
            
            user.isActive = !user.isActive;
            await user.save();
            
            res.json({ message: 'User status updated' });
        } else {
            const user = Array.from(users.values()).find(u => u.id === userId);
            
            if (!user) {
                return res.status(404).json({ error: 'User not found' });
            }
            
            user.isActive = user.isActive !== false ? false : true;
            res.json({ message: 'User status updated' });
        }
    } catch (error) {
        console.error('Toggle user status error:', error);
        res.status(500).json({ error: 'Failed to toggle user status' });
    }
});

// Delete user (admin)
app.delete('/api/admin/users/:userId', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { userId } = req.params;
        
        if (useMongoose && User) {
            await User.deleteOne({ id: userId });
            res.json({ message: 'User deleted' });
        } else {
            const user = Array.from(users.values()).find(u => u.id === userId);
            if (user) {
                users.delete(user.username);
            }
            res.json({ message: 'User deleted' });
        }
    } catch (error) {
        console.error('Delete user error:', error);
        res.status(500).json({ error: 'Failed to delete user' });
    }
});

// Get all departments (admin)
app.get('/api/admin/departments', authenticateToken, isAdmin, async (req, res) => {
    try {
        if (useMongoose && Department) {
            const departments = await Department.find().sort({ name: 1 }).lean();
            res.json(departments);
        } else {
            const departments = [
                { id: 'dept-1', name: 'Разработка', description: 'Отдел разработки ПО', createdAt: new Date().toISOString() },
                { id: 'dept-2', name: 'Дизайн', description: 'Отдел дизайна и UX', createdAt: new Date().toISOString() },
                { id: 'dept-3', name: 'Маркетинг', description: 'Отдел маркетинга', createdAt: new Date().toISOString() },
                { id: 'dept-4', name: 'Продажи', description: 'Отдел продаж', createdAt: new Date().toISOString() },
                { id: 'dept-5', name: 'Поддержка', description: 'Служба поддержки', createdAt: new Date().toISOString() }
            ];
            res.json(departments);
        }
    } catch (error) {
        console.error('Get admin departments error:', error);
        res.status(500).json({ error: 'Failed to get departments' });
    }
});

// Create department (admin)
app.post('/api/admin/departments', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { name, description } = req.body;
        
        if (!name) {
            return res.status(400).json({ error: 'Name is required' });
        }
        
        const departmentData = {
            id: generateId(),
            name,
            description: description || '',
            createdAt: new Date().toISOString()
        };
        
        if (useMongoose && Department) {
            const department = new Department(departmentData);
            await department.save();
            res.status(201).json(department);
        } else {
            res.status(201).json(departmentData);
        }
    } catch (error) {
        console.error('Create department error:', error);
        res.status(500).json({ error: 'Failed to create department' });
    }
});

// Update department (admin)
app.put('/api/admin/departments/:deptId', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { deptId } = req.params;
        const { name, description } = req.body;
        
        if (useMongoose && Department) {
            const department = await Department.findOne({ id: deptId });
            
            if (!department) {
                return res.status(404).json({ error: 'Department not found' });
            }
            
            department.name = name;
            department.description = description || '';
            await department.save();
            
            res.json(department);
        } else {
            res.json({ id: deptId, name, description });
        }
    } catch (error) {
        console.error('Update department error:', error);
        res.status(500).json({ error: 'Failed to update department' });
    }
});

// Delete department (admin)
app.delete('/api/admin/departments/:deptId', authenticateToken, isAdmin, async (req, res) => {
    try {
        const { deptId } = req.params;
        
        if (useMongoose && Department) {
            await Department.deleteOne({ id: deptId });
        }
        
        res.json({ message: 'Department deleted' });
    } catch (error) {
        console.error('Delete department error:', error);
        res.status(500).json({ error: 'Failed to delete department' });
    }
});

// Get all tasks (admin)
app.get('/api/admin/tasks', authenticateToken, isAdmin, async (req, res) => {
    try {
        if (useMongoose && Task) {
            const tasks = await Task.find().sort({ createdAt: -1 }).lean();
            res.json(tasks);
        } else {
            const allTasks = Array.from(tasksMap.values());
            res.json(allTasks);
        }
    } catch (error) {
        console.error('Get admin tasks error:', error);
        res.status(500).json({ error: 'Failed to get tasks' });
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
    
   // ВАЖНО: Сохраняем mapping userId -> socketId
    userSockets.set(socket.userId, socket.id);
    console.log('✅ User socket mapped:', socket.userId, '->', socket.id);
    
    onlineUsers.set(socket.id, socket.userId);

    // Notify all users about online status
    io.emit('users:online', {
        userId: socket.userId,
        online: true
    });

    // Send online users list
    const onlineUserIds = Array.from(new Set(onlineUsers.values()));
    socket.emit('users:list', onlineUserIds);

    // ИСПРАВЛЕНО: Join user's chat rooms - ищем в обоих Maps
    const userChatsFromChats = Array.from(chats.values())
        .filter(chat => chat.participants && chat.participants.includes(socket.userId));
    
    const userChatsFromChatsMap = Array.from(chatsMap.values())
        .filter(chat => chat.participants && chat.participants.includes(socket.userId));
    
    const allUserChats = [...userChatsFromChats, ...userChatsFromChatsMap];
    
    console.log('👤 User chats:', allUserChats.length);
    
    allUserChats.forEach(chat => {
        socket.join(chat.id);
        console.log('✅ Joined chat room:', chat.id);
    });


    // Chat join
socket.on('chat:join', (chatId) => {
    console.log('👤 User manually joining chat room:', chatId);
    socket.join(chatId);
});

// Chat message
socket.on('message:send', async (data) => {
    console.log('📨 Received message:send:', data);
    console.log('User:', socket.userId);
    
const { chatId, text, type, metadata, tempId } = data;
    
    // ИСПРАВЛЕНО: Ищем чат в обоих местах
    let chat = chats.get(chatId);
    if (!chat) {
        chat = chatsMap.get(chatId);
    }
    if (!chat) {
        // Ищем в массивах значений
        chat = Array.from(chats.values()).find(c => c.id === chatId);
    }
    if (!chat) {
        chat = Array.from(chatsMap.values()).find(c => c.id === chatId);
    }
    
    console.log('Chat found:', chat ? 'yes' : 'no');
    
    if (!chat) {
        console.error('❌ Chat not found:', chatId);
        console.log('Available chats:', Array.from(chats.keys()));
        console.log('Available chatsMap:', Array.from(chatsMap.keys()));
        return socket.emit('error', { message: 'Chat not found' });
    }
    
    if (!chat.participants || !chat.participants.includes(socket.userId)) {
        console.error('❌ Access denied. User:', socket.userId, 'Participants:', chat.participants);
        return socket.emit('error', { message: 'Access denied' });
    }
    
    console.log('✅ Access granted');

    const messageId = `msg_${uuidv4()}`;
    const encryptedText = chat.encrypted ? encryptMessage(text) : text;

    const messageData = {
        id: messageId,
        chatId,
        senderId: socket.userId,
        text: encryptedText,
        type: type || 'text',
        metadata: metadata || {},
        createdAt: new Date().toISOString(),
        encrypted: chat.encrypted,
         delivered: false,
        read: false,
        tempId: tempId
    };
    
    console.log('💾 Saving message:', messageData.id);

    // Save to cache
    if (!messages.has(chatId)) {
        messages.set(chatId, []);
    }
    messages.get(chatId).push(messageData);
    console.log('✅ Message saved to memory');
    
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
                updatedAt: new Date().toISOString()
            }
        );
    } catch (dbError) {
        console.error('❌ Error updating chat in MongoDB:', dbError);
    }

    // ВАЖНО: Отправляем сообщение в комнату чата
    console.log('📤 Broadcasting message to room:', chatId);
    io.to(chatId).emit('message:new', {
        ...messageData,
        text: text // Send original (decrypted) text to all
    });
    
    console.log('✅ Message broadcasted');

    // Update task if this is a task chat
    if (chat.taskId) {
        console.log('📋 Updating task comments:', chat.taskId);
        
        if (useMongoose && Task) {
            await Task.updateOne(
                { id: chat.taskId },
                { 
                    $inc: { commentsCount: 1 },
                    $set: { hasUnread: true }
                }
            );
        } else {
            const task = tasksMap.get(chat.taskId);
            if (task) {
                task.commentsCount = (task.commentsCount || 0) + 1;
                task.hasUnread = true;
                tasksMap.set(chat.taskId, task);
            }
        }
        
        io.emit('task:comment', {
            taskId: chat.taskId,
            userId: socket.userId
        });
        
        console.log('✅ Task updated');
    }
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

  // Сообщение доставлено (когда получатель получил сообщение)
// Сообщение доставлено
socket.on('message:delivered', async (data) => {
    const { messageId, chatId } = data;
    
    console.log('✅ Message delivered:', messageId, 'Chat:', chatId);
    
    try {
        // Находим сообщение
        let message;
        
        if (useMongoose && Message) {
            message = await Message.findOneAndUpdate(
                { id: messageId },
                { 
                    delivered: true,
                    deliveredAt: new Date()
                },
                { new: true }
            );
        }
        
        // Обновляем в памяти
        const chatMessages = messages.get(chatId);
        if (chatMessages) {
            const msg = chatMessages.find(m => m.id === messageId);
            if (msg) {
                msg.delivered = true;
                msg.deliveredAt = new Date().toISOString();
                message = msg;
            }
        }
        
        if (message) {
            // ИСПРАВЛЕНО: Отправляем обновление ТОЛЬКО отправителю
            const senderSocketId = userSockets.get(message.senderId);
            if (senderSocketId) {
                io.to(senderSocketId).emit('message:status-updated', {
                    messageId,
                    delivered: true,
                    deliveredAt: new Date().toISOString()
                });
                
                console.log('✅ Sent delivered status to sender:', message.senderId);
            }
        }
        
    } catch (error) {
        console.error('❌ Error updating message delivered status:', error);
    }
});

// Сообщение прочитано
socket.on('message:read', async (data) => {
    const { messageId, chatId } = data;
    
    console.log('👁️ Message read:', messageId, 'Chat:', chatId);
    
    try {
        // Находим сообщение
        let message;
        
        if (useMongoose && Message) {
            message = await Message.findOneAndUpdate(
                { id: messageId },
                { 
                    read: true,
                    readAt: new Date(),
                    delivered: true,
                    deliveredAt: new Date()
                },
                { new: true }
            );
        }
        
        // Обновляем в памяти
        const chatMessages = messages.get(chatId);
        if (chatMessages) {
            const msg = chatMessages.find(m => m.id === messageId);
            if (msg) {
                msg.read = true;
                msg.readAt = new Date().toISOString();
                msg.delivered = true;
                msg.deliveredAt = new Date().toISOString();
                message = msg;
            }
        }
        
        if (message) {
            // ИСПРАВЛЕНО: Отправляем обновление ТОЛЬКО отправителю
            const senderSocketId = userSockets.get(message.senderId);
            if (senderSocketId) {
                io.to(senderSocketId).emit('message:status-updated', {
                    messageId,
                    read: true,
                    readAt: new Date().toISOString(),
                    delivered: true
                });
                
                console.log('✅ Sent read status to sender:', message.senderId);
            }
        }
        
    } catch (error) {
        console.error('❌ Error updating message read status:', error);
    }
});

// Пометить все сообщения чата как прочитанные
socket.on('messages:read-all', async (chatId) => {
    console.log('👁️ Marking all messages as read in chat:', chatId);
    
    try {
        // Находим чат
        let chat = chats.get(chatId) || chatsMap.get(chatId);
        
        if (!chat) {
            console.error('Chat not found:', chatId);
            return;
        }
        
        // Обновляем все непрочитанные сообщения в MongoDB
        if (useMongoose && Message) {
            await Message.updateMany(
                { 
                    chatId: chatId,
                    senderId: { $ne: socket.userId }, // Не наши сообщения
                    read: false
                },
                { 
                    read: true,
                    readAt: new Date(),
                    delivered: true,
                    deliveredAt: new Date()
                }
            );
        }
        
        // Обновляем в памяти
        const chatMessages = messages.get(chatId);
        if (chatMessages) {
            chatMessages.forEach(msg => {
                if (msg.senderId !== socket.userId && !msg.read) {
                    msg.read = true;
                    msg.readAt = new Date().toISOString();
                    msg.delivered = true;
                    msg.deliveredAt = new Date().toISOString();
                    
                    // Уведомляем о каждом обновлении
                    io.to(chatId).emit('message:status-updated', {
                        messageId: msg.id,
                        read: true,
                        readAt: msg.readAt,
                        delivered: true
                    });
                }
            });
        }
        
        // Сбрасываем счетчик непрочитанных
        if (chat.unreadCount) {
            chat.unreadCount[socket.userId] = 0;
            
            if (useMongoose && Chat) {
                await Chat.findOneAndUpdate(
                    { id: chatId },
                    { unreadCount: chat.unreadCount }
                );
            }
        }
        
        console.log('✅ All messages marked as read');
        
    } catch (error) {
        console.error('❌ Error marking messages as read:', error);
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