// server.js - Основной файл сервера
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
        methods: ["GET", "POST"]
    }
});

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));
app.use('/uploads', express.static('uploads'));

// Инициализация Telegram бота
let telegramBot = null;
if (config.telegram.enabled) {
    telegramBot = new TelegramBot(config.telegram.botToken, { polling: true });
    setupTelegramBot();
}

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
        // Разрешенные типы файлов
        const allowedTypes = /jpeg|jpg|png|gif|pdf|doc|docx|xls|xlsx|txt|zip|rar/;
        const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
        const mimetype = allowedTypes.test(file.mimetype);
        
        if (mimetype && extname) {
            return cb(null, true);
        } else {
            cb(new Error('Неподдерживаемый тип файла'));
        }
    }
});

// База данных (простая JSON для демо, в продакшене используйте PostgreSQL/MongoDB)
class Database {
    constructor() {
        this.data = {
            users: [],
            tasks: [],
            comments: [],
            files: [],
            notifications: [],
            telegramLinks: [],
             // Новые коллекции для чата
            chats: [],
            chatMessages: [],
            chatMembers: [],
            calls: []
        };
        this.init();
    }

   async init() {
    try {
        const data = await fs.readFile(config.dbPath, 'utf8');
        this.data = JSON.parse(data);

        // Гарантируем, что все коллекции существуют:
        if (!this.data.users) this.data.users = [];
        if (!this.data.tasks) this.data.tasks = [];
        if (!this.data.comments) this.data.comments = [];
        if (!this.data.files) this.data.files = [];
        if (!this.data.notifications) this.data.notifications = [];
        if (!this.data.telegramLinks) this.data.telegramLinks = [];
        if (!this.data.chats) this.data.chats = [];
    } catch (error) {
        await this.save();
    }
}

    async save() {
        await fs.mkdir(path.dirname(config.dbPath), { recursive: true });
        await fs.writeFile(config.dbPath, JSON.stringify(this.data, null, 2));
    }

    // Методы для работы с пользователями
    async createUser(userData) {
        const user = {
            id: Date.now(),
            ...userData,
            createdAt: new Date().toISOString(),
            role: userData.role || 'user',
            emailNotifications: true,
            telegramNotifications: false,
            telegramChatId: null,
            avatar: userData.avatar || null,
            status: 'offline',
            lastSeen: new Date().toISOString()
        };
        this.data.users.push(user);
        await this.save();
        return user;
    }

     async addChatMessage(taskId, userId, userName, text, files = []) {
        const msg = {
            id: Date.now(),
            taskId,
            userId,
            userName,
            text,
            files,
            createdAt: new Date().toISOString()
        };
        this.data.chats.push(msg);
        await this.save();
        return msg;
    }
    async getChatMessages(taskId) {
        return this.data.chats.filter(m => m.taskId === taskId);
    }

    // --- Файлы задачи ---
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

    async findUserByEmail(email) {
        return this.data.users.find(u => u.email === email);
    }

    async findUserById(id) {
        return this.data.users.find(u => u.id === id);
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

    // Методы для работы с задачами
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
        if (index !== -1) {
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
        if (role === 'admin') {
            return this.data.tasks;
        }
        return this.data.tasks.filter(t => 
            t.creatorId === userId || 
            t.assignees.includes(userId) || 
            t.watchers.includes(userId)
        );
    }


    // Методы для комментариев
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

    // Методы для уведомлений
    async createNotification(notificationData) {
        const notification = {
            id: Date.now(),
            ...notificationData,
            createdAt: new Date().toISOString(),
            read: false
        };
        this.data.notifications.push(notification);
        await this.save();
        return notification;
    }

    async getNotifications(userId) {
        return this.data.notifications.filter(n => n.userId === userId);
    }

    async linkTelegram(userId, chatId) {
        const user = await this.findUserById(userId);
        if (user) {
            user.telegramChatId = chatId;
            user.telegramNotifications = true;
            await this.save();
            return true;
        }
        return false;
    }


    async getAllUsers() {
        return this.data.users.map(u => ({
            id: u.id,
            name: u.name,
            email: u.email,
            avatar: u.avatar,
            status: u.status,
            lastSeen: u.lastSeen
        }));
    }



}

const db = new Database();





// Email сервис
class EmailService {
    constructor() {
        this.transporter = nodemailer.createTransport({
            host: config.email.host,
            port: config.email.port,
            secure: false,
            auth: {
                user: config.email.user,
                pass: config.email.pass
            }
        });
    }

    async sendEmail(to, subject, html) {
        try {
            await this.transporter.sendMail({
                from: `"Task Management System" <${config.email.user}>`,
                to,
                subject,
                html
            });
            return true;
        } catch (error) {
            console.error('Email error:', error);
            return false;
        }
    }

    async sendTaskNotification(user, task, action) {
        const subject = `Задача "${task.title}" - ${action}`;
        const html = `
            <h2>Уведомление о задаче</h2>
            <p><strong>Задача:</strong> ${task.title}</p>
            <p><strong>Действие:</strong> ${action}</p>
            <p><strong>Описание:</strong> ${task.description || 'Нет описания'}</p>
            <p><strong>Приоритет:</strong> ${task.priority}</p>
            <p><strong>Статус:</strong> ${task.status}</p>
            <hr>
            <p><a href="${process.env.APP_URL || 'http://localhost:3000'}/task/${task.id}">Открыть задачу</a></p>
        `;
        return this.sendEmail(user.email, subject, html);
    }

    async sendCommentNotification(user, task, comment, author) {
        const subject = `Новый комментарий в задаче "${task.title}"`;
        const html = `
            <h2>Новый комментарий</h2>
            <p><strong>Задача:</strong> ${task.title}</p>
            <p><strong>Автор:</strong> ${author.name}</p>
            <p><strong>Комментарий:</strong></p>
            <blockquote>${comment.text}</blockquote>
            <hr>
            <p><a href="${process.env.APP_URL || 'http://localhost:3000'}/task/${task.id}#comment-${comment.id}">Перейти к комментарию</a></p>
        `;
        return this.sendEmail(user.email, subject, html);
    }
}

const emailService = new EmailService();

// Telegram бот
function setupTelegramBot() {
    if (!telegramBot) return;

    // Команда /start
    telegramBot.onText(/\/start (.+)/, async (msg, match) => {
        const chatId = msg.chat.id;
        const token = match[1];
        
        // Проверяем токен и связываем с пользователем
        const user = db.data.users.find(u => u.telegramToken === token);
        if (user) {
            await db.linkTelegram(user.id, chatId);
            telegramBot.sendMessage(chatId, 
                `✅ Аккаунт успешно привязан!\nТеперь вы будете получать уведомления о задачах.`
            );
        } else {
            telegramBot.sendMessage(chatId, 
                '❌ Неверный токен. Получите новый токен в настройках профиля.'
            );
        }
    });

    // Команда /tasks
    telegramBot.onText(/\/tasks/, async (msg) => {
        const chatId = msg.chat.id;
        const user = db.data.users.find(u => u.telegramChatId === chatId);
        
        if (user) {
            const tasks = await db.getTasks(user.id, user.role);
            const activeTasks = tasks.filter(t => t.status !== 'done');
            
            if (activeTasks.length === 0) {
                telegramBot.sendMessage(chatId, '📋 У вас нет активных задач');
            } else {
                let message = '📋 *Ваши активные задачи:*\n\n';
                activeTasks.forEach((task, index) => {
                    const priority = {
                        low: '🟢',
                        medium: '🟡',
                        high: '🟠',
                        urgent: '🔴'
                    }[task.priority];
                    
                    message += `${index + 1}. ${priority} *${task.title}*\n`;
                    message += `   Статус: ${task.status}\n`;
                    message += `   Срок: ${task.deadline || 'Не указан'}\n\n`;
                });
                
                telegramBot.sendMessage(chatId, message, { parse_mode: 'Markdown' });
            }
        } else {
            telegramBot.sendMessage(chatId, 
                '❌ Аккаунт не привязан. Используйте команду /start с токеном из настроек профиля.'
            );
        }
    });
}

// Notification сервис
class NotificationService {
    async notify(userId, type, data) {
        const user = await db.findUserById(userId);
        if (!user) return;

        // Создаем уведомление в БД
        await db.createNotification({
            userId,
            type,
            data,
            read: false
        });

        // Отправляем через WebSocket
        io.to(`user-${userId}`).emit('notification', {
            type,
            data,
            timestamp: new Date().toISOString()
        });

        // Email уведомление
        if (user.emailNotifications) {
            switch (type) {
                case 'task_assigned':
                    await emailService.sendTaskNotification(user, data.task, 'Вам назначена задача');
                    break;
                case 'task_updated':
                    await emailService.sendTaskNotification(user, data.task, 'Задача обновлена');
                    break;
                case 'new_comment':
                    await emailService.sendCommentNotification(user, data.task, data.comment, data.author);
                    break;
            }
        }

        // Telegram уведомление
        if (user.telegramNotifications && user.telegramChatId && telegramBot) {
            let message = '';
            switch (type) {
                case 'task_assigned':
                    message = `📌 *Новая задача:* ${data.task.title}\n\nПриоритет: ${data.task.priority}\nСрок: ${data.task.deadline || 'Не указан'}`;
                    break;
                case 'task_updated':
                    message = `📝 *Задача обновлена:* ${data.task.title}\n\nИзменения: ${data.changes}`;
                    break;
                case 'new_comment':
                    message = `💬 *Новый комментарий в задаче:* ${data.task.title}\n\n${data.author.name}: ${data.comment.text}`;
                    break;
            }
            
            if (message) {
                telegramBot.sendMessage(user.telegramChatId, message, { parse_mode: 'Markdown' });
            }
        }
    }

    async notifyMultiple(userIds, type, data) {
        for (const userId of userIds) {
            await this.notify(userId, type, data);
        }
    }
}

const notificationService = new NotificationService();

// Middleware для проверки токена
const authMiddleware = async (req, res, next) => {
    const token = req.header('Authorization')?.replace('Bearer ', '');
    
    if (!token) {
        return res.status(401).json({ error: 'Необходима авторизация' });
    }

    try {
        const decoded = jwt.verify(token, config.jwtSecret);
        const user = await db.findUserById(decoded.id);
        
        if (!user) {
            throw new Error();
        }

        req.user = user;
        req.token = token;
        next();
    } catch (error) {
        res.status(401).json({ error: 'Неверный токен' });
    }
};

// Middleware для проверки прав администратора
const adminMiddleware = (req, res, next) => {
    if (req.user.role !== 'admin') {
        return res.status(403).json({ error: 'Недостаточно прав' });
    }
    next();
};

// API Routes

// Регистрация
app.post('/api/auth/register', async (req, res) => {
    try {
        const { email, password, name } = req.body;

        // Проверка существующего пользователя
        const existingUser = await db.findUserByEmail(email);
        if (existingUser) {
            return res.status(400).json({ error: 'Пользователь с таким email уже существует' });
        }

        // Хеширование пароля
        const hashedPassword = await bcrypt.hash(password, 10);

        // Создание пользователя
        const user = await db.createUser({
            email,
            password: hashedPassword,
            name,
            role: email === config.adminEmail ? 'admin' : 'user'
        });

        // Генерация токена
        const token = jwt.sign({ id: user.id }, config.jwtSecret, { expiresIn: '7d' });

        // Отправка приветственного письма
        await emailService.sendEmail(
            email,
            'Добро пожаловать в Task Management System',
            `<h1>Добро пожаловать, ${name}!</h1>
            <p>Ваш аккаунт успешно создан.</p>
            <p>Теперь вы можете управлять задачами и получать уведомления.</p>`
        );

        res.json({
            token,
            user: {
                id: user.id,
                email: user.email,
                name: user.name,
                role: user.role
            }
        });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Авторизация
app.post('/api/auth/login', async (req, res) => {
    try {
        const { email, password } = req.body;

        const user = await db.findUserByEmail(email);
        if (!user) {
            return res.status(401).json({ error: 'Неверный email или пароль' });
        }

        const isPasswordValid = await bcrypt.compare(password, user.password);
        if (!isPasswordValid) {
            return res.status(401).json({ error: 'Неверный email или пароль' });
        }

        const token = jwt.sign({ id: user.id }, config.jwtSecret, { expiresIn: '7d' });

        res.json({
            token,
            user: {
                id: user.id,
                email: user.email,
                name: user.name,
                role: user.role
            }
        });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Получение профиля
app.get('/api/auth/profile', authMiddleware, async (req, res) => {
    const { password, ...userWithoutPassword } = req.user;
    res.json(userWithoutPassword);
});

// Обновление настроек уведомлений
app.put('/api/auth/notifications', authMiddleware, async (req, res) => {
    try {
        const { emailNotifications, telegramNotifications } = req.body;
        
        const user = await db.findUserById(req.user.id);
        user.emailNotifications = emailNotifications;
        user.telegramNotifications = telegramNotifications;
        
        await db.save();
        res.json({ success: true });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Генерация токена для Telegram
app.post('/api/auth/telegram-token', authMiddleware, async (req, res) => {
    try {
        const token = crypto.randomBytes(32).toString('hex');
        const user = await db.findUserById(req.user.id);
        user.telegramToken = token;
        await db.save();
        
        const botUsername = process.env.TELEGRAM_BOT_USERNAME || 'YourBotUsername';
        const link = `https://t.me/${botUsername}?start=${token}`;
        
        res.json({ token, link });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// CRUD для задач
app.get('/api/tasks', authMiddleware, async (req, res) => {
    try {
        const tasks = await db.getTasks(req.user.id, req.user.role);
        res.json(tasks);
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

        // Уведомляем назначенных
        if (task.assignees && task.assignees.length > 0) {
            await notificationService.notifyMultiple(
                task.assignees,
                'task_assigned',
                { task }
            );
        }

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

        // Уведомляем всех участников
        const participants = [...(task.assignees || []), ...(task.watchers || [])];
        await notificationService.notifyMultiple(
            participants,
            'task_updated',
            { 
                task,
                changes: Object.keys(req.body).join(', ')
            }
        );

        res.json(task);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.delete('/api/tasks/:id', authMiddleware, async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        const index = db.data.tasks.findIndex(t => t.id === taskId);
        
        if (index === -1) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }

        // Проверка прав
        const task = db.data.tasks[index];
        if (task.creatorId !== req.user.id && req.user.role !== 'admin') {
            return res.status(403).json({ error: 'Недостаточно прав' });
        }

        db.data.tasks.splice(index, 1);
        await db.save();
        
        res.json({ success: true });
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

app.post('/api/tasks/:id/comments', authMiddleware, upload.array('files', 5), async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        const task = db.data.tasks.find(t => t.id === taskId);
        
        if (!task) {
            return res.status(404).json({ error: 'Задача не найдена' });
        }

        // Сохраняем информацию о файлах
        const files = [];
        if (req.files) {
            for (const file of req.files) {
                const fileData = await db.addFile({
                    originalName: file.originalname,
                    filename: file.filename,
                    path: file.path,
                    size: file.size,
                    mimetype: file.mimetype,
                    uploaderId: req.user.id
                });
                files.push(fileData);
            }
        }

        // Создаем комментарий
        const comment = await db.addComment({
            taskId,
            userId: req.user.id,
            userName: req.user.name,
            text: req.body.text,
            files: files.map(f => f.id)
        });

        // Уведомляем участников
        const participants = [...(task.assignees || []), ...(task.watchers || []), task.creatorId]
            .filter(id => id !== req.user.id);
        
        await notificationService.notifyMultiple(
            participants,
            'new_comment',
            {
                task,
                comment,
                author: req.user
            }
        );

        res.json(comment);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Загрузка файлов
app.post('/api/upload', authMiddleware, upload.single('file'), async (req, res) => {
    try {
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

        res.json(fileData);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Получение файла
app.get('/api/files/:id', authMiddleware, async (req, res) => {
    try {
        const file = db.data.files.find(f => f.id === parseInt(req.params.id));
        
        if (!file) {
            return res.status(404).json({ error: 'Файл не найден' });
        }

        res.sendFile(path.resolve(file.path));
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Уведомления
app.get('/api/notifications', authMiddleware, async (req, res) => {
    try {
        const notifications = await db.getNotifications(req.user.id);
        res.json(notifications);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.put('/api/notifications/:id/read', authMiddleware, async (req, res) => {
    try {
        const notification = db.data.notifications.find(n => 
            n.id === parseInt(req.params.id) && n.userId === req.user.id
        );
        
        if (!notification) {
            return res.status(404).json({ error: 'Уведомление не найдено' });
        }

        notification.read = true;
        await db.save();
        
        res.json({ success: true });
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

// Админ панель
app.get('/api/admin/stats', authMiddleware, adminMiddleware, async (req, res) => {
    try {
        const stats = {
            totalUsers: db.data.users.length,
            totalTasks: db.data.tasks.length,
            activeTasks: db.data.tasks.filter(t => t.status !== 'done').length,
            completedTasks: db.data.tasks.filter(t => t.status === 'done').length,
            totalComments: db.data.comments.length,
            totalFiles: db.data.files.length,
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



app.get('/api/tasks/:id/chat', authMiddleware, async (req, res) => {
    try {
        const msgs = await db.getChatMessages(parseInt(req.params.id));
        res.json(msgs);
    } catch (e) {
        res.status(400).json({error: e.message});
    }
});
app.post('/api/tasks/:id/chat', authMiddleware, upload.array('files', 5), async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        const files = [];
        if (req.files) {
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
        const msg = await db.addChatMessage(
            taskId, req.user.id, req.user.name, req.body.text, files
        );
        io.to(`task-chat-${taskId}`).emit('chat_message', msg); // WebSocket
        res.json(msg);
    } catch (e) {
        res.status(400).json({error: e.message});
    }
});

// --- API для файлов задачи ---
app.get('/api/tasks/:id/files', authMiddleware, async (req, res) => {
    try {
        const files = await db.getTaskFiles(parseInt(req.params.id));
        res.json(files);
    } catch (e) {
        res.status(400).json({error: e.message});
    }
});
app.post('/api/tasks/:id/files', authMiddleware, upload.single('file'), async (req, res) => {
    try {
        const taskId = parseInt(req.params.id);
        if (!req.file) return res.status(400).json({error:'Файл не загружен'});
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
    } catch (e) {
        res.status(400).json({error: e.message});
    }
});


// WebSocket для real-time уведомлений
io.on('connection', (socket) => {
    console.log('New WebSocket connection');

    socket.on('authenticate', async (token) => {
        try {
            const decoded = jwt.verify(token, config.jwtSecret);
            const user = await db.findUserById(decoded.id);
            
            if (user) {
                socket.join(`user-${user.id}`);
                socket.userId = user.id;
                socket.emit('authenticated', { success: true });
            }
        } catch (error) {
            socket.emit('authenticated', { success: false });
        }

        
    });


    socket.on('join_task_chat', (taskId) => {
        socket.join(`task-chat-${taskId}`);
    });

    socket.on('disconnect', () => {
        console.log('WebSocket disconnected');
    });
});

// Запуск сервера
server.listen(config.port, () => {
    console.log(`Server running on port ${config.port}`);
    console.log(`Admin email: ${config.adminEmail}`);
    if (config.telegram.enabled) {
        console.log('Telegram bot enabled');
    }
});