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
app.use(cors({
  origin: 'https://task2.koleso.app'
}));
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));
app.use(express.static('public'));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100
});
app.use('/api/', limiter);

// JWT Secret
const JWT_SECRET = process.env.JWT_SECRET || 'your-super-secret-jwt-key-change-in-production';
const ENCRYPTION_KEY = process.env.ENCRYPTION_KEY || crypto.randomBytes(32).toString('hex');

// In-memory database (replace with real DB in production)
const users = new Map();
const chats = new Map();
const messages = new Map();
const conferences = new Map();
const onlineUsers = new Map();

// Initialize admin user
const adminPassword = bcrypt.hashSync('admin123', 10);
users.set('admin', {
  id: 'user_admin',
  username: 'admin',
  email: 'admin@company.com',
  password: adminPassword,
  name: 'System Admin',
  avatar: null,
  createdAt: new Date(),
  publicKey: null
});

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

    const user = {
      id: userId,
      username,
      email,
      password: hashedPassword,
      name: name || username,
      avatar: null,
      createdAt: new Date(),
      publicKey: null
    };

    users.set(username, user);

    const token = jwt.sign({ id: userId, username }, JWT_SECRET, { expiresIn: '7d' });

    res.json({
      token,
      user: {
        id: userId,
        username,
        email,
        name: user.name,
        avatar: user.avatar
      }
    });
  } catch (error) {
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
    res.status(500).json({ error: 'Login failed' });
  }
});

app.get('/api/users', authenticateToken, (req, res) => {
  const userList = Array.from(users.values()).map(u => ({
    id: u.id,
    username: u.username,
    name: u.name,
    avatar: u.avatar,
    online: onlineUsers.has(u.id)
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
          text: messageText, // Decrypted text for preview
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

app.post('/api/chats', authenticateToken, (req, res) => {
  try {
    const { type, participants, name } = req.body;
    const userId = req.user.id;

    if (!participants || participants.length === 0) {
      return res.status(400).json({ error: 'Participants required' });
    }

    const chatId = `chat_${uuidv4()}`;
    const allParticipants = [...new Set([userId, ...participants])];

    const chat = {
      id: chatId,
      type: type || 'private',
      name: name || null,
      participants: allParticipants,
      createdBy: userId,
      createdAt: new Date(),
      encrypted: true,
      unreadCount: {}
    };

    chats.set(chatId, chat);
    messages.set(chatId, []);

    // Notify participants
    allParticipants.forEach(participantId => {
      const socketId = Array.from(onlineUsers.entries())
        .find(([sid, uid]) => uid === participantId)?.[0];
      
      if (socketId) {
        io.to(socketId).emit('chat:created', chat);
      }
    });

    res.json(chat);
  } catch (error) {
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
  socket.on('message:send', (data) => {
    const { chatId, text, type, metadata } = data;
    const chat = chats.get(chatId);

    if (!chat || !chat.participants.includes(socket.userId)) {
      return socket.emit('error', { message: 'Access denied' });
    }

    const messageId = `msg_${uuidv4()}`;
    const encryptedText = chat.encrypted ? encryptMessage(text) : text;

    const message = {
      id: messageId,
      chatId,
      senderId: socket.userId,
      text: encryptedText,
      type: type || 'text',
      metadata: metadata || {},
      createdAt: new Date(),
      encrypted: chat.encrypted
    };

    if (!messages.has(chatId)) {
      messages.set(chatId, []);
    }
    messages.get(chatId).push(message);

    // Update unread counts
    chat.participants.forEach(participantId => {
      if (participantId !== socket.userId) {
        chat.unreadCount = chat.unreadCount || {};
        chat.unreadCount[participantId] = (chat.unreadCount[participantId] || 0) + 1;
      }
    });

    // Send to all participants
    io.to(chatId).emit('message:new', {
      ...message,
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
    socket.join(`conference:${conferenceId}`);
    
    if (!conferences.has(conferenceId)) {
      conferences.set(conferenceId, {
        id: conferenceId,
        participants: new Set(),
        startedAt: new Date()
      });
    }

    const conference = conferences.get(conferenceId);
    conference.participants.add(socket.userId);

    // Notify existing participants
    socket.to(`conference:${conferenceId}`).emit('conference:participant:joined', {
      userId: socket.userId,
      username: socket.username
    });

    // Send existing participants to new user
    const existingParticipants = Array.from(conference.participants)
      .filter(id => id !== socket.userId)
      .map(id => {
        const user = Array.from(users.values()).find(u => u.id === id);
        return {
          userId: id,
          username: user?.username
        };
      });

    socket.emit('conference:participants', existingParticipants);
  });

  socket.on('conference:leave', (conferenceId) => {
    const conference = conferences.get(conferenceId);
    if (conference) {
      conference.participants.delete(socket.userId);
      
      socket.to(`conference:${conferenceId}`).emit('conference:participant:left', {
        userId: socket.userId
      });

      if (conference.participants.size === 0) {
        conferences.delete(conferenceId);
      }
    }
    
    socket.leave(`conference:${conferenceId}`);
  });

  // WebRTC signaling
  socket.on('webrtc:offer', ({ to, offer, conferenceId }) => {
    const toSocketId = Array.from(onlineUsers.entries())
      .find(([sid, uid]) => uid === to)?.[0];
    
    if (toSocketId) {
      io.to(toSocketId).emit('webrtc:offer', {
        from: socket.userId,
        offer,
        conferenceId
      });
    }
  });

  socket.on('webrtc:answer', ({ to, answer, conferenceId }) => {
    const toSocketId = Array.from(onlineUsers.entries())
      .find(([sid, uid]) => uid === to)?.[0];
    
    if (toSocketId) {
      io.to(toSocketId).emit('webrtc:answer', {
        from: socket.userId,
        answer,
        conferenceId
      });
    }
  });

  socket.on('webrtc:ice-candidate', ({ to, candidate, conferenceId }) => {
    const toSocketId = Array.from(onlineUsers.entries())
      .find(([sid, uid]) => uid === to)?.[0];
    
    if (toSocketId) {
      io.to(toSocketId).emit('webrtc:ice-candidate', {
        from: socket.userId,
        candidate,
        conferenceId
      });
    }
  });

  // Disconnect
  socket.on('disconnect', () => {
    console.log(`User disconnected: ${socket.username}`);
    
    // Remove from conferences
    conferences.forEach((conference, conferenceId) => {
      if (conference.participants.has(socket.userId)) {
        conference.participants.delete(socket.userId);
        io.to(`conference:${conferenceId}`).emit('conference:participant:left', {
          userId: socket.userId
        });
      }
    });

    onlineUsers.delete(socket.id);

    // Notify about offline status
    io.emit('users:online', {
      userId: socket.userId,
      online: false
    });
  });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`🚀 Server running on port ${PORT}`);
  console.log(`📱 Open http://localhost:${PORT} in your browser`);
});