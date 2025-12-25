// server.js - Полная версия с мессенджером и звонками
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
    port: process.env.PORT || 3010,
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
            
            // Инициализация структур если их нет
            if (!this.data.chats) this.data.chats = [];
            if (!this.data.chatMessages) this.data.chatMessages = [];
            if (!this.data.chatMembers) this.data.chatMembers = [];
            if (!this.data.calls) this.data.calls = [];
            
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

// ChatManager class - полная версия
class ChatManager {
    constructor(db, io) {
        this.db = db;
        this.io = io;
        this.userSockets = new Map(); // userId -> Set of socketIds
        this.activeCalls = new Map(); // callId -> call object
        this.socketUsers = new Map(); // socketId -> userId
        this.typingUsers = new Map(); // chatId -> Set of userIds
        
        // Инициализация структур БД
        if (!this.db.data.chats) this.db.data.chats = [];
        if (!this.db.data.chatMessages) this.db.data.chatMessages = [];
        if (!this.db.data.chatMembers) this.db.data.chatMembers = [];
        if (!this.db.data.calls) this.db.data.calls = [];
    }

    // ===== УПРАВЛЕНИЕ ПОДКЛЮЧЕНИЯМИ =====
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

    // ===== УПРАВЛЕНИЕ ЧАТАМИ =====
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
            if (!chat.unreadCounts) chat.unreadCounts = {};
            chat.unreadCount = chat.unreadCounts[userId] || 0;
            
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

    async getOrCreatePrivateChat(userId1, userId2) {
        // Проверяем, существует ли уже приватный чат между этими пользователями
        const existingChat = this.db.data.chats.find(chat => 
            chat.type === 'private' && 
            chat.members.includes(userId1) && 
            chat.members.includes(userId2)
        );

        if (existingChat) {
            return existingChat;
        }

        // Создаём новый приватный чат
        const chatId = Date.now().toString();
        const chat = {
            id: chatId,
            type: 'private',
            members: [userId1, userId2],
            createdAt: new Date().toISOString(),
            lastMessageAt: null,
            lastMessage: null,
            unreadCounts: {
                [userId1]: 0,
                [userId2]: 0
            }
        };

        this.db.data.chats.push(chat);

        // Добавляем членство для обоих пользователей
        this.db.data.chatMembers.push({
            chatId,
            userId: userId1,
            status: 'active',
            joinedAt: new Date().toISOString()
        });

        this.db.data.chatMembers.push({
            chatId,
            userId: userId2,
            status: 'active',
            joinedAt: new Date().toISOString()
        });

        await this.db.save();
        return chat;
    }

    async createGroupChat(name, creatorId, memberIds) {
        const chatId = Date.now().toString();
        const members = [creatorId, ...memberIds.filter(id => id !== creatorId)];
        
        const chat = {
            id: chatId,
            type: 'group',
            name,
            members,
            creatorId,
            createdAt: new Date().toISOString(),
            lastMessageAt: null,
            lastMessage: null,
            unreadCounts: {}
        };

        // Инициализируем счётчики непрочитанных для всех участников
        members.forEach(memberId => {
            chat.unreadCounts[memberId] = 0;
        });

        this.db.data.chats.push(chat);

        // Добавляем членство для всех участников
        members.forEach(memberId => {
            this.db.data.chatMembers.push({
                chatId,
                userId: memberId,
                status: 'active',
                joinedAt: new Date().toISOString()
            });
        });

        await this.db.save();

        // Уведомляем всех участников о новом чате
        members.forEach(memberId => {
            this.sendToUser(memberId, 'chat_created', chat);
        });

        return chat;
    }

    async createTaskChat(taskId, taskTitle, members) {
        const chatId = `task_${taskId}`;
        
        // Проверяем, не существует ли уже чат для этой задачи
        const existingChat = this.db.data.chats.find(c => c.id === chatId);
        if (existingChat) {
            return existingChat;
        }

        const chat = {
            id: chatId,
            type: 'task',
            taskId,
            name: `Задача: ${taskTitle}`,
            members,
            createdAt: new Date().toISOString(),
            lastMessageAt: null,
            lastMessage: null,
            unreadCounts: {}
        };

        // Инициализируем счётчики непрочитанных
        members.forEach(memberId => {
            chat.unreadCounts[memberId] = 0;
        });

        this.db.data.chats.push(chat);

        // Добавляем членство
        members.forEach(memberId => {
            this.db.data.chatMembers.push({
                chatId,
                userId: memberId,
                status: 'active',
                joinedAt: new Date().toISOString()
            });
        });

        await this.db.save();
        return chat;
    }

    // ===== УПРАВЛЕНИЕ СООБЩЕНИЯМИ =====
    async sendMessage(chatId, senderId, content, type = 'text', attachments = []) {
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) {
            throw new Error('Чат не найден');
        }

        // Проверяем, является ли отправитель участником чата
        if (!chat.members.includes(senderId)) {
            throw new Error('Вы не являетесь участником этого чата');
        }

        const messageId = `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const message = {
            id: messageId,
            chatId,
            senderId,
            content,
            type,
            attachments: attachments || [],
            timestamp: new Date().toISOString(),
            readBy: [senderId]
        };

        // Сохраняем сообщение
        if (!this.db.data.chatMessages) {
            this.db.data.chatMessages = [];
        }
        this.db.data.chatMessages.push(message);

        // Обновляем информацию о чате
        chat.lastMessage = {
            content,
            senderId,
            timestamp: message.timestamp
        };
        chat.lastMessageAt = message.timestamp;

        // Увеличиваем счётчик непрочитанных для всех участников, кроме отправителя
        if (!chat.unreadCounts) chat.unreadCounts = {};
        chat.members.forEach(memberId => {
            if (memberId !== senderId) {
                chat.unreadCounts[memberId] = (chat.unreadCounts[memberId] || 0) + 1;
            }
        });

        await this.db.save();

        // Отправляем сообщение всем участникам через WebSocket
        const sender = this.db.data.users.find(u => u.id === senderId);
        const messageWithSender = {
            ...message,
            sender: sender ? { id: sender.id, name: sender.name } : null
        };

        chat.members.forEach(memberId => {
            this.sendToUser(memberId, 'new_message', {
                chatId,
                message: messageWithSender,
                chat: {
                    id: chat.id,
                    lastMessage: chat.lastMessage,
                    lastMessageAt: chat.lastMessageAt,
                    unreadCount: chat.unreadCounts[memberId] || 0
                }
            });
        });

        return message;
    }

    async getMessages(chatId, limit = 50, before = null) {
        let messages = this.db.data.chatMessages.filter(m => m.chatId === chatId);

        // Фильтруем по времени если указан параметр before
        if (before) {
            messages = messages.filter(m => new Date(m.timestamp) < new Date(before));
        }

        // Сортируем по времени (новые сначала) и ограничиваем количество
        messages = messages
            .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
            .slice(0, limit);

        // Добавляем информацию об отправителях
        const messagesWithSenders = messages.map(msg => {
            const sender = this.db.data.users.find(u => u.id === msg.senderId);
            return {
                ...msg,
                sender: sender ? { id: sender.id, name: sender.name } : null
            };
        });

        // Возвращаем в хронологическом порядке
        return messagesWithSenders.reverse();
    }

    async markAsRead(chatId, userId) {
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) return;

        // Сбрасываем счётчик непрочитанных
        if (!chat.unreadCounts) chat.unreadCounts = {};
        chat.unreadCounts[userId] = 0;

        // Помечаем все сообщения как прочитанные
        const chatMessages = this.db.data.chatMessages.filter(m => m.chatId === chatId);
        chatMessages.forEach(msg => {
            if (!msg.readBy) msg.readBy = [];
            if (!msg.readBy.includes(userId)) {
                msg.readBy.push(userId);
            }
        });

        await this.db.save();

        // Уведомляем отправителей о прочтении
        chat.members.forEach(memberId => {
            if (memberId !== userId) {
                this.sendToUser(memberId, 'messages_read', { chatId, userId });
            }
        });
    }

    // ===== TYPING ИНДИКАТОРЫ =====
    setTyping(chatId, userId, isTyping) {
        if (!this.typingUsers.has(chatId)) {
            this.typingUsers.set(chatId, new Set());
        }

        const typingSet = this.typingUsers.get(chatId);
        
        if (isTyping) {
            typingSet.add(userId);
        } else {
            typingSet.delete(userId);
        }

        // Уведомляем других участников
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (chat) {
            const user = this.db.data.users.find(u => u.id === userId);
            chat.members.forEach(memberId => {
                if (memberId !== userId) {
                    this.sendToUser(memberId, 'user_typing', {
                        chatId,
                        userId,
                        userName: user ? user.name : 'Unknown',
                        isTyping
                    });
                }
            });
        }
    }

    // ===== УПРАВЛЕНИЕ ЗВОНКАМИ =====
    async startCall(chatId, initiatorId, callType) {
        console.log(`Starting ${callType} call in chat ${chatId} by user ${initiatorId}`);
        
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) {
            throw new Error('Чат не найден');
        }

        const callId = Date.now().toString();
        const call = {
            id: callId,
            chatId,
            initiatorId,
            type: callType,
            status: 'pending',
            participants: [initiatorId],
            createdAt: new Date().toISOString()
        };

        this.activeCalls.set(callId, call);
        this.db.data.calls.push({ ...call });
        await this.db.save();

        // Получаем информацию об инициаторе
        const initiator = this.db.data.users.find(u => u.id === initiatorId);

        // Уведомляем всех остальных участников чата о входящем звонке
        chat.members.forEach(memberId => {
            if (memberId !== initiatorId) {
                this.sendToUser(memberId, 'incoming_call', {
                    callId,
                    chatId,
                    initiatorId,
                    initiatorName: initiator ? initiator.name : 'Unknown',
                    callType,
                    chatName: chat.name || 'Приватный чат'
                });
            }
        });

        return call;
    }

    async acceptCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call || call.status === 'ended') {
            throw new Error('Звонок не найден или уже завершён');
        }

        if (!call.participants.includes(userId)) {
            call.participants.push(userId);
        }

        call.status = 'active';
        
        // Уведомляем всех участников о принятии звонка
        const user = this.db.data.users.find(u => u.id === userId);
        call.participants.forEach(participantId => {
            this.sendToUser(participantId, 'call_accepted', {
                callId,
                userId,
                userName: user ? user.name : 'Unknown',
                participants: call.participants
            });
        });

        return call;
    }

    async declineCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        const user = this.db.data.users.find(u => u.id === userId);

        // Уведомляем инициатора об отклонении
        this.sendToUser(call.initiatorId, 'call_declined', {
            callId,
            userId,
            userName: user ? user.name : 'Unknown'
        });

        return this.endCall(callId);
    }

    async endCall(callId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        call.status = 'ended';
        call.endedAt = new Date().toISOString();

        // Уведомляем всех участников о завершении звонка
        call.participants.forEach(participantId => {
            this.sendToUser(participantId, 'call_ended', {
                callId,
                endedAt: call.endedAt
            });
        });

        this.activeCalls.delete(callId);
        return call;
    }

    // WebRTC сигналинг
    async handleSignaling(callId, fromUserId, signal) {
        const call = this.activeCalls.get(callId);
        if (!call) {
            console.warn(`Call ${callId} not found for signaling`);
            return;
        }

        console.log(`Handling ${signal.type} signal for call ${callId} from user ${fromUserId}`);

        switch (signal.type) {
            case 'offer':
                call.participants.forEach(participantId => {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'sdp_offer', {
                            callId,
                            userId: fromUserId,
                            offer: signal.offer
                        });
                    }
                });
                break;

            case 'answer':
                call.participants.forEach(participantId => {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'sdp_answer', {
                            callId,
                            userId: fromUserId,
                            answer: signal.answer
                        });
                    }
                });
                break;

            case 'ice-candidate':
                call.participants.forEach(participantId => {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'ice_candidate', {
                            callId,
                            userId: fromUserId,
                            candidate: signal.candidate
                        });
                    }
                });
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

// ===== API МАРШРУТЫ - АУТЕНТИФИКАЦИЯ =====
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
            return {
                ...userWithoutPassword,
                online: chatManager ? chatManager.isUserOnline(u.id) : false
            };
        });
        res.json(users);
    } catch (error) {
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

// ===== API МАРШРУТЫ - ЗАДАЧИ =====
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

// ===== API МАРШРУТЫ - ЧАТЫ =====
app.get('/api/chats', authMiddleware, async (req, res) => {
    try {
        const chats = await chatManager.getUserChats(req.user.id);
        res.json(chats);
    } catch (error) {
        console.error('Ошибка получения чатов:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.post('/api/chats', authMiddleware, async (req, res) => {
    try {
        const { type, name, members, taskId, taskTitle } = req.body;

        let chat;
        if (type === 'private') {
            // Создаём или получаем приватный чат
            const otherUserId = members[0];
            chat = await chatManager.getOrCreatePrivateChat(req.user.id, otherUserId);
        } else if (type === 'group') {
            // Создаём групповой чат
            chat = await chatManager.createGroupChat(name, req.user.id, members);
        } else if (type === 'task') {
            // Создаём чат для задачи
            chat = await chatManager.createTaskChat(taskId, taskTitle, members);
        } else {
            return res.status(400).json({ error: 'Неверный тип чата' });
        }

        res.json(chat);
    } catch (error) {
        console.error('Ошибка создания чата:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.get('/api/chats/:chatId/messages', authMiddleware, async (req, res) => {
    try {
        const { chatId } = req.params;
        const { limit = 50, before } = req.query;

        const messages = await chatManager.getMessages(chatId, parseInt(limit), before);
        res.json(messages);
    } catch (error) {
        console.error('Ошибка получения сообщений:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

app.post('/api/chats/:chatId/messages', authMiddleware, async (req, res) => {
    try {
        const { chatId } = req.params;
        const { content, type = 'text', attachments = [] } = req.body;

        if (!content || content.trim() === '') {
            return res.status(400).json({ error: 'Сообщение не может быть пустым' });
        }

        const message = await chatManager.sendMessage(
            chatId,
            req.user.id,
            content,
            type,
            attachments
        );

        res.json({ message });
    } catch (error) {
        console.error('Ошибка отправки сообщения:', error);
        res.status(500).json({ error: error.message || 'Ошибка сервера' });
    }
});

app.post('/api/chats/:chatId/read', authMiddleware, async (req, res) => {
    try {
        const { chatId } = req.params;
        await chatManager.markAsRead(chatId, req.user.id);
        res.json({ success: true });
    } catch (error) {
        console.error('Ошибка отметки как прочитанное:', error);
        res.status(500).json({ error: 'Ошибка сервера' });
    }
});

// ===== API МАРШРУТЫ - ЗВОНКИ =====
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

// ===== API МАРШРУТЫ - PUSH УВЕДОМЛЕНИЯ =====
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

// ===== WEBSOCKET ОБРАБОТКА =====
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
                
                // Уведомляем пользователя об успешной аутентификации
                socket.emit('authenticated', { success: true, user });
                
                // Уведомляем других пользователей об онлайн статусе
                io.emit('user_status_changed', {
                    userId: user.id,
                    status: 'online'
                });
                
                console.log(`User ${user.name} (${user.id}) authenticated`);
            } else {
                socket.emit('authenticated', { success: false, error: 'User not found' });
            }
        } catch (error) {
            console.error('Authentication error:', error);
            socket.emit('authenticated', { success: false, error: 'Invalid token' });
        }
    });

    // ===== ОБРАБОТЧИКИ СООБЩЕНИЙ =====
    socket.on('send_message', async ({ chatId, content, type, attachments }) => {
        if (!socket.userId) {
            socket.emit('error', { message: 'Не авторизован' });
            return;
        }

        try {
            await chatManager.sendMessage(socket.userId, chatId, content, type, attachments);
        } catch (error) {
            console.error('Error sending message:', error);
            socket.emit('error', { message: error.message });
        }
    });

    socket.on('typing_start', ({ chatId }) => {
        if (!socket.userId) return;
        chatManager.setTyping(chatId, socket.userId, true);
    });

    socket.on('typing_stop', ({ chatId }) => {
        if (!socket.userId) return;
        chatManager.setTyping(chatId, socket.userId, false);
    });

    socket.on('mark_as_read', async ({ chatId }) => {
        if (!socket.userId) return;
        
        try {
            await chatManager.markAsRead(chatId, socket.userId);
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    });

    // ===== ОБРАБОТЧИКИ ЗВОНКОВ =====
    socket.on('call_start', async ({ chatId, type }) => {
        if (!socket.userId) return;

        try {
            const call = await chatManager.startCall(chatId, socket.userId, type);
            socket.emit('call_started', call);
        } catch (error) {
            console.error('Error starting call:', error);
            socket.emit('call_error', { error: error.message });
        }
    });

    socket.on('call_accept', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            const call = await chatManager.acceptCall(callId, socket.userId);
            socket.emit('call_joined', call);
        } catch (error) {
            console.error('Error accepting call:', error);
            socket.emit('call_error', { error: error.message });
        }
    });

    socket.on('call_decline', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            await chatManager.declineCall(callId, socket.userId);
        } catch (error) {
            console.error('Error declining call:', error);
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

    // ===== WEBRTC СИГНАЛИНГ =====
    socket.on('call_signal', async ({ callId, signal }) => {
        if (!socket.userId) return;

        try {
            await chatManager.handleSignaling(callId, socket.userId, signal);
        } catch (error) {
            console.error('Error handling signaling:', error);
        }
    });

    // ===== ОТКЛЮЧЕНИЕ =====
    socket.on('disconnect', async () => {
        if (socket.userId) {
            chatManager.removeUserSocket(socket.userId, socket.id);
            
            // Если у пользователя больше нет активных соединений
            if (!chatManager.isUserOnline(socket.userId)) {
                await db.updateUserStatus(socket.userId, 'offline');
                
                // Уведомляем других пользователей об оффлайн статусе
                io.emit('user_status_changed', {
                    userId: socket.userId,
                    status: 'offline'
                });
            }
            
            console.log(`User ${socket.userId} disconnected`);
        }
    });
});

// ===== ЗАПУСК СЕРВЕРА =====
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
            console.log(`💬 Чатов: ${db.data.chats.length}`);
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