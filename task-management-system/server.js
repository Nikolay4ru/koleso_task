// server.js - Исправленная версия с правильным порядком инициализации
require('dotenv').config();

const express = require('express');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const multer = require('multer');
const { Server } = require('socket.io');
const http = require('http');
const path = require('path');
const fs = require('fs').promises;

// Импорт web-push только если VAPID ключи настроены
let webpush = null;
if (process.env.VAPID_PUBLIC_KEY && process.env.VAPID_PRIVATE_KEY) {
    try {
        webpush = require('web-push');
        webpush.setVapidDetails(
            `mailto:${process.env.EMAIL_USER || 'admin@example.com'}`,
            process.env.VAPID_PUBLIC_KEY,
            process.env.VAPID_PRIVATE_KEY
        );
        console.log('✓ VAPID ключи настроены для push уведомлений');
    } catch (error) {
        console.warn('⚠ Ошибка настройки web-push:', error.message);
        webpush = null;
    }
}

// Конфигурация
const config = {
    port: process.env.PORT || 3000,
    jwtSecret: process.env.JWT_SECRET || 'your-secret-key-change-in-production',
    dbPath: './data/database.json',
    uploadsDir: './uploads',
    email: {
        host: process.env.EMAIL_HOST || 'smtp.gmail.com',
        port: process.env.EMAIL_PORT || 587,
        user: process.env.EMAIL_USER || 'your-email@gmail.com',
        pass: process.env.EMAIL_PASS || 'your-app-password'
    },
    telegram: {
        botToken: process.env.TELEGRAM_BOT_TOKEN || 'YOUR_BOT_TOKEN',
        enabled: process.env.TELEGRAM_ENABLED === 'true'
    },
    adminEmail: process.env.ADMIN_EMAIL || 'admin@example.com',
    vapid: {
        publicKey: process.env.VAPID_PUBLIC_KEY,
        privateKey: process.env.VAPID_PRIVATE_KEY,
        subject: process.env.APP_URL || 'https://task2.koleso.app'
    }
};

// Инициализация Express
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST", "PUT", "DELETE"]
    }
});

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(express.static('public'));

// База данных
class Database {
    constructor() {
        this.data = {
            users: [],
            tasks: [],
            comments: [],
            files: [],
            chats: [],
            chatMessages: [],
            chatMembers: [],
            calls: []
        };
    }

    async load() {
        try {
            await fs.mkdir('./data', { recursive: true });
            const data = await fs.readFile(config.dbPath, 'utf8');
            this.data = JSON.parse(data);
            console.log('✓ База данных загружена');
        } catch (error) {
            console.log('ℹ Создание новой базы данных');
            await this.save();
        }
    }

    async save() {
        try {
            await fs.writeFile(config.dbPath, JSON.stringify(this.data, null, 2));
        } catch (error) {
            console.error('Ошибка сохранения БД:', error);
        }
    }

    async findUserByEmail(email) {
        return this.data.users.find(u => u.email === email);
    }

    async findUserById(id) {
        return this.data.users.find(u => u.id === id);
    }

    async createUser(userData) {
        const user = {
            id: Date.now(),
            ...userData,
            createdAt: new Date().toISOString(),
            status: 'offline'
        };
        this.data.users.push(user);
        await this.save();
        return user;
    }

    async updateUserStatus(userId, status) {
        const user = this.data.users.find(u => u.id === userId);
        if (user) {
            user.status = status;
            await this.save();
        }
    }

    async getTasks(userId = null, userRole = null) {
        if (userRole === 'admin') {
            return this.data.tasks;
        }
        if (userId) {
            return this.data.tasks.filter(task => 
                task.creatorId === userId || 
                (task.assignees && task.assignees.includes(userId))
            );
        }
        return this.data.tasks;
    }

    async createTask(taskData) {
        const task = {
            id: Date.now(),
            ...taskData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };
        this.data.tasks.push(task);
        await this.save();
        return task;
    }

    async updateTask(id, updates) {
        const index = this.data.tasks.findIndex(t => t.id === id);
        if (index === -1) return null;

        this.data.tasks[index] = {
            ...this.data.tasks[index],
            ...updates,
            updatedAt: new Date().toISOString()
        };
        await this.save();
        return this.data.tasks[index];
    }

    async deleteTask(id) {
        const index = this.data.tasks.findIndex(t => t.id === id);
        if (index === -1) return false;

        this.data.tasks.splice(index, 1);
        await this.save();
        return true;
    }

    async getTask(id) {
        return this.data.tasks.find(t => t.id === id);
    }
}

// ChatManager class - встроенный в server.js
class ChatManager {
    constructor(db, io) {
        this.db = db;
        this.io = io;
        this.userSockets = new Map(); // userId -> Set of socketIds
        this.activeCalls = new Map(); // callId -> call object
        this.socketUsers = new Map(); // socketId -> userId
        
        // Инициализация структур БД
        if (!this.db.data.chats) this.db.data.chats = [];
        if (!this.db.data.chatMessages) this.db.data.chatMessages = [];
        if (!this.db.data.chatMembers) this.db.data.chatMembers = [];
        if (!this.db.data.calls) this.db.data.calls = [];
    }

    // Управление подключениями
    addUserSocket(userId, socketId) {
        if (!this.userSockets.has(userId)) {
            this.userSockets.set(userId, new Set());
        }
        this.userSockets.get(userId).add(socketId);
        this.socketUsers.set(socketId, userId);
        console.log(`User ${userId} connected with socket ${socketId}`);
    }

    removeUserSocket(userId, socketId) {
        const sockets = this.userSockets.get(userId);
        if (sockets) {
            sockets.delete(socketId);
            if (sockets.size === 0) {
                this.userSockets.delete(userId);
            }
        }
        this.socketUsers.delete(socketId);
        console.log(`User ${userId} disconnected socket ${socketId}`);
    }

    isUserOnline(userId) {
        return this.userSockets.has(userId);
    }

    sendToUser(userId, event, data) {
        const socketIds = Array.from(this.userSockets.get(userId) || []);
        for (const socketId of socketIds) {
            this.io.to(socketId).emit(event, data);
        }
    }

    

    async getUserChats(userId) {
        // Убеждаемся, что структура существует
        if (!this.db.data.chatMembers) {
            this.db.data.chatMembers = [];
        }
        if (!this.db.data.chats) {
            this.db.data.chats = [];
        }

        const userChatIds = this.db.data.chatMembers
            .filter(m => m.userId === userId && m.status === 'active')
            .map(m => m.chatId);

        const chats = this.db.data.chats.filter(chat => userChatIds.includes(chat.id));
        
        // Добавляем информацию о непрочитанных сообщениях
        for (const chat of chats) {
            chat.unreadCount = chat.unreadCounts && chat.unreadCounts[userId] || 0;
            
            // Для приватных чатов добавляем информацию о собеседнике
            if (chat.type === 'private') {
                const otherUserId = chat.members.find(id => id !== userId);
                const otherUser = this.db.data.users.find(u => u.id === otherUserId);
                if (otherUser) {
                    chat.otherUser = {
                        id: otherUser.id,
                        name: otherUser.name,
                        online: this.isUserOnline(otherUser.id)
                    };
                }
            }
        }

        return chats.sort((a, b) => 
            new Date(b.lastMessageAt || b.createdAt) - new Date(a.lastMessageAt || a.createdAt)
        );
    }

    // ===== УПРАВЛЕНИЕ ЗВОНКАМИ =====
    async startCall(chatId, initiatorId, callType) {
        console.log(`Starting ${callType} call in chat ${chatId} by user ${initiatorId}`);
        
        const call = {
            id: Date.now().toString(),
            chatId,
            initiatorId,
            type: callType,
            status: 'pending',
            participants: [initiatorId],
            createdAt: new Date().toISOString()
        };

        this.activeCalls.set(call.id, call);

        // Сохраняем в БД
        this.db.data.calls.push({ ...call });
        await this.db.save();

        // Уведомляем получателя (для простоты считаем что chatId это userId получателя)
        const recipientId = parseInt(chatId);
        if (recipientId && recipientId !== initiatorId) {
            this.sendToUser(recipientId, 'incoming_call', {
                callId: call.id,
                initiatorId,
                callType,
                chatName: 'Приватный чат',
                chatId
            });
        }

        return call;
    }

    async acceptCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call || call.status === 'ended') return null;

        if (!call.participants.includes(userId)) {
            call.participants.push(userId);
        }

        call.status = 'active';
        
        // Уведомляем инициатора о принятии звонка
        this.sendToUser(call.initiatorId, 'call_accepted', {
            callId,
            userId,
            participants: call.participants
        });

        return call;
    }

    async declineCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        // Уведомляем инициатора об отклонении
        this.sendToUser(call.initiatorId, 'call_declined', {
            callId,
            userId
        });

        return this.endCall(callId);
    }

    async endCall(callId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        call.status = 'ended';
        call.endedAt = new Date().toISOString();

        // Уведомляем всех участников о завершении звонка
        for (const participantId of call.participants) {
            this.sendToUser(participantId, 'call_ended', {
                callId,
                endedAt: call.endedAt
            });
        }

        this.activeCalls.delete(callId);
        return call;
    }

    // WebRTC сигналинг
    async handleSignaling(callId, fromUserId, signal) {
        const call = this.activeCalls.get(callId);
        if (!call) return;

        console.log(`Handling ${signal.type} signal for call ${callId} from user ${fromUserId}`);

        switch (signal.type) {
            case 'offer':
                for (const participantId of call.participants) {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'sdp_offer', {
                            callId,
                            userId: fromUserId,
                            offer: signal.offer
                        });
                    }
                }
                break;

            case 'answer':
                for (const participantId of call.participants) {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'sdp_answer', {
                            callId,
                            userId: fromUserId,
                            answer: signal.answer
                        });
                    }
                }
                break;

            case 'ice-candidate':
                for (const participantId of call.participants) {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'ice_candidate', {
                            callId,
                            userId: fromUserId,
                            candidate: signal.candidate
                        });
                    }
                }
                break;
        }
    }
}

// Middleware для аутентификации
const authMiddleware = async (req, res, next) => {
    try {
        const token = req.headers.authorization?.replace('Bearer ', '');
        
        if (!token) {
            return res.status(401).json({ error: 'Токен не предоставлен' });
        }

        const decoded = jwt.verify(token, config.jwtSecret);
        const user = await db.findUserById(decoded.userId);
        
        if (!user) {
            return res.status(401).json({ error: 'Пользователь не найден' });
        }

        req.user = user;
        next();
    } catch (error) {
        console.error('Ошибка аутентификации:', error.message);
        res.status(401).json({ error: 'Недействительный токен' });
    }
};

// Инициализация базы данных
const db = new Database();

// ВАЖНО: Инициализация ChatManager ПОСЛЕ создания db
let chatManager;

// HTML маршруты
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'tasks.html'));
});

app.get('/login', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

app.get('/tasks', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'tasks.html'));
});

app.get('/task/:id', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'task.html'));
});

app.get('/messenger', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'messenger.html'));
});

// API маршруты
app.post('/api/register', async (req, res) => {
    try {
        const { name, email, password } = req.body;

        if (!name || !email || !password) {
            return res.status(400).json({ error: 'Все поля обязательны' });
        }

        const existingUser = await db.findUserByEmail(email);
        if (existingUser) {
            return res.status(400).json({ error: 'Пользователь уже существует' });
        }

        const hashedPassword = await bcrypt.hash(password, 10);
        
        const user = await db.createUser({
            name,
            email,
            password: hashedPassword,
            role: email === config.adminEmail ? 'admin' : 'user'
        });

        const token = jwt.sign({ userId: user.id }, config.jwtSecret);
        
        const { password: _, ...userWithoutPassword } = user;
        res.json({ user: userWithoutPassword, token });

    } catch (error) {
        console.error('Ошибка регистрации:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.post('/api/login', async (req, res) => {
    try {
        const { email, password } = req.body;

        const user = await db.findUserByEmail(email);
        if (!user) {
            return res.status(400).json({ error: 'Неверные учетные данные' });
        }

        const validPassword = await bcrypt.compare(password, user.password);
        if (!validPassword) {
            return res.status(400).json({ error: 'Неверные учетные данные' });
        }

        const token = jwt.sign({ userId: user.id }, config.jwtSecret);
        
        const { password: _, ...userWithoutPassword } = user;
        res.json({ user: userWithoutPassword, token });

    } catch (error) {
        console.error('Ошибка входа:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.get('/api/profile', authMiddleware, (req, res) => {
    const { password, ...userWithoutPassword } = req.user;
    res.json(userWithoutPassword);
});

app.get('/api/users', authMiddleware, async (req, res) => {
    try {
        const users = db.data.users.map(u => {
            const { password, ...userWithoutPassword } = u;
            return userWithoutPassword;
        });
        res.json(users);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.get('/api/tasks', authMiddleware, async (req, res) => {
    try {
        const tasks = await db.getTasks(req.user.id, req.user.role);
        res.json(tasks);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.post('/api/tasks', authMiddleware, async (req, res) => {
    try {
        const task = await db.createTask({
            ...req.body,
            creatorId: req.user.id,
            creatorName: req.user.name
        });
        
        io.emit('task_created', task);
        res.json(task);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.get('/api/tasks/:id', authMiddleware, async (req, res) => {
    try {
        const task = await db.getTask(parseInt(req.params.id));
        if (!task) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }
        res.json(task);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.put('/api/tasks/:id', authMiddleware, async (req, res) => {
    try {
        const task = await db.updateTask(parseInt(req.params.id), req.body);
        if (!task) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }
        
        io.emit('task_updated', task);
        res.json(task);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.delete('/api/tasks/:id', authMiddleware, async (req, res) => {
    try {
        const success = await db.deleteTask(parseInt(req.params.id));
        if (!success) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }
        
        io.emit('task_deleted', { id: parseInt(req.params.id) });
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

// API для push уведомлений
app.get('/api/vapid-public-key', (req, res) => {
    if (process.env.VAPID_PUBLIC_KEY) {
        res.json({ publicKey: process.env.VAPID_PUBLIC_KEY });
    } else {
        res.status(503).json({ error: 'Push уведомления недоступны' });
    }
});

app.post('/api/push-subscribe', authMiddleware, async (req, res) => {
    try {
        const { subscription, userId } = req.body;
        
        // Сохраняем подписку в БД
        const user = await db.findUserById(userId);
        if (user) {
            user.pushSubscription = subscription;
            await db.save();
        }
        
        res.json({ success: true });
    } catch (error) {
        console.error('Ошибка сохранения push подписки:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

// API для чатов (базовые endpoint'ы)
app.get('/api/chats', authMiddleware, async (req, res) => {
    try {
        const chats = await chatManager.getUserChats(req.user.id);
        res.json(chats);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.post('/api/chats', authMiddleware, async (req, res) => {
    try {
        // Создание чата - пока заглушка
        res.json({ id: Date.now().toString(), type: 'private' });
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.get('/api/chats/:id/messages', authMiddleware, async (req, res) => {
    try {
        // Возвращаем пустой список сообщений
        res.json([]);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

// API для отклонения звонка через push уведомление
app.post('/api/calls/:callId/decline', authMiddleware, async (req, res) => {
    try {
        const callId = req.params.callId;
        const call = await chatManager.endCall(callId);
        
        if (call) {
            console.log('Звонок отклонен через API:', callId);
        }
        
        res.json({ success: true });
    } catch (error) {
        console.error('Ошибка отклонения звонка:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

// WebSocket обработка
io.on('connection', (socket) => {
    console.log('WebSocket подключен:', socket.id);
    
    socket.on('authenticate', async (token) => {
        try {
            const decoded = jwt.verify(token, config.jwtSecret);
            const user = await db.findUserById(decoded.userId);
            
            if (user) {
                socket.userId = user.id;
                socket.user = user;
                
                chatManager.addUserSocket(user.id, socket.id);
                await db.updateUserStatus(user.id, 'online');
                
                socket.emit('authenticated', { success: true, user });
            } else {
                socket.emit('authenticated', { success: false, error: 'User not found' });
            }
        } catch (error) {
            socket.emit('authenticated', { success: false, error: 'Invalid token' });
        }
    });

    // Обработчики звонков
    socket.on('call_start', async ({ chatId, type }) => {
        if (!socket.userId) return;

        try {
            const call = await chatManager.startCall(chatId, socket.userId, type);
            socket.emit('call_started', call);
        } catch (error) {
            socket.emit('call_error', { error: error.message });
        }
    });

    socket.on('call_accepted', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            const call = await chatManager.acceptCall(callId, socket.userId);
            socket.emit('call_joined', call);
        } catch (error) {
            socket.emit('call_error', { error: error.message });
        }
    });

    socket.on('call_declined', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            await chatManager.declineCall(callId, socket.userId);
        } catch (error) {
            socket.emit('call_error', { error: error.message });
        }
    });

    socket.on('call_end', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            await chatManager.endCall(callId);
        } catch (error) {
            console.error('Error ending call:', error);
        }
    });

    socket.on('call_signal', async ({ callId, signal }) => {
        if (!socket.userId) return;

        try {
            await chatManager.handleSignaling(callId, socket.userId, signal);
        } catch (error) {
            console.error('Error handling signaling:', error);
        }
    });

    socket.on('disconnect', () => {
        if (socket.userId) {
            chatManager.removeUserSocket(socket.userId, socket.id);
            
            if (!chatManager.isUserOnline(socket.userId)) {
                db.updateUserStatus(socket.userId, 'offline');
            }
        }
    });
});

// Запуск сервера
async function startServer() {
    try {
        await fs.mkdir('./data', { recursive: true });
        await fs.mkdir('./uploads', { recursive: true });
        
        await db.load();
        
        // Создаем ChatManager ПОСЛЕ загрузки db
        chatManager = new ChatManager(db, io);
        
        server.listen(config.port, '0.0.0.0', () => {
            console.log(`✅ Сервер запущен на порту ${config.port}`);
            console.log(`📧 Админ email: ${config.adminEmail}`);
            console.log(`🌐 URL: http://localhost:${config.port}`);
            console.log(`👥 Пользователей: ${db.data.users.length}`);
            console.log(`📋 Задач: ${db.data.tasks.length}`);
        });
        
    } catch (error) {
        console.error('❌ Ошибка запуска сервера:', error);
        process.exit(1);
    }
}

// Обработка ошибок
process.on('unhandledRejection', (reason, promise) => {
    console.error('Необработанный Promise rejection:', reason);
});

process.on('uncaughtException', (error) => {
    console.error('Необработанное исключение:', error);
    process.exit(1);
});

startServer();