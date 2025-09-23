// server.js - Основной файл сервера с полной интеграцией чата
const express = require('express');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const multer = require('multer');
const nodemailer = require('nodemailer');
const TelegramBot = require('node-telegram-bot-api');
const { Server } = require('socket.io');
const http = require('http');
const path = require('path');
const fs = require('fs').promises;
const crypto = require('crypto');

const ChatManager = require('./chat');

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
    adminEmail: process.env.ADMIN_EMAIL || 'admin@example.com'
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
app.use(express.json());
app.use(express.static('public'));
app.use('/uploads', express.static('uploads'));

// HTML маршруты (должны быть перед API маршрутами)
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'tasks.html'));
});

app.get('/login', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

app.get('/tasks', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'tasks.html'));
});

app.get('/task/new', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'task-new.html'));
});

app.get('/task/:id', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'task.html'));
});

app.get('/messenger', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'messenger.html'));
});

app.get('/chat', (req, res) => {
    res.redirect('/messenger');
});

// База данных
class Database {
    constructor() {
        this.data = {
            users: [],
            tasks: [],
            comments: [],
            files: [],
            notifications: [],
            chats: [],
            chatMessages: [],
            chatMembers: [],
            calls: []
        };
        this.load();
    }

    async load() {
        try {
            const data = await fs.readFile(config.dbPath, 'utf8');
            this.data = JSON.parse(data);
        } catch (error) {
            await this.save();
        }
    }

    async save() {
        const dir = path.dirname(config.dbPath);
        await fs.mkdir(dir, { recursive: true });
        await fs.writeFile(config.dbPath, JSON.stringify(this.data, null, 2));
    }

    // User methods
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
            createdAt: new Date().toISOString()
        };
        this.data.users.push(user);
        await this.save();
        return user;
    }

    async updateUserStatus(userId, status) {
        const user = await this.findUserById(userId);
        if (user) {
            user.status = status;
            if (status === 'offline') {
                user.lastSeen = new Date().toISOString();
            }
            await this.save();
        }
        return user;
    }

    // Task methods
    async createTask(taskData) {
        const task = {
            id: Date.now(),
            ...taskData,
            assignees: taskData.assignees || [],
            watchers: taskData.watchers || [],
            commentsCount: 0,
            filesCount: 0,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };
        this.data.tasks.push(task);
        await this.save();
        return task;
    }

    async updateTask(id, updates) {
        const index = this.data.tasks.findIndex(t => t.id === id);
        if (index !== -1) {
            // Если обновляются исполнители или наблюдатели, убедимся что это массивы
            if (updates.assignees !== undefined && !Array.isArray(updates.assignees)) {
                updates.assignees = [];
            }
            if (updates.watchers !== undefined && !Array.isArray(updates.watchers)) {
                updates.watchers = [];
            }
            
            this.data.tasks[index] = {
                ...this.data.tasks[index],
                ...updates,
                updatedAt: new Date().toISOString()
            };
            await this.save();
            return this.data.tasks[index];
        }
        return null;
    }

    async getTasks(userId, role) {
        // Добавляем дополнительную информацию к каждой задаче
        const tasksWithCounts = this.data.tasks.map(task => {
            // Подсчитываем комментарии
            const commentsCount = this.data.comments ? 
                this.data.comments.filter(c => c.taskId === task.id).length : 0;
            
            // Подсчитываем файлы
            const filesCount = task.files ? task.files.length : 0;
            
            return {
                ...task,
                commentsCount,
                filesCount
            };
        });

        if (role === 'admin') {
            return tasksWithCounts;
        }
        
        return tasksWithCounts.filter(t => 
            t.creatorId === userId || 
            (t.assignees && t.assignees.includes(userId)) ||
            (t.watchers && t.watchers.includes(userId))
        );
    }

    async getTask(id) {
        return this.data.tasks.find(t => t.id === id);
    }

    async deleteTask(id) {
        const index = this.data.tasks.findIndex(t => t.id === id);
        if (index !== -1) {
            this.data.tasks.splice(index, 1);
            await this.save();
            return true;
        }
        return false;
    }

    // File methods
    async addFile(fileData) {
        const file = {
            id: Date.now(),
            ...fileData,
            uploadedAt: new Date().toISOString()
        };
        this.data.files.push(file);
        await this.save();
        return file;
    }

    async addTaskFile(taskId, fileId) {
        const task = this.data.tasks.find(t => t.id === taskId);
        if (task) {
            if (!task.files) task.files = [];
            if (!task.files.includes(fileId)) task.files.push(fileId);
            await this.save();
            return true;
        }
        return false;
    }

    async getTaskFiles(taskId) {
        const task = this.data.tasks.find(t => t.id === taskId);
        if (!task || !task.files) return [];
        return this.data.files.filter(f => task.files.includes(f.id));
    }

    // Legacy chat methods for task chat compatibility
    async addChatMessage(taskId, userId, userName, text, files = []) {
        const message = {
            id: Date.now(),
            taskId,
            userId,
            userName,
            text,
            files,
            createdAt: new Date().toISOString()
        };
        if (!this.data.chats) this.data.chats = [];
        this.data.chats.push(message);
        await this.save();
        return message;
    }

    async getChatMessages(taskId) {
        if (!this.data.chats) return [];
        return this.data.chats.filter(m => m.taskId === taskId);
    }

    // Comment methods
    async addComment(commentData) {
        const comment = {
            id: Date.now(),
            ...commentData,
            createdAt: new Date().toISOString()
        };
        this.data.comments.push(comment);
        await this.save();
        return comment;
    }

    async getComments(taskId) {
        return this.data.comments.filter(c => c.taskId === taskId);
    }
}

// Инициализация базы данных
const db = new Database();

// Инициализация менеджера чата
const chatManager = new ChatManager(db, io);

// Настройка загрузки файлов
const storage = multer.diskStorage({
    destination: async (req, file, cb) => {
        const uploadPath = path.join(config.uploadsDir, req.user.id.toString());
        await fs.mkdir(uploadPath, { recursive: true });
        cb(null, uploadPath);
    },
    filename: (req, file, cb) => {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        cb(null, uniqueSuffix + path.extname(file.originalname));
    }
});

const upload = multer({ 
    storage,
    limits: { fileSize: 10 * 1024 * 1024 }, // 10MB
    fileFilter: (req, file, cb) => {
        const allowedTypes = /jpeg|jpg|png|gif|pdf|doc|docx|xls|xlsx|zip|rar/;
        const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
        const mimetype = allowedTypes.test(file.mimetype);
        if (mimetype && extname) {
            return cb(null, true);
        } else {
            cb(new Error('Invalid file type'));
        }
    }
});

// Email транспорт
const emailTransporter = nodemailer.createTransport({
    host: config.email.host,
    port: config.email.port,
    secure: false,
    auth: {
        user: config.email.user,
        pass: config.email.pass
    }
});

// Middleware для аутентификации
const authMiddleware = async (req, res, next) => {
    try {
        const token = req.headers.authorization?.split(' ')[1];
        if (!token) {
            return res.status(401).json({ error: 'Не авторизован' });
        }
        const decoded = jwt.verify(token, config.jwtSecret);
        const user = await db.findUserById(decoded.id);
        if (!user) {
            return res.status(401).json({ error: 'Пользователь не найден' });
        }
        req.user = user;
        next();
    } catch (error) {
        res.status(401).json({ error: 'Неверный токен' });
    }
};

// Middleware для проверки админа
const adminMiddleware = (req, res, next) => {
    if (req.user.role !== 'admin') {
        return res.status(403).json({ error: 'Доступ запрещен' });
    }
    next();
};

// ====================== API ROUTES ======================

// Аутентификация
app.post('/api/auth/register', async (req, res) => {
    try {
        const { email, password, name } = req.body;
        
        const existingUser = await db.findUserByEmail(email);
        if (existingUser) {
            return res.status(400).json({ error: 'Пользователь уже существует' });
        }

        const hashedPassword = await bcrypt.hash(password, 10);
        const user = await db.createUser({
            email,
            password: hashedPassword,
            name,
            role: email === config.adminEmail ? 'admin' : 'user',
            avatar: null,
            status: 'offline'
        });

        const token = jwt.sign({ id: user.id }, config.jwtSecret, { expiresIn: '7d' });
        
        const { password: _, ...userWithoutPassword } = user;
        res.json({ user: userWithoutPassword, token });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.post('/api/auth/login', async (req, res) => {
    try {
        const { email, password } = req.body;
        
        const user = await db.findUserByEmail(email);
        if (!user) {
            return res.status(401).json({ error: 'Неверный email или пароль' });
        }

        const isValid = await bcrypt.compare(password, user.password);
        if (!isValid) {
            return res.status(401).json({ error: 'Неверный email или пароль' });
        }

        await db.updateUserStatus(user.id, 'online');
        
        const token = jwt.sign({ id: user.id }, config.jwtSecret, { expiresIn: '7d' });
        
        const { password: _, ...userWithoutPassword } = user;
        res.json({ user: userWithoutPassword, token });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Профиль пользователя
app.get('/api/profile', authMiddleware, async (req, res) => {
    const { password, ...userWithoutPassword } = req.user;
    res.json(userWithoutPassword);
});

app.put('/api/profile', authMiddleware, upload.single('avatar'), async (req, res) => {
    try {
        const updates = req.body;
        
        if (req.file) {
            updates.avatar = `/uploads/${req.user.id}/${req.file.filename}`;
        }

        Object.assign(req.user, updates);
        await db.save();
        
        const { password, ...userWithoutPassword } = req.user;
        res.json(userWithoutPassword);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Задачи
app.get('/api/tasks', authMiddleware, async (req, res) => {
    try {
        const tasks = await db.getTasks(req.user.id, req.user.role);
        res.json(tasks);
    } catch (error) {
        res.status(400).json({ error: error.message });
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
        res.status(400).json({ error: error.message });
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
        res.status(400).json({ error: error.message });
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
        res.status(400).json({ error: error.message });
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
        res.status(400).json({ error: error.message });
    }
});

// Чат задачи (legacy support + new implementation)
app.get('/api/tasks/:id/chat', authMiddleware, async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        const task = await db.getTask(taskId);
        
        if (!task) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }

        // Проверяем доступ к задаче
        const hasAccess = req.user.role === 'admin' || 
                         task.creatorId === req.user.id ||
                         (task.assignees && task.assignees.includes(req.user.id)) ||
                         (task.watchers && task.watchers.includes(req.user.id));

        if (!hasAccess) {
            return res.status(403).json({ error: 'Нет доступа к чату задачи' });
        }

        // Получаем сообщения чата задачи
        const messages = await db.getChatMessages(taskId);
        res.json(messages);
    } catch (error) {
        console.error('Error in /api/tasks/:id/chat:', error);
        res.status(400).json({ error: error.message });
    }
});

app.post('/api/tasks/:id/chat', authMiddleware, upload.array('files', 5), async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        const task = await db.getTask(taskId);
        
        if (!task) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }

        // Проверяем доступ
        const hasAccess = req.user.role === 'admin' || 
                         task.creatorId === req.user.id ||
                         (task.assignees && task.assignees.includes(req.user.id)) ||
                         (task.watchers && task.watchers.includes(req.user.id));

        if (!hasAccess) {
            return res.status(403).json({ error: 'Нет доступа к чату задачи' });
        }

        // Обрабатываем файлы
        const files = [];
        if (req.files && req.files.length > 0) {
            for (const file of req.files) {
                const fileData = await db.addFile({
                    originalName: file.originalname,
                    filename: file.filename,
                    path: file.path,
                    size: file.size,
                    mimetype: file.mimetype,
                    uploaderId: req.user.id
                });
                files.push(fileData.id);
            }
        }

        // Создаем сообщение
        const message = await db.addChatMessage(
            taskId,
            req.user.id,
            req.user.name,
            req.body.text || '',
            files
        );

        // Отправляем через WebSocket
        io.to(`task-chat-${taskId}`).emit('chat_message', message);
        
        res.json(message);
    } catch (error) {
        console.error('Error in POST /api/tasks/:id/chat:', error);
        res.status(400).json({ error: error.message });
    }
});

// Файлы задачи
app.get('/api/tasks/:id/files', authMiddleware, async (req, res) => {
    try {
        const files = await db.getTaskFiles(parseInt(req.params.id));
        res.json(files);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.post('/api/tasks/:id/files', authMiddleware, upload.single('file'), async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        
        if (!req.file) {
            return res.status(400).json({ error: 'Файл не загружен' });
        }
        
        const fileData = await db.addFile({
            originalName: req.file.originalname,
            filename: req.file.filename,
            path: req.file.path,
            size: req.file.size,
            mimetype: req.file.mimetype,
            uploaderId: req.user.id
        });
        
        await db.addTaskFile(taskId, fileData.id);
        res.json(fileData);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Комментарии
app.get('/api/tasks/:id/comments', authMiddleware, async (req, res) => {
    try {
        const comments = await db.getComments(parseInt(req.params.id));
        res.json(comments);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.post('/api/tasks/:id/comments', authMiddleware, async (req, res) => {
    try {
        const comment = await db.addComment({
            taskId: parseInt(req.params.id),
            userId: req.user.id,
            userName: req.user.name,
            text: req.body.text
        });
        
        io.to(`task-${req.params.id}`).emit('new_comment', comment);
        res.json(comment);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Пользователи
app.get('/api/users', authMiddleware, async (req, res) => {
    try {
        const users = db.data.users.map(u => {
            const { password, ...userWithoutPassword } = u;
            return userWithoutPassword;
        });
        res.json(users);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// ====================== CHAT API ======================

// Получить список чатов пользователя
app.get('/api/chats', authMiddleware, async (req, res) => {
    try {
        const chats = await chatManager.getUserChats(req.user.id);
        res.json(chats);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Создать новый чат
app.post('/api/chats', authMiddleware, async (req, res) => {
    try {
        const { type, name, members, avatar } = req.body;
        
        let chat;
        if (type === 'private' && members.length === 1) {
            chat = await chatManager.createPrivateChat(req.user.id, members[0]);
        } else if (type === 'group') {
            chat = await chatManager.createGroupChat(
                name,
                [...new Set([req.user.id, ...members])],
                req.user.id,
                avatar
            );
        } else if (type === 'task' && req.body.taskId) {
            chat = await chatManager.createTaskChat(
                req.body.taskId,
                [...new Set([req.user.id, ...members])],
                req.user.id
            );
        } else {
            return res.status(400).json({ error: 'Неверный тип чата' });
        }
        
        res.json(chat);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Получить информацию о чате
app.get('/api/chats/:id', authMiddleware, async (req, res) => {
    try {
        const chat = await chatManager.getChatById(req.params.id, req.user.id);
        if (!chat) {
            return res.status(404).json({ error: 'Чат не найден' });
        }
        res.json(chat);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Получить сообщения чата
app.get('/api/chats/:id/messages', authMiddleware, async (req, res) => {
    try {
        const { limit = 50, before } = req.query;
        const messages = await chatManager.getChatMessages(
            req.params.id,
            req.user.id,
            parseInt(limit),
            before
        );
        res.json(messages);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Отправить сообщение в чат
app.post('/api/chats/:id/messages', authMiddleware, upload.array('attachments', 5), async (req, res) => {
    try {
        const attachments = [];
        if (req.files && req.files.length > 0) {
            for (const file of req.files) {
                const fileData = await db.addFile({
                    originalName: file.originalname,
                    filename: file.filename,
                    path: file.path,
                    size: file.size,
                    mimetype: file.mimetype,
                    uploaderId: req.user.id
                });
                attachments.push(fileData);
            }
        }
        
        const message = await chatManager.sendMessage({
            chatId: req.params.id,
            senderId: req.user.id,
            senderName: req.user.name,
            text: req.body.text,
            attachments,
            replyTo: req.body.replyTo
        });
        
        res.json(message);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Редактировать сообщение
app.put('/api/messages/:id', authMiddleware, async (req, res) => {
    try {
        const message = await chatManager.editMessage(
            req.params.id,
            req.user.id,
            req.body.text
        );
        if (!message) {
            return res.status(404).json({ error: 'Сообщение не найдено' });
        }
        res.json(message);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Удалить сообщение
app.delete('/api/messages/:id', authMiddleware, async (req, res) => {
    try {
        const success = await chatManager.deleteMessage(req.params.id, req.user.id);
        if (!success) {
            return res.status(404).json({ error: 'Сообщение не найдено' });
        }
        res.json({ success: true });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Отметить сообщения как прочитанные
app.post('/api/chats/:id/read', authMiddleware, async (req, res) => {
    try {
        await chatManager.markMessagesAsRead(
            req.params.id,
            req.user.id,
            req.body.messageIds
        );
        res.json({ success: true });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Поиск по сообщениям
app.get('/api/chats/search', authMiddleware, async (req, res) => {
    try {
        const { query, chatId } = req.query;
        const results = await chatManager.searchMessages(
            req.user.id,
            query,
            chatId
        );
        res.json(results);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// ====================== WEBSOCKET ======================

io.on('connection', (socket) => {
    console.log('New WebSocket connection');

    socket.on('authenticate', async (token) => {
        try {
            const decoded = jwt.verify(token, config.jwtSecret);
            const user = await db.findUserById(decoded.id);
            
            if (user) {
                socket.userId = user.id;
                socket.join(`user-${user.id}`);
                
                // Добавляем сокет в менеджер чата
                chatManager.addUserSocket(user.id, socket.id);
                
                // Обновляем статус пользователя
                await db.updateUserStatus(user.id, 'online');
                
                // Присоединяем к чатам пользователя
                const userChats = await chatManager.getUserChats(user.id);
                for (const chat of userChats) {
                    socket.join(`chat-${chat.id}`);
                }
                
                socket.emit('authenticated', { 
                    success: true,
                    user: { id: user.id, name: user.name }
                });
            }
        } catch (error) {
            socket.emit('authenticated', { success: false });
        }
    });

    // Присоединение к чату задачи
    socket.on('join_task_chat', (taskId) => {
        if (socket.userId) {
            socket.join(`task-chat-${taskId}`);
            console.log(`User ${socket.userId} joined task chat ${taskId}`);
        }
    });

    // Покидание чата задачи
    socket.on('leave_task_chat', (taskId) => {
        socket.leave(`task-chat-${taskId}`);
    });

    // Присоединение к чату
    socket.on('join_chat', (chatId) => {
        if (socket.userId) {
            socket.join(`chat-${chatId}`);
        }
    });

    // Покидание чата
    socket.on('leave_chat', (chatId) => {
        socket.leave(`chat-${chatId}`);
    });

    // Индикатор набора текста
    socket.on('typing_start', ({ chatId }) => {
        if (socket.userId) {
            chatManager.typingStarted(chatId, socket.userId);
        }
    });

    socket.on('typing_stop', ({ chatId }) => {
        if (socket.userId) {
            chatManager.typingStopped(chatId, socket.userId);
        }
    });

    // Видеозвонки
    socket.on('call_start', async ({ chatId, type }) => {
        if (socket.userId) {
            const call = await chatManager.startCall(chatId, socket.userId, type);
            socket.emit('call_started', call);
        }
    });

    socket.on('call_join', async ({ callId }) => {
        if (socket.userId) {
            await chatManager.joinCall(callId, socket.userId);
        }
    });

    socket.on('call_leave', async ({ callId }) => {
        if (socket.userId) {
            await chatManager.leaveCall(callId, socket.userId);
        }
    });

    socket.on('call_end', async ({ callId }) => {
        if (socket.userId) {
            await chatManager.endCall(callId, socket.userId);
        }
    });

    socket.on('call_signal', async ({ callId, signal }) => {
        if (socket.userId) {
            await chatManager.handleCallSignal(callId, socket.userId, signal);
        }
    });

    // Отключение
    socket.on('disconnect', async () => {
        if (socket.userId) {
            // Удаляем сокет из менеджера чата
            chatManager.removeUserSocket(socket.userId, socket.id);
            
            // Если это был последний сокет пользователя, обновляем статус
            if (!chatManager.isUserOnline(socket.userId)) {
                await db.updateUserStatus(socket.userId, 'offline');
            }
        }
        console.log('WebSocket disconnected');
    });
});

// Telegram Bot Setup
function setupTelegramBot() {
    if (!telegramBot) return;

    telegramBot.onText(/\/start(.*)/, async (msg, match) => {
        const chatId = msg.chat.id;
        const token = match[1].trim();

        if (token) {
            const user = db.data.users.find(u => u.telegramToken === token);
            if (user) {
                user.telegramChatId = chatId;
                user.telegramToken = null;
                await db.save();
                
                telegramBot.sendMessage(chatId, 
                    `✅ Аккаунт успешно привязан!\nВы будете получать уведомления о задачах.`
                );
            } else {
                telegramBot.sendMessage(chatId, 
                    `❌ Неверный токен. Получите новый токен в приложении.`
                );
            }
        } else {
            telegramBot.sendMessage(chatId, 
                `Добро пожаловать! Для привязки аккаунта используйте токен из приложения.`
            );
        }
    });

    telegramBot.onText(/\/tasks/, async (msg) => {
        const chatId = msg.chat.id;
        const user = db.data.users.find(u => u.telegramChatId === chatId);
        
        if (!user) {
            telegramBot.sendMessage(chatId, 
                `❌ Сначала привяжите аккаунт командой /start`
            );
            return;
        }

        const tasks = await db.getTasks(user.id, user.role);
        const activeTasks = tasks.filter(t => t.status !== 'done');

        if (activeTasks.length === 0) {
            telegramBot.sendMessage(chatId, `У вас нет активных задач`);
        } else {
            let message = `📋 Ваши активные задачи:\n\n`;
            activeTasks.forEach(task => {
                message += `${task.priority === 'high' ? '🔴' : task.priority === 'medium' ? '🟡' : '🟢'} `;
                message += `*${task.title}*\n`;
                message += `Статус: ${task.status}\n`;
                message += `Срок: ${task.dueDate || 'Не указан'}\n\n`;
            });
            telegramBot.sendMessage(chatId, message, { parse_mode: 'Markdown' });
        }
    });
}

// Notification Service
class NotificationService {
    async notify(userId, type, data) {
        const user = await db.findUserById(userId);
        if (!user) return;

        // Email уведомление
        if (user.emailNotifications !== false) {
            await this.sendEmail(user.email, type, data);
        }

        // Telegram уведомление
        if (user.telegramChatId && telegramBot) {
            await this.sendTelegram(user.telegramChatId, type, data);
        }

        // WebSocket уведомление
        io.to(`user-${userId}`).emit('notification', {
            type,
            data,
            timestamp: new Date().toISOString()
        });

        // Сохраняем в БД
        await this.saveNotification(userId, type, data);
    }

    async notifyMultiple(userIds, type, data) {
        for (const userId of userIds) {
            await this.notify(userId, type, data);
        }
    }

    async sendEmail(email, type, data) {
        const subjects = {
            task_assigned: 'Вам назначена новая задача',
            task_updated: 'Задача обновлена',
            task_comment: 'Новый комментарий к задаче',
            task_due: 'Приближается срок задачи'
        };

        const subject = subjects[type] || 'Уведомление';
        let html = `<h2>${subject}</h2>`;

        if (data.task) {
            html += `
                <p><strong>Задача:</strong> ${data.task.title}</p>
                <p><strong>Описание:</strong> ${data.task.description || 'Не указано'}</p>
                <p><strong>Приоритет:</strong> ${data.task.priority}</p>
                <p><strong>Срок:</strong> ${data.task.dueDate || 'Не указан'}</p>
            `;
        }

        try {
            await emailTransporter.sendMail({
                from: config.email.user,
                to: email,
                subject,
                html
            });
        } catch (error) {
            console.error('Email send error:', error);
        }
    }

    async sendTelegram(chatId, type, data) {
        const messages = {
            task_assigned: `📌 Вам назначена новая задача: *${data.task.title}*`,
            task_updated: `📝 Задача обновлена: *${data.task.title}*`,
            task_comment: `💬 Новый комментарий к задаче: *${data.task.title}*`,
            task_due: `⏰ Приближается срок задачи: *${data.task.title}*`
        };

        const message = messages[type];
        if (message) {
            try {
                await telegramBot.sendMessage(chatId, message, { parse_mode: 'Markdown' });
            } catch (error) {
                console.error('Telegram send error:', error);
            }
        }
    }

    async saveNotification(userId, type, data) {
        const notification = {
            id: Date.now(),
            userId,
            type,
            data,
            read: false,
            createdAt: new Date().toISOString()
        };

        db.data.notifications.push(notification);
        await db.save();
        
        return notification;
    }
}

const notificationService = new NotificationService();

// Admin endpoints
app.get('/api/admin/stats', authMiddleware, adminMiddleware, async (req, res) => {
    try {
        const stats = {
            totalUsers: db.data.users.length,
            onlineUsers: db.data.users.filter(u => u.status === 'online').length,
            totalTasks: db.data.tasks.length,
            activeTasks: db.data.tasks.filter(t => t.status !== 'done').length,
            completedTasks: db.data.tasks.filter(t => t.status === 'done').length,
            totalComments: db.data.comments.length,
            totalFiles: db.data.files.length,
            totalChats: db.data.chats.length,
            totalMessages: db.data.chatMessages.length,
            telegramUsers: db.data.users.filter(u => u.telegramChatId).length
        };
        res.json(stats);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.get('/api/admin/users', authMiddleware, adminMiddleware, async (req, res) => {
    try {
        const users = db.data.users.map(u => {
            const { password, ...userWithoutPassword } = u;
            return userWithoutPassword;
        });
        res.json(users);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.put('/api/admin/users/:id', authMiddleware, adminMiddleware, async (req, res) => {
    try {
        const user = await db.findUserById(parseInt(req.params.id));
        
        if (!user) {
            return res.status(404).json({ error: 'Пользователь не найден' });
        }

        Object.assign(user, req.body);
        await db.save();
        
        const { password, ...userWithoutPassword } = user;
        res.json(userWithoutPassword);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Запуск сервера
server.listen(config.port, () => {
    console.log(`Server running on port ${config.port}`);
    console.log(`Admin email: ${config.adminEmail}`);
    if (config.telegram.enabled) {
        console.log('Telegram bot enabled');
    }
});