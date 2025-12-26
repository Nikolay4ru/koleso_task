// ==================== Global Variables ====================
let socket = null;
let currentUser = null;
let currentChat = null;
let users = [];
let chats = [];
let onlineUsers = new Set();
let pendingChatUser = null;


// Делаем переменные доступными глобально для других модулей
window.currentChat = null;
window.pendingChatUser = null;

// ==================== Utility Functions ====================
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="material-icons">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : type === 'info' ? 'info' : 'warning'}</span>
        <span>${message}</span>
    `;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'toastSlideOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function formatTime(date) {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    
    if (diff < 60000) return 'только что';
    if (diff < 3600000) return `${Math.floor(diff / 60000)} мин назад`;
    if (d.toDateString() === now.toDateString()) {
        return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }
    return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

function getUserInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function generateGradient(str) {
    const colors = [
        ['#667eea', '#764ba2'],
        ['#f093fb', '#f5576c'],
        ['#4facfe', '#00f2fe'],
        ['#43e97b', '#38f9d7'],
        ['#fa709a', '#fee140'],
        ['#30cfd0', '#330867'],
        ['#a8edea', '#fed6e3'],
        ['#ff9a9e', '#fecfef']
    ];
    const index = str.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % colors.length;
    return `linear-gradient(135deg, ${colors[index][0]}, ${colors[index][1]})`;
}

// ==================== File Type Detection ====================
function getFileType(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    const videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
    const audioExts = ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
    const docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    
    if (imageExts.includes(ext)) return 'image';
    if (videoExts.includes(ext)) return 'video';
    if (audioExts.includes(ext)) return 'audio';
    if (docExts.includes(ext)) return 'document';
    return 'file';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
}

// ==================== API Functions ====================
async function apiCall(endpoint, options = {}) {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    
    const response = await fetch(endpoint, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
            ...options.headers
        }
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Request failed');
    }
    
    return response.json();
}

// ==================== Authentication ====================
async function login(username, password) {
    try {
        const data = await apiCall('/api/login', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        currentUser = data.user;
        
        console.log('✅ Logged in user:', currentUser); // Проверка что avatar есть
        
        initApp();
        showToast('Вход выполнен успешно');
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function register(name, email, username, password) {
    try {
        const data = await apiCall('/api/register', {
            method: 'POST',
            body: JSON.stringify({ name, email, username, password })
        });
        
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        currentUser = data.user;
        
        console.log('✅ Registered user:', currentUser); // Проверка что avatar есть
        
        initApp();
        showToast('Регистрация успешна');
    } catch (error) {
        showToast(error.message, 'error');
    }
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    if (socket) {
        socket.disconnect();
    }
    
    window.location.reload();
}

// ==================== Socket.IO ====================
function initSocket() {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    
    if (typeof io === 'undefined') {
        console.error('Socket.io not loaded!');
        showToast('Ошибка подключения к серверу', 'error');
        return;
    }
    
    try {
        socket = io({
            auth: { token },
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionDelay: 1000,
            reconnectionAttempts: 5
        });
        
        socket.on('connect', () => {
            console.log('Connected to server');
            loadChats();
            loadUsers();
            
            if (typeof setupWebRTCSocketHandlers === 'function') {
                setupWebRTCSocketHandlers();
            }
        });
        
        socket.on('connect_error', (error) => {
            console.error('Connection error:', error);
            showToast('Ошибка подключения к серверу', 'error');
        });
    } catch (error) {
        console.error('Error initializing socket:', error);
        showToast('Не удалось подключиться к серверу', 'error');
    }
    
    socket.on('disconnect', () => {
        console.log('Disconnected from server');
    });
    
    socket.on('users:online', (data) => {
        if (data.online) {
            onlineUsers.add(data.userId);
        } else {
            onlineUsers.delete(data.userId);
        }
        updateOnlineStatus();
    });
    
    socket.on('users:list', (userIds) => {
        onlineUsers = new Set(userIds);
        updateOnlineStatus();
    });


    // НОВОЕ: Обработчик обновления аватара
    socket.on('user:avatar-updated', (data) => {
        console.log('👤 User avatar updated:', data);
        
        // Обновляем в массиве users
        const user = users.find(u => u.id === data.userId);
        if (user) {
            user.avatar = data.avatar;
            console.log('✅ Updated avatar in users array for:', user.name);
        }
        
        // Обновляем currentUser если это мы
        if (currentUser && currentUser.id === data.userId) {
            currentUser.avatar = data.avatar;
            localStorage.setItem('user', JSON.stringify(currentUser));
            console.log('✅ Updated current user avatar');
        }
        
        // Обновляем все аватары в UI
        updateAllAvatarsInUI(data.userId, data.avatar);
        
        // Перерисовываем контакты и чаты
        renderContacts();
        renderChats();
    });
    
    // ИСПРАВЛЕНИЕ: Улучшенная обработка создания чата
    socket.on('chat:created', (chat) => {
        console.log('Chat created event received:', chat);
        
        // Проверяем валидность чата
        if (!chat || !chat.id) {
            console.warn('Invalid chat received:', chat);
            return;
        }
        
        // Убедимся, что у чата есть participants
        if (!chat.participants || !Array.isArray(chat.participants)) {
            console.warn('Chat missing participants:', chat);
            return;
        }
        
        const existingChatIndex = chats.findIndex(c => c.id === chat.id);
        
        if (existingChatIndex === -1) {
            console.log('Adding new chat to list:', chat.id);
            chats.push(chat);
        } else {
            console.log('Updating existing chat:', chat.id);
            chats[existingChatIndex] = chat;
        }
        
        renderChats();
        
        // Если это чат, который мы только что создали (currentChat уже установлен)
        // не открываем его повторно
        if (!currentChat || currentChat.id !== chat.id) {
            console.log('Chat created but not opening (already handled)');
        }
    });
    
    socket.on('message:new', (message) => {
        handleNewMessage(message);
    });
    
    const typingUsers = new Map();
    
    socket.on('typing:user', (data) => {
        if (currentChat && data.chatId === currentChat.id) {
            const indicator = document.getElementById('typingIndicator');
            const text = document.getElementById('typingText');
            
            if (!typingUsers.has(data.chatId)) {
                typingUsers.set(data.chatId, new Set());
            }
            
            const chatTypingUsers = typingUsers.get(data.chatId);
            
            if (data.typing) {
                chatTypingUsers.add(data.userId);
            } else {
                chatTypingUsers.delete(data.userId);
            }
            
            if (chatTypingUsers.size > 0) {
                const typingUsersList = Array.from(chatTypingUsers)
                    .map(userId => users.find(u => u.id === userId))
                    .filter(u => u);
                
                let typingText = '';
                if (typingUsersList.length === 1) {
                    typingText = `${typingUsersList[0].name} печатает...`;
                } else if (typingUsersList.length === 2) {
                    typingText = `${typingUsersList[0].name} и ${typingUsersList[1].name} печатают...`;
                } else {
                    typingText = `${typingUsersList[0].name} и ещё ${typingUsersList.length - 1} печатают...`;
                }
                
                text.textContent = typingText;
                indicator.style.display = 'flex';
            } else {
                indicator.style.display = 'none';
            }
        }
    });
}

// ==================== UI Rendering ====================
function renderChats() {
    const chatsList = document.getElementById('chatsList');
    
    if (chats.length === 0) {
        chatsList.innerHTML = `
            <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                <span class="material-icons" style="font-size: 48px; margin-bottom: 12px; opacity: 0.5;">chat_bubble_outline</span>
                <p>Нет активных чатов</p>
                <p style="font-size: 12px; margin-top: 8px;">Создайте новый чат для начала общения</p>
            </div>
        `;
        return;
    }
    
    const sortedChats = chats.sort((a, b) => {
        const timeA = a.lastMessage?.createdAt || a.createdAt;
        const timeB = b.lastMessage?.createdAt || b.createdAt;
        return new Date(timeB) - new Date(timeA);
    });
    
    chatsList.innerHTML = sortedChats.map(chat => {
        if (!chat || !chat.id) {
            console.warn('Invalid chat in list:', chat);
            return '';
        }
        
        const isActive = currentChat && currentChat.id === chat.id;
        
        let chatName, avatarContent, showOnline, avatarStyle;
        
        const participants = Array.isArray(chat.participants) ? chat.participants : [];
        
        if (chat.type === 'group') {
            chatName = chat.name || 'Групповой чат';
            avatarContent = '<span class="material-icons">group</span>';
            avatarStyle = `background: ${generateGradient(chatName)};`;
            showOnline = false;
        } else {
            // ИСПРАВЛЕНО: Объявляем otherParticipant в правильной области видимости
            const otherParticipant = users.find(u => participants.includes(u.id) && u.id !== currentUser.id);
            chatName = otherParticipant?.name || 'Чат';
            showOnline = otherParticipant && onlineUsers.has(otherParticipant.id);
            
            // Показываем аватар или инициалы
            if (otherParticipant && otherParticipant.avatar) {
                avatarContent = `<img src="${otherParticipant.avatar}" alt="${chatName}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                avatarStyle = 'background: transparent;';
            } else if (otherParticipant) {
                avatarContent = getUserInitials(otherParticipant.name);
                avatarStyle = `background: ${generateGradient(chatName)};`;
            } else {
                avatarContent = '<span class="material-icons">person</span>';
                avatarStyle = `background: ${generateGradient(chatName)};`;
            }
        }
        
        let messagePreview = 'Нет сообщений';
        if (chat.lastMessage) {
            const messageText = chat.lastMessage.text || chat.lastMessage.content || 'Сообщение';
            const sender = users.find(u => u.id === chat.lastMessage.senderId);
            const senderName = sender?.id === currentUser.id ? 'Вы' : sender?.name || 'Пользователь';
            
            if (chat.lastMessage.metadata && chat.lastMessage.metadata.files && chat.lastMessage.metadata.files.length > 0) {
                const file = chat.lastMessage.metadata.files[0];
                const fileType = getFileType(file.name);
                const fileIcon = fileType === 'image' ? '🖼️' : fileType === 'video' ? '🎥' : fileType === 'audio' ? '🎵' : '📄';
                messagePreview = `${senderName}: ${fileIcon} ${file.name}`;
            } else if (chat.type === 'group') {
                messagePreview = `${senderName}: ${messageText.substring(0, 25)}`;
            } else {
                const prefix = chat.lastMessage.senderId === currentUser.id ? 'Вы: ' : '';
                messagePreview = prefix + messageText.substring(0, 30);
            }
        }
        
        return `
            <div class="chat-item ${isActive ? 'active' : ''}" data-chat-id="${chat.id}">
                <div class="chat-avatar ${showOnline ? 'online' : ''}" style="${avatarStyle}">
                    ${avatarContent}
                </div>
                <div class="chat-details">
                    <div class="chat-header-row">
                        <span class="chat-name">${chatName}</span>
                        <span class="chat-time">${chat.lastMessage ? formatTime(chat.lastMessage.createdAt) : ''}</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span class="chat-preview">${messagePreview}</span>
                        ${chat.unreadCount > 0 ? `<span class="unread-badge">${chat.unreadCount}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', () => {
            const chatId = item.dataset.chatId;
            const chat = chats.find(c => c.id === chatId);
            if (chat) {
                openChat(chat);
            }
        });
    });
}

function renderContacts() {
    const contactsList = document.getElementById('contactsList');
    
    if (users.length === 0) {
        contactsList.innerHTML = `
            <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                <p>Загрузка контактов...</p>
            </div>
        `;
        return;
    }
    
    const sortedUsers = users
        .filter(u => u.id !== currentUser.id)
        .sort((a, b) => {
            const aOnline = onlineUsers.has(a.id);
            const bOnline = onlineUsers.has(b.id);
            if (aOnline && !bOnline) return -1;
            if (!aOnline && bOnline) return 1;
            return a.name.localeCompare(b.name);
        });
    
    contactsList.innerHTML = sortedUsers.map(user => {
        const isOnline = onlineUsers.has(user.id);
        
        // ИСПРАВЛЕНО: Показываем аватар или инициалы
        let avatarContent;
        if (user.avatar) {
            avatarContent = `<img src="${user.avatar}" alt="${user.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        } else {
            avatarContent = getUserInitials(user.name);
        }
        
        return `
            <div class="chat-item" data-user-id="${user.id}">
                <div class="chat-avatar ${isOnline ? 'online' : ''}" style="background: ${user.avatar ? 'transparent' : generateGradient(user.name)}">
                    ${avatarContent}
                </div>
                <div class="chat-details">
                    <div class="chat-header-row">
                        <span class="chat-name">${user.name}</span>
                    </div>
                    <span class="chat-preview">${isOnline ? 'В сети' : 'Не в сети'}</span>
                </div>
            </div>
        `;
    }).join('');
    
    document.querySelectorAll('#contactsList .chat-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.dataset.userId;
            selectContact(userId);
        });
    });
}

function selectContact(userId) {
    console.log('=== SELECT CONTACT ===');
    console.log('User ID:', userId);
    
    const user = users.find(u => u.id === userId);
    if (!user) {
        console.error('User not found:', userId);
        showToast('Пользователь не найден', 'error');
        return;
    }
    
    console.log('User found:', user.name, user.id);

    document.querySelectorAll('#contactsList .chat-item').forEach(item => {
        item.classList.toggle('active', item.dataset.userId === userId);
    });

    // Ищем существующий чат
    const existingChat = chats.find(chat => {
        const isPrivate = chat.type === 'private';
        const hasParticipants = Array.isArray(chat.participants);
        const hasUser = hasParticipants && chat.participants.includes(userId);
        const hasCurrentUser = hasParticipants && chat.participants.includes(currentUser.id);
        
        console.log('Checking chat:', chat.id, {
            isPrivate,
            hasParticipants,
            hasUser,
            hasCurrentUser,
            participants: chat.participants
        });
        
        return isPrivate && hasParticipants && hasUser && hasCurrentUser;
    });

    if (existingChat) {
        console.log('Opening existing chat:', existingChat.id);
        openChat(existingChat);
        pendingChatUser = null;
    } else {
        console.log('No existing chat, showing empty window for user:', user.id);
        showEmptyChatWindow(user);
    }
}

function showEmptyChatWindow(user) {
    console.log('=== SHOW EMPTY CHAT WINDOW ===');
    console.log('User:', user);
    
    if (!user || !user.id) {
        console.error('Invalid user for empty chat window');
        return;
    }

    pendingChatUser = user;
    window.pendingChatUser = user;
    currentChat = null;
    window.currentChat = null;
    
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('chatContainer').style.display = 'flex';

    const isOnline = onlineUsers.has(user.id);

    const chatHeader = document.querySelector('.chat-header');
    const chatAvatar = chatHeader.querySelector('.chat-avatar');
    
    document.getElementById('chatName').textContent = user.name;
    document.getElementById('chatStatus').textContent = isOnline ? 'В сети' : 'Не в сети';
    
    // ИСПРАВЛЕНО: Показываем аватар пользователя
    if (user.avatar) {
        chatAvatar.style.background = 'transparent';
        chatAvatar.innerHTML = `<img src="${user.avatar}" alt="${user.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
    } else {
        chatAvatar.style.background = generateGradient(user.name);
        chatAvatar.innerHTML = getUserInitials(user.name);
    }

    chatHeader.removeAttribute('data-chat-type');

    const messagesList = document.getElementById('messagesList');
    messagesList.innerHTML = `
        <div style="text-align: center; padding: 60px 20px; color: rgba(0,0,0,0.4);">
            <span class="material-icons" style="font-size: 64px; opacity: 0.3; margin-bottom: 16px;">chat_bubble_outline</span>
            <p style="font-size: 16px; margin: 0;">Начните диалог с ${user.name}</p>
            <p style="font-size: 14px; margin: 8px 0 0 0; opacity: 0.6;">Чат будет создан после отправки первого сообщения</p>
        </div>
    `;

    const messageTextarea = document.getElementById('messageTextarea');
    messageTextarea.disabled = false;
    messageTextarea.placeholder = 'Написать сообщение...';
    messageTextarea.value = '';
    messageTextarea.style.height = 'auto';
    
    const audioCallBtn = document.getElementById('audioCallBtn');
    const videoCallBtn = document.getElementById('videoCallBtn');
    
    // Отключаем кнопки звонков для нового чата
    if (audioCallBtn) audioCallBtn.disabled = true;
    if (videoCallBtn) videoCallBtn.disabled = true;

    document.getElementById('typingIndicator').style.display = 'none';

    messageTextarea.focus();
    
    console.log('Empty chat window shown, pendingChatUser set to:', pendingChatUser.id);
}

async function createChatForContact(user) {
    try {
        console.log('=== CREATE CHAT FOR CONTACT ===');
        console.log('Creating chat for user:', user.id, user.name);
        
        // Проверяем, может чат уже существует
        const existingChat = chats.find(chat => 
            chat.type === 'private' && 
            Array.isArray(chat.participants) &&
            chat.participants.includes(user.id) && 
            chat.participants.includes(currentUser.id)
        );
        
        if (existingChat) {
            console.log('Found existing chat:', existingChat.id);
            currentChat = existingChat;
            await openChat(existingChat);
            return existingChat;
        }
        
        // Создаем новый чат
        const chatData = await apiCall('/api/chats', {
            method: 'POST',
            body: JSON.stringify({
                type: 'private',
                participants: [user.id]
            })
        });

        console.log('Chat created response:', chatData);

        // Убедимся, что у чата есть все необходимые поля
        if (!chatData.participants || !Array.isArray(chatData.participants)) {
            console.warn('Chat missing participants, adding manually');
            chatData.participants = [currentUser.id, user.id];
        }
        
        // Убедимся, что есть базовые поля
        if (!chatData.createdAt) {
            chatData.createdAt = new Date().toISOString();
        }
        
        if (chatData.type !== 'group' && chatData.type !== 'private') {
            chatData.type = 'private';
        }
        
        // Добавляем чат в список
        const existingChatIndex = chats.findIndex(c => c.id === chatData.id);
        if (existingChatIndex === -1) {
            console.log('Adding chat to chats array');
            chats.push(chatData);
        } else {
            console.log('Updating existing chat in array');
            chats[existingChatIndex] = chatData;
        }
        
        // Устанавливаем как текущий чат
         currentChat = chatData;
        window.currentChat = chatData;
        
        // Обновляем UI
        renderChats();
        
        // Открываем чат (без загрузки сообщений, т.к. их еще нет)
        document.getElementById('welcomeScreen').style.display = 'none';
        document.getElementById('chatContainer').style.display = 'flex';
        
        const chatHeader = document.querySelector('.chat-header');
        const chatAvatar = chatHeader.querySelector('.chat-avatar');
        
        document.getElementById('chatName').textContent = user.name;
        document.getElementById('chatStatus').textContent = onlineUsers.has(user.id) ? 'В сети' : 'Не в сети';
        
        chatAvatar.style.background = generateGradient(user.name);
        chatAvatar.innerHTML = getUserInitials(user.name);
        
        chatHeader.removeAttribute('data-chat-type');
        
        // Очищаем список сообщений
        const messagesList = document.getElementById('messagesList');
        messagesList.innerHTML = '';
        
        // Включаем кнопки звонков
        const audioCallBtn = document.getElementById('audioCallBtn');
        const videoCallBtn = document.getElementById('videoCallBtn');
        if (audioCallBtn) audioCallBtn.disabled = false;
        if (videoCallBtn) videoCallBtn.disabled = false;
        
        console.log('Chat created and opened successfully:', chatData.id);
        return chatData;
        
    } catch (error) {
        console.error('Error creating chat:', error);
        showToast('Ошибка создания чата: ' + error.message, 'error');
        throw error;
    }
}

async function openChat(chat) {
    if (!chat || !chat.id) {
        console.error('Invalid chat object:', chat);
        showToast('Ошибка: неверный чат', 'error');
        return;
    }
    
    currentChat = chat;
    window.currentChat = chat;
    pendingChatUser = null;
    window.pendingChatUser = null;
    
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('chatContainer').style.display = 'flex';
    
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.toggle('active', item.dataset.chatId === chat.id);
    });
    
    const chatHeader = document.querySelector('.chat-header');
    const chatAvatar = chatHeader.querySelector('.chat-avatar');
    
    if (chat.type === 'group') {
        // Проверяем наличие participants и его тип
        const participantCount = Array.isArray(chat.participants) ? chat.participants.length : 0;
        
        document.getElementById('chatName').textContent = chat.name || 'Групповой чат';
        document.getElementById('chatStatus').textContent = `${participantCount} ${getParticipantWord(participantCount)}`;
        
        chatAvatar.style.background = generateGradient(chat.name || 'Group');
        chatAvatar.innerHTML = '<span class="material-icons">group</span>';
        
        chatHeader.setAttribute('data-chat-type', 'group');
        
    } else {
        // ИСПРАВЛЕНО: Правильное отображение аватара для приватного чата
        const participants = Array.isArray(chat.participants) ? chat.participants : [];
        const otherParticipant = users.find(u => participants.includes(u.id) && u.id !== currentUser.id);
        const chatName = otherParticipant?.name || 'Чат';
        const isOnline = otherParticipant && onlineUsers.has(otherParticipant.id);
        
        document.getElementById('chatName').textContent = chatName;
        document.getElementById('chatStatus').textContent = isOnline ? 'В сети' : 'Не в сети';
        
        // ИСПРАВЛЕНО: Показываем аватар или инициалы
        if (otherParticipant) {
            if (otherParticipant.avatar) {
                chatAvatar.style.background = 'transparent';
                chatAvatar.innerHTML = `<img src="${otherParticipant.avatar}" alt="${chatName}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            } else {
                chatAvatar.style.background = generateGradient(chatName);
                chatAvatar.innerHTML = getUserInitials(chatName);
            }
        } else {
            chatAvatar.style.background = generateGradient(chatName);
            chatAvatar.innerHTML = '<span class="material-icons">person</span>';
        }
        
        chatHeader.removeAttribute('data-chat-type');
    }
    
    await loadMessages(chat.id);
    
    socket.emit('messages:read', chat.id);
    
    document.getElementById('messageTextarea').focus();
    
    // Включаем кнопки звонков
    const audioCallBtn = document.getElementById('audioCallBtn');
    const videoCallBtn = document.getElementById('videoCallBtn');
    if (audioCallBtn) audioCallBtn.disabled = false;
    if (videoCallBtn) videoCallBtn.disabled = false;
}

function getParticipantWord(count) {
    if (count % 10 === 1 && count % 100 !== 11) {
        return 'участник';
    } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
        return 'участника';
    } else {
        return 'участников';
    }
}

setInterval(() => {
    document.querySelectorAll('.chat-time').forEach(timeEl => {
        const chatId = timeEl.closest('.chat-item')?.dataset.chatId;
        if (chatId) {
            const chat = chats.find(c => c.id === chatId);
            if (chat && chat.lastMessage) {
                timeEl.textContent = formatTime(chat.lastMessage.createdAt);
            }
        }
    });
    
    document.querySelectorAll('.message-time').forEach(timeEl => {
        const timestamp = timeEl.dataset.timestamp;
        if (timestamp) {
            timeEl.textContent = formatTime(new Date(timestamp));
        }
    });
}, 60000);

async function loadMessages(chatId) {
    try {
        const messages = await apiCall(`/api/messages/${chatId}`);
        renderMessages(messages);
    } catch (error) {
        showToast('Ошибка загрузки сообщений', 'error');
    }
}

// ==================== НОВОЕ: Рендер файлов в сообщениях ====================
// ==================== РЕНДЕР ФАЙЛОВ В СООБЩЕНИЯХ ====================
function renderMessageFiles(files) {
    if (!files || files.length === 0) return '';
    
    return files.map(file => {
        const fileType = getFileType(file.name);
        
        if (fileType === 'image') {
            return `
                <div class="message-image">
                    <img src="${file.url}" alt="${file.name}" onclick="window.open('${file.url}', '_blank')" style="cursor: pointer; max-width: 300px; max-height: 300px; border-radius: 8px; object-fit: cover;">
                </div>
            `;
        } else if (fileType === 'video') {
            return `
                <div class="message-video">
                    <video controls style="max-width: 300px; max-height: 300px; border-radius: 8px;">
                        <source src="${file.url}" type="video/mp4">
                        Ваш браузер не поддерживает видео.
                    </video>
                </div>
            `;
        } else if (fileType === 'audio') {
            return `
                <div class="message-audio">
                    <audio controls style="width: 300px;">
                        <source src="${file.url}" type="audio/mpeg">
                        Ваш браузер не поддерживает аудио.
                    </audio>
                    <p style="font-size: 12px; margin: 4px 0 0 0; color: var(--text-secondary);">${file.name}</p>
                </div>
            `;
        } else {
            return `
                <div class="message-file" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(0,0,0,0.05); border-radius: 8px; cursor: pointer;" onclick="window.open('${file.url}', '_blank')">
                    <span class="material-icons" style="font-size: 36px; color: var(--primary-color);">description</span>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${file.name}</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">${file.size ? formatFileSize(file.size) : 'Файл'}</div>
                    </div>
                    <span class="material-icons" style="color: var(--text-secondary);">download</span>
                </div>
            `;
        }
    }).join('');
}


function getFileType(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    const videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
    const audioExts = ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
    const docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    
    if (imageExts.includes(ext)) return 'image';
    if (videoExts.includes(ext)) return 'video';
    if (audioExts.includes(ext)) return 'audio';
    if (docExts.includes(ext)) return 'document';
    return 'file';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
}

function renderMessages(messages) {
    const messagesList = document.getElementById('messagesList');
    const isGroupChat = currentChat && currentChat.type === 'group';
    
    messagesList.innerHTML = messages.map(msg => {
        const isSent = msg.senderId === currentUser.id;
        const sender = users.find(u => u.id === msg.senderId);
        const isSystem = msg.type === 'system';
        
        // Рендерим файлы если они есть
        const filesHtml = msg.metadata && msg.metadata.files ? renderMessageFiles(msg.metadata.files) : '';
        
        // ИСПРАВЛЕНО: Не показываем пустой bubble если есть только файлы
        const hasText = msg.text && msg.text.trim().length > 0;
        
        // ИСПРАВЛЕНО: Аватар сообщения
        let messageAvatarHtml = '';
        if (!isSent && !isSystem && sender) {
            if (sender.avatar) {
                messageAvatarHtml = `
                    <div class="message-avatar" style="background: transparent;">
                        <img src="${sender.avatar}" alt="${sender.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    </div>
                `;
            } else {
                messageAvatarHtml = `
                    <div class="message-avatar" style="background: ${generateGradient(sender.name)}">
                        ${getUserInitials(sender.name)}
                    </div>
                `;
            }
        }
        
        return `
            <div class="message ${isSent ? 'sent' : 'received'} ${isSystem ? 'system' : ''}">
                ${messageAvatarHtml}
                <div class="message-content">
                    ${isGroupChat && !isSent && !isSystem ? `
                        <div class="message-sender-name" data-sender-id="${msg.senderId}">${sender?.name || 'Пользователь'}</div>
                    ` : ''}
                    ${filesHtml}
                    ${hasText ? `<div class="message-bubble ${isSystem ? 'system-bubble' : ''}">${msg.text}</div>` : ''}
                    <div class="message-time" data-timestamp="${msg.createdAt}">${formatTime(msg.createdAt)}</div>
                </div>
            </div>
        `;
    }).join('');
    
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
    
    if (typeof makeMessageSendersClickable === 'function') {
        makeMessageSendersClickable();
    }
}

function handleNewMessage(message) {
    console.log('New message received:', message);
    
    if (currentChat && message.chatId === currentChat.id) {
        const messagesList = document.getElementById('messagesList');
        const isSent = message.senderId === currentUser.id;
        const sender = users.find(u => u.id === message.senderId);
        const isSystem = message.type === 'system';
        const isGroupChat = currentChat.type === 'group';
        
        const filesHtml = message.metadata && message.metadata.files ? renderMessageFiles(message.metadata.files) : '';
        
        // ИСПРАВЛЕНО: Не показываем пустой bubble если есть только файлы
        const hasText = message.text && message.text.trim().length > 0;
        
        // ИСПРАВЛЕНО: Аватар сообщения
        let messageAvatarHtml = '';
        if (!isSent && !isSystem && sender) {
            if (sender.avatar) {
                messageAvatarHtml = `
                    <div class="message-avatar" style="background: transparent;">
                        <img src="${sender.avatar}" alt="${sender.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    </div>
                `;
            } else {
                messageAvatarHtml = `
                    <div class="message-avatar" style="background: ${generateGradient(sender.name)}">
                        ${getUserInitials(sender.name)}
                    </div>
                `;
            }
        }
        
        const messageHtml = `
            <div class="message ${isSent ? 'sent' : 'received'} ${isSystem ? 'system' : ''}">
                ${messageAvatarHtml}
                <div class="message-content">
                    ${isGroupChat && !isSent && !isSystem ? `
                        <div class="message-sender-name" data-sender-id="${message.senderId}">${sender?.name || 'Пользователь'}</div>
                    ` : ''}
                    ${filesHtml}
                    ${hasText ? `<div class="message-bubble ${isSystem ? 'system-bubble' : ''}">${message.text}</div>` : ''}
                    <div class="message-time" data-timestamp="${message.createdAt}">${formatTime(message.createdAt)}</div>
                </div>
            </div>
        `;
        
        messagesList.insertAdjacentHTML('beforeend', messageHtml);
        
        if (typeof makeMessageSendersClickable === 'function') {
            makeMessageSendersClickable();
        }
        
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
        
        socket.emit('messages:read', currentChat.id);
    }
    
    const chat = chats.find(c => c.id === message.chatId);
    if (chat) {
        chat.lastMessage = message;
        if (message.senderId !== currentUser.id && (!currentChat || currentChat.id !== message.chatId)) {
            chat.unreadCount = (chat.unreadCount || 0) + 1;
        }
        renderChats();
    }
    
    if (message.type === 'system' && message.metadata?.action === 'start' && message.metadata?.conferenceId) {
        const conferenceId = message.metadata.conferenceId;
        console.log('Conference start detected:', conferenceId);
        
        if (message.senderId !== currentUser.id) {
            const sender = users.find(u => u.id === message.senderId);
            showIncomingCallModal(sender?.name || 'Пользователь', conferenceId, message.chatId);
        }
    }
    
    if (message.senderId !== currentUser.id && (!currentChat || currentChat.id !== message.chatId)) {
        const sender = users.find(u => u.id === message.senderId);
        showNotification(sender?.name || 'Новое сообщение', message.text);
    }
}

function sendMessage() {
    console.log('=== SEND MESSAGE FUNCTION CALLED ===');
    console.trace('Call stack'); // Показывает откуда вызвана функция
    
    const textarea = document.getElementById('messageTextarea');
    console.log('Textarea element:', textarea);
    
    if (!textarea) {
        console.error('Textarea not found!');
        return;
    }
    
    const text = textarea.value.trim();
    
    console.log('Textarea value:', textarea.value);
    console.log('Trimmed text:', text);
    console.log('Text length:', text.length);
    
    if (!text) {
        console.log('Empty message, ignoring');
        return;
    }

    console.log('currentChat:', currentChat);
    console.log('pendingChatUser:', pendingChatUser);
    console.log('Message text:', text);

    // ИСПРАВЛЕНИЕ: Если есть pendingChatUser (новый диалог), создаем чат
    if (pendingChatUser && !currentChat) {
        console.log('Creating new chat for pending user:', pendingChatUser.id);
        
        // Блокируем textarea и кнопку отправки
        textarea.disabled = true;
        const sendBtn = document.getElementById('sendBtn');
        if (sendBtn) {
            console.log('Send button found, disabling');
            sendBtn.disabled = true;
        } else {
            console.warn('Send button not found!');
        }
        
        const originalText = text;
        textarea.value = 'Отправка...';
        
        createChatForContact(pendingChatUser)
            .then(chat => {
                console.log('Chat created successfully:', chat.id);
                console.log('Sending message to new chat...');
                
                // Отправляем сообщение
                socket.emit('message:send', {
                    chatId: chat.id,
                    text: originalText,
                    type: 'text'
                });
                
                console.log('Message emitted via socket');
                
                // Очищаем поля
                textarea.value = '';
                textarea.style.height = 'auto';
                textarea.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                textarea.focus();
                
                pendingChatUser = null;
                
                console.log('Message sent successfully to new chat');
            })
            .catch(error => {
                console.error('Failed to create chat:', error);
                showToast('Ошибка создания чата', 'error');
                
                // Восстанавливаем текст при ошибке
                textarea.value = originalText;
                textarea.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                textarea.focus();
            });
        
        return;
    }
    
    // Проверяем наличие активного чата
    if (!currentChat || !currentChat.id) {
        console.error('No current chat to send message to');
        console.log('currentChat state:', currentChat);
        console.log('pendingChatUser state:', pendingChatUser);
        showToast('Ошибка: выберите чат или пользователь', 'error');
        return;
    }
    
    console.log('Sending message to existing chat:', currentChat.id);
    
    socket.emit('message:send', {
        chatId: currentChat.id,
        text,
        type: 'text'
    });
    
    console.log('Message emitted to existing chat');
    
    textarea.value = '';
    textarea.style.height = 'auto';
    
    if (currentChat && currentChat.id) {
        socket.emit('typing:stop', currentChat.id);
    }
}

// ==================== Data Loading ====================
async function loadUsers() {
    try {
        users = await apiCall('/api/users');
        renderContacts();
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

async function loadChats() {
    try {
        chats = await apiCall('/api/chats');
        renderChats();
    } catch (error) {
        console.error('Error loading chats:', error);
    }
}

async function startChatWithUser(userId) {
    selectContact(userId);
}

// ==================== Notifications ====================
function showNotification(title, body) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body,
            icon: '/assets/logo.png',
            badge: '/assets/badge.png'
        });
    }
}

async function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            showToast('Уведомления включены');
        }
    }
}

// ==================== Incoming Call Modal ====================
function showIncomingCallModal(callerName, conferenceId, chatId) {
    const modal = document.getElementById('incomingCallModal');
    const modalContent = document.getElementById('incomingCallContent');
    
    if (!modal || !modalContent) {
        console.error('Incoming call modal not found');
        return;
    }
    
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulse 2s infinite;">
                <span class="material-icons" style="font-size: 40px; color: white;">videocam</span>
            </div>
            <h2 style="margin: 0 0 8px 0; font-size: 24px; color: var(--text-primary);">Входящий звонок</h2>
            <p style="margin: 0 0 24px 0; color: var(--text-secondary); font-size: 16px;">
                <strong>${callerName}</strong> звонит вам
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="declineIncomingCall('${conferenceId}')" style="flex: 1; max-width: 140px; padding: 12px 24px; border: none; border-radius: 8px; background: #ef4444; color: white; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="material-icons">call_end</span>
                    Отклонить
                </button>
                <button onclick="acceptIncomingCall('${conferenceId}', '${chatId}')" style="flex: 1; max-width: 140px; padding: 12px 24px; border: none; border-radius: 8px; background: #10b981; color: white; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="material-icons">videocam</span>
                    Принять
                </button>
            </div>
        </div>
        
        <style>
            @keyframes pulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.8; }
            }
        </style>
    `;
    
    modal.style.display = 'flex';
    playRingtone();
}

window.acceptIncomingCall = async function(conferenceId, chatId) {
    stopRingtone();
    const modal = document.getElementById('incomingCallModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (typeof joinExistingConference === 'function') {
        await joinExistingConference(conferenceId, chatId);
    }
};

window.declineIncomingCall = function(conferenceId) {
    stopRingtone();
    const modal = document.getElementById('incomingCallModal');
    if (modal) {
        modal.style.display = 'none';
    }
    showToast('Звонок отклонен', 'info');
};

let ringtoneAudio = null;

function playRingtone() {
    try {
        ringtoneAudio = new Audio();
        ringtoneAudio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZVRE';
        ringtoneAudio.loop = true;
        ringtoneAudio.volume = 0.5;
        ringtoneAudio.play().catch(e => console.log('Cannot play ringtone:', e));
    } catch (error) {
        console.error('Ringtone error:', error);
    }
}

function stopRingtone() {
    if (ringtoneAudio) {
        ringtoneAudio.pause();
        ringtoneAudio.currentTime = 0;
        ringtoneAudio = null;
    }
}

// ==================== UI Helpers ====================
function updateOnlineStatus() {
    renderChats();
    renderContacts();
}

// ==================== Render Users List for New Chat Modal ====================
function renderUsersList() {
    const usersList = document.getElementById('usersList');
    const groupUsersList = document.getElementById('groupUsersList');
    
    if (!users || users.length === 0) {
        const emptyHtml = `
            <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                <p>Загрузка пользователей...</p>
            </div>
        `;
        if (usersList) usersList.innerHTML = emptyHtml;
        if (groupUsersList) groupUsersList.innerHTML = emptyHtml;
        return;
    }
    
    const otherUsers = users.filter(u => u.id !== currentUser.id);
    
    const usersHtml = otherUsers.map(user => {
        const isOnline = onlineUsers.has(user.id);
        
        return `
            <div class="user-item" data-user-id="${user.id}">
                <div class="user-avatar ${isOnline ? 'online' : ''}" style="background: ${generateGradient(user.name)}">
                    ${user.avatar ? `<img src="${user.avatar}" alt="${user.name}">` : getUserInitials(user.name)}
                </div>
                <div class="user-info">
                    <h4>${user.name}</h4>
                    <p>${user.username}</p>
                </div>
                <span class="user-status ${isOnline ? 'online' : ''}">${isOnline ? 'В сети' : 'Не в сети'}</span>
            </div>
        `;
    }).join('');
    
    if (usersList) {
        usersList.innerHTML = usersHtml;
        
        usersList.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                createPrivateChat(userId);
            });
        });
    }
    
    if (groupUsersList) {
        groupUsersList.innerHTML = usersHtml;
        
        groupUsersList.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                item.classList.toggle('selected');
                updateSelectedUsers();
            });
        });
    }
}

async function createPrivateChat(userId) {
    try {
        const user = users.find(u => u.id === userId);
        if (!user) {
            showToast('Пользователь не найден', 'error');
            return;
        }
        
        const existingChat = chats.find(c => 
            c.type === 'private' && 
            c.participants.includes(userId) &&
            c.participants.includes(currentUser.id)
        );
        
        if (existingChat) {
            closeModal();
            openChat(existingChat);
            showToast('Чат открыт', 'success');
            return;
        }
        
        const chatData = await apiCall('/api/chats', {
            method: 'POST',
            body: JSON.stringify({
                type: 'private',
                participants: [userId]
            })
        });
        
        closeModal();
        
        setTimeout(() => {
            const newChat = chats.find(c => c.id === chatData.id);
            if (newChat) {
                openChat(newChat);
            }
        }, 300);
        
        showToast('Чат создан', 'success');
        
    } catch (err) {
        console.error('Create chat error:', err);
        showToast(err.message || 'Ошибка создания чата', 'error');
    }
}

function updateSelectedUsers() {
    const selectedUsers = document.querySelectorAll('#groupUsersList .user-item.selected');
    const selectedContainer = document.getElementById('selectedUsers');
    
    if (selectedUsers.length === 0) {
        selectedContainer.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 20px;">Выберите участников</p>';
        return;
    }
    
    selectedContainer.innerHTML = Array.from(selectedUsers).map(item => {
        const userId = item.dataset.userId;
        const user = users.find(u => u.id === userId);
        
        return `
            <div class="selected-user" data-user-id="${userId}">
                <div class="user-avatar" style="background: ${generateGradient(user.name)}">
                    ${getUserInitials(user.name)}
                </div>
                <span>${user.name}</span>
                <button class="remove-user" onclick="removeSelectedUser('${userId}')">
                    <span class="material-icons">close</span>
                </button>
            </div>
        `;
    }).join('');
}

window.removeSelectedUser = function(userId) {
    const userItem = document.querySelector(`#groupUsersList .user-item[data-user-id="${userId}"]`);
    if (userItem) {
        userItem.classList.remove('selected');
        updateSelectedUsers();
    }
};

// ==================== ИСПРАВЛЕНО: Создание группового чата ====================
async function createGroupChat() {
    try {
        const groupName = document.getElementById('groupNameInput').value.trim();
        const selectedUserItems = document.querySelectorAll('#groupUsersList .user-item.selected');
        
        if (!groupName) {
            showToast('Введите название группы', 'warning');
            return;
        }
        
        if (selectedUserItems.length === 0) {
            showToast('Выберите участников', 'warning');
            return;
        }
        
        const participantIds = Array.from(selectedUserItems).map(item => item.dataset.userId);
        
        // ИСПРАВЛЕНИЕ: Закрываем модал и очищаем форму ДО создания чата
        closeModal();
        document.getElementById('groupNameInput').value = '';
        selectedUserItems.forEach(item => item.classList.remove('selected'));
        updateSelectedUsers();
        
        // Создаем чат
        const chatData = await apiCall('/api/chats', {
            method: 'POST',
            body: JSON.stringify({
                type: 'group',
                name: groupName,
                participants: participantIds
            })
        });
        
        console.log('Group chat created:', chatData.id);
        
        // Открываем чат через небольшую задержку, чтобы дождаться события socket
        setTimeout(() => {
            const newChat = chats.find(c => c.id === chatData.id);
            if (newChat) {
                openChat(newChat);
            }
        }, 300);
        
        showToast('Групповой чат создан', 'success');
        
    } catch (err) {
        console.error('Create group chat error:', err);
        showToast(err.message || 'Ошибка создания группы', 'error');
    }
}

function openChatInfo(chat) {
    if (!chat || !chat.id) {
        console.error('Invalid chat for info panel:', chat);
        return;
    }
    
    const chatInfoAvatar = document.getElementById('chatInfoAvatar');
    const chatInfoName = document.getElementById('chatInfoName');
    const chatInfoStatus = document.getElementById('chatInfoStatus');
    const chatMembersSection = document.getElementById('chatMembersSection');
    const chatMembersList = document.getElementById('chatMembersList');
    
    const participants = Array.isArray(chat.participants) ? chat.participants : [];
    
    if (chat.type === 'group') {
        chatInfoAvatar.style.background = generateGradient(chat.name || 'Group');
        chatInfoAvatar.innerHTML = '<span class="material-icons">group</span>';
        chatInfoName.textContent = chat.name || 'Групповой чат';
        chatInfoStatus.textContent = `${participants.length} ${getParticipantWord(participants.length)}`;
        
        chatMembersSection.style.display = 'block';
        
        const members = participants
            .map(userId => users.find(u => u.id === userId))
            .filter(u => u);
        
        chatMembersList.innerHTML = members.map(member => {
            const isOnline = onlineUsers.has(member.id);
            const isSelf = member.id === currentUser.id;
            
            // ИСПРАВЛЕНО: Показываем аватар участника
            let memberAvatarContent;
            if (member.avatar) {
                memberAvatarContent = `<img src="${member.avatar}" alt="${member.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            } else {
                memberAvatarContent = getUserInitials(member.name);
            }
            
            const avatarStyle = member.avatar ? 'background: transparent;' : `background: ${generateGradient(member.name)};`;
            
            return `
                <div class="member-item">
                    <div class="member-avatar" style="${avatarStyle}">
                        ${memberAvatarContent}
                    </div>
                    <div class="member-info">
                        <div class="member-name">${member.name}${isSelf ? ' (Вы)' : ''}</div>
                        <div class="member-status ${isOnline ? 'online' : ''}">
                            ${isOnline ? 'в сети' : 'не в сети'}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
    } else {
        const otherParticipant = users.find(u => 
            participants.includes(u.id) && u.id !== currentUser.id
        );
        
        if (otherParticipant) {
            // ИСПРАВЛЕНО: Показываем аватар пользователя
            if (otherParticipant.avatar) {
                chatInfoAvatar.style.background = 'transparent';
                chatInfoAvatar.innerHTML = `<img src="${otherParticipant.avatar}" alt="${otherParticipant.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            } else {
                chatInfoAvatar.style.background = generateGradient(otherParticipant.name);
                chatInfoAvatar.innerHTML = getUserInitials(otherParticipant.name);
            }
            
            chatInfoName.textContent = otherParticipant.name;
            
            const isOnline = onlineUsers.has(otherParticipant.id);
            chatInfoStatus.textContent = isOnline ? 'в сети' : 'не в сети';
        }
        
        chatMembersSection.style.display = 'none';
    }
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
}

// ==================== Event Listeners ====================
function setupEventListeners() {
    document.getElementById('loginBtn').addEventListener('click', () => {
        const username = document.getElementById('loginUsername').value;
        const password = document.getElementById('loginPassword').value;
        login(username, password);
    });
    
    ['loginUsername', 'loginPassword'].forEach(id => {
        document.getElementById(id).addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('loginBtn').click();
            }
        });
    });
    
    document.getElementById('registerBtn').addEventListener('click', () => {
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const username = document.getElementById('registerUsername').value;
        const password = document.getElementById('registerPassword').value;
        register(name, email, username, password);
    });
    
    ['registerName', 'registerEmail', 'registerUsername', 'registerPassword'].forEach(id => {
        document.getElementById(id).addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('registerBtn').click();
            }
        });
    });
    
    document.getElementById('showRegister').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
    });
    
    document.getElementById('showLogin').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('registerForm').style.display = 'none';
        document.getElementById('loginForm').style.display = 'block';
    });
    
    const chatInfoBtn = document.getElementById('chatInfoBtn');
    const chatInfoPanel = document.getElementById('chatInfoPanel');
    const closeChatInfoBtn = document.getElementById('closeChatInfoBtn');
    
    if (chatInfoBtn) {
        chatInfoBtn.addEventListener('click', () => {
            if (currentChat) {
                openChatInfo(currentChat);
                chatInfoPanel.classList.add('open');
            }
        });
    }
    
    if (closeChatInfoBtn) {
        closeChatInfoBtn.addEventListener('click', () => {
            chatInfoPanel.classList.remove('open');
        });
    }
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            const parent = btn.closest('.sidebar') || btn.closest('.conference-sidebar');
            
            parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            parent.querySelector(`#${tab}Tab, #${tab}Panel`).classList.add('active');
        });
    });
    
    const newChatBtns = [
        document.getElementById('newChatBtn'),
        document.getElementById('welcomeNewChat')
    ];
    
    newChatBtns.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', () => {
                document.getElementById('newChatModal').classList.add('active');
                renderUsersList();
            });
        }
    });
    
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    document.getElementById('cancelChatBtn').addEventListener('click', closeModal);
    
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            document.getElementById('privateChat').style.display = type === 'private' ? 'block' : 'none';
            document.getElementById('groupChat').style.display = type === 'group' ? 'block' : 'none';
            
            renderUsersList();
        });
    });
    
    document.getElementById('createChatBtn').addEventListener('click', () => {
        const activeType = document.querySelector('.type-btn.active').dataset.type;
        
        if (activeType === 'private') {
            showToast('Выберите пользователя из списка', 'info');
        } else {
            createGroupChat();
        }
    });
    
    const userSearchInput = document.getElementById('userSearchInput');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('#usersList .user-item').forEach(item => {
                const userName = item.querySelector('h4').textContent.toLowerCase();
                const userUsername = item.querySelector('p').textContent.toLowerCase();
                const matches = userName.includes(query) || userUsername.includes(query);
                item.style.display = matches ? 'flex' : 'none';
            });
        });
    }
    
    const groupUserSearchInput = document.getElementById('groupUserSearchInput');
    if (groupUserSearchInput) {
        groupUserSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('#groupUsersList .user-item').forEach(item => {
                const userName = item.querySelector('h4').textContent.toLowerCase();
                const userUsername = item.querySelector('p').textContent.toLowerCase();
                const matches = userName.includes(query) || userUsername.includes(query);
                item.style.display = matches ? 'flex' : 'none';
            });
        });
    }

    const textarea = document.getElementById('messageTextarea');
    let typingTimeout;
    
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        
        if (currentChat && currentChat.id) {
            socket.emit('typing:start', currentChat.id);
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                socket.emit('typing:stop', currentChat.id);
            }, 1000);
        }
    });
    
textarea.addEventListener('keydown', (e) => {
    console.log('Keydown event:', e.key, 'Shift:', e.shiftKey);
    console.log('sendMessage type:', typeof sendMessage);
    console.log('sendMessage function:', sendMessage);
    
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        console.log('Enter pressed without shift - calling sendMessage');
        
        // Попробуем вызвать явно
        if (typeof sendMessage === 'function') {
            sendMessage();
        } else {
            console.error('sendMessage is not a function!', typeof sendMessage);
            // Попробуем использовать сохраненную версию
            if (typeof window.appSendMessage === 'function') {
                console.log('Using window.appSendMessage instead');
                window.appSendMessage();
            }
        }
    }
});
    
    document.getElementById('sendBtn').addEventListener('click', sendMessage);
    
    document.getElementById('videoCallBtn').addEventListener('click', () => {
        if (currentChat && window.startVideoCall) {
            window.startVideoCall(currentChat);
        }
    });
    
    document.getElementById('audioCallBtn').addEventListener('click', () => {
        if (currentChat && window.startAudioCall) {
            window.startAudioCall(currentChat);
        }
    });
}

// ==================== Initialization ====================
function initApp() {
    document.getElementById('authScreen').style.display = 'none';
    document.getElementById('mainApp').style.display = 'flex';
    
    document.getElementById('currentUserName').textContent = currentUser.name;
    
    // ИСПРАВЛЕНО: Показываем аватар текущего пользователя
    const avatar = document.getElementById('currentUserAvatar');
    if (currentUser.avatar) {
        avatar.style.background = 'transparent';
        avatar.innerHTML = `<img src="${currentUser.avatar}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
    } else {
        avatar.style.background = generateGradient(currentUser.name);
        avatar.innerHTML = getUserInitials(currentUser.name);
    }
    
    initSocket();
    setupEventListeners();
    
    setTimeout(() => {
        if (typeof initModals === 'function') {
            initModals();
        }
        if (typeof initSearch === 'function') {
            initSearch();
        }
        if (typeof initNotificationBanner === 'function') {
            initNotificationBanner();
        }
        if (typeof initKeyboardShortcuts === 'function') {
            initKeyboardShortcuts();
        }
        if (typeof initMobileSupport === 'function') {
            initMobileSupport();
        }
        if (typeof setupAutoScroll === 'function') {
            setupAutoScroll();
        }
    }, 200);
    
    setTimeout(requestNotificationPermission, 2000);
}

// ==================== App Start ====================
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
    
    if (token && userStr) {
        try {
            currentUser = JSON.parse(userStr);
            console.log('✅ Loaded user from storage:', currentUser); // Проверка
            initApp();
        } catch (error) {
            console.error('Error parsing user data:', error);
            localStorage.clear();
            sessionStorage.clear();
        }
    } else {
        setupEventListeners();
    }
});


window.createChatForContact = createChatForContact;



// ==================== Update All Avatars in UI ====================
function updateAllAvatarsInUI(userId, avatarUrl) {
    console.log('🔄 Updating all avatars for user:', userId);
    
    // 1. Обновляем аватары в списке чатов
    document.querySelectorAll('.chat-item').forEach(chatItem => {
        const chatId = chatItem.dataset.chatId;
        const chat = chats.find(c => c.id === chatId);
        
        if (chat && chat.type === 'private') {
            const participants = Array.isArray(chat.participants) ? chat.participants : [];
            if (participants.includes(userId)) {
                const avatarEl = chatItem.querySelector('.chat-avatar');
                if (avatarEl) {
                    avatarEl.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                    console.log('✅ Updated avatar in chat list');
                }
            }
        }
    });
    
    // 2. Обновляем аватар в заголовке открытого чата
    if (currentChat && currentChat.type === 'private') {
        const participants = Array.isArray(currentChat.participants) ? currentChat.participants : [];
        if (participants.includes(userId) && userId !== currentUser.id) {
            const chatHeaderAvatar = document.querySelector('.chat-header .chat-avatar');
            if (chatHeaderAvatar) {
                chatHeaderAvatar.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                console.log('✅ Updated avatar in chat header');
            }
        }
    }
    
    // 3. Обновляем аватар в панели информации о чате
    const chatInfoPanel = document.getElementById('chatInfoPanel');
    if (chatInfoPanel && chatInfoPanel.classList.contains('open')) {
        if (currentChat && currentChat.type === 'private') {
            const participants = Array.isArray(currentChat.participants) ? currentChat.participants : [];
            if (participants.includes(userId) && userId !== currentUser.id) {
                const chatInfoAvatar = document.getElementById('chatInfoAvatar');
                if (chatInfoAvatar) {
                    chatInfoAvatar.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                    console.log('✅ Updated avatar in chat info panel');
                }
            }
        }
    }
    
    // 4. Обновляем аватары в списке контактов
    document.querySelectorAll('#contactsList .chat-item').forEach(contactItem => {
        const contactUserId = contactItem.dataset.userId;
        if (contactUserId === userId) {
            const avatarEl = contactItem.querySelector('.chat-avatar');
            if (avatarEl) {
                avatarEl.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                console.log('✅ Updated avatar in contacts list');
            }
        }
    });
    
    // 5. Обновляем аватары в сообщениях
    document.querySelectorAll('.message-avatar').forEach(msgAvatar => {
        // Находим сообщение
        const messageEl = msgAvatar.closest('.message');
        if (messageEl && !messageEl.classList.contains('sent')) {
            // Это входящее сообщение, проверяем отправителя
            const senderNameEl = messageEl.querySelector('.message-sender-name');
            if (senderNameEl) {
                const senderId = senderNameEl.dataset.senderId;
                if (senderId === userId) {
                    msgAvatar.innerHTML = `<img src="${avatarUrl}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                    console.log('✅ Updated avatar in message');
                }
            }
        }
    });
    
    // 6. Обновляем аватар текущего пользователя в сайдбаре
    if (currentUser && currentUser.id === userId) {
        const sidebarAvatar = document.getElementById('currentUserAvatar');
        if (sidebarAvatar) {
            sidebarAvatar.innerHTML = `<img src="${avatarUrl}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            console.log('✅ Updated current user avatar in sidebar');
        }
        
        // Обновляем в профиле
        const profileAvatar = document.getElementById('profileAvatarDisplay');
        if (profileAvatar) {
            profileAvatar.innerHTML = `<img src="${avatarUrl}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            console.log('✅ Updated avatar in profile modal');
        }
    }
}