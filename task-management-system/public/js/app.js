// ==================== Global Variables ====================
let socket = null;
let currentUser = null;
let currentChat = null;
let users = [];
let chats = [];
let onlineUsers = new Set();

// ==================== Utility Functions ====================
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="material-icons">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'warning'}</span>
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
    
    // Check if io is loaded
    if (typeof io === 'undefined') {
        console.error('Socket.io not loaded! Please check your internet connection.');
        showToast('Ошибка подключения к серверу. Проверьте интернет соединение.', 'error');
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
            
            // Setup WebRTC handlers after socket is ready
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
    
    socket.on('chat:created', (chat) => {
        chats.push(chat);
        renderChats();
    });
    
    socket.on('message:new', (message) => {
        handleNewMessage(message);
    });
    
    socket.on('typing:user', (data) => {
        if (currentChat && data.chatId === currentChat.id) {
            const indicator = document.getElementById('typingIndicator');
            const text = document.getElementById('typingText');
            
            if (data.typing) {
                const user = users.find(u => u.id === data.userId);
                text.textContent = `${user?.name || 'Пользователь'} печатает...`;
                indicator.style.display = 'flex';
            } else {
                indicator.style.display = 'none';
            }
        }
    });
    
    // WebRTC events will be handled in webrtc.js
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
        const isActive = currentChat && currentChat.id === chat.id;
        
        let chatName, avatarIcon, showOnline;
        
        if (chat.type === 'group') {
            // Group chat
            chatName = chat.name || 'Групповой чат';
            avatarIcon = 'group';
            showOnline = false;
        } else {
            // Private chat
            const otherParticipant = users.find(u => chat.participants.includes(u.id) && u.id !== currentUser.id);
            chatName = otherParticipant?.name || 'Чат';
            avatarIcon = 'person';
            showOnline = otherParticipant && onlineUsers.has(otherParticipant.id);
        }
        
        // Last message preview
        let messagePreview = 'Нет сообщений';
        if (chat.lastMessage) {
            // Debug logging
            if (!chat.lastMessage.text || chat.lastMessage.text.length > 50) {
                console.warn('Invalid lastMessage.text:', chat.lastMessage);
            }
            
            const messageText = chat.lastMessage.text || chat.lastMessage.content || 'Сообщение';
            const sender = users.find(u => u.id === chat.lastMessage.senderId);
            const senderName = sender?.id === currentUser.id ? 'Вы' : sender?.name || 'Пользователь';
            
            if (chat.type === 'group') {
                messagePreview = `${senderName}: ${messageText.substring(0, 25)}`;
            } else {
                // For private chats, add prefix if sent by current user
                const prefix = chat.lastMessage.senderId === currentUser.id ? 'Вы: ' : '';
                messagePreview = prefix + messageText.substring(0, 30);
            }
        }
        
        return `
            <div class="chat-item ${isActive ? 'active' : ''}" data-chat-id="${chat.id}">
                <div class="chat-avatar ${showOnline ? 'online' : ''}" style="background: ${generateGradient(chatName)}">
                    <span class="material-icons">${avatarIcon}</span>
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
    
    // Add click handlers
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', () => {
            const chatId = item.dataset.chatId;
            const chat = chats.find(c => c.id === chatId);
            openChat(chat);
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
        
        return `
            <div class="chat-item" data-user-id="${user.id}">
                <div class="chat-avatar ${isOnline ? 'online' : ''}" style="background: ${generateGradient(user.name)}">
                    ${user.avatar ? `<img src="${user.avatar}" alt="${user.name}">` : getUserInitials(user.name)}
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
    
    // Add click handlers
    document.querySelectorAll('#contactsList .chat-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.dataset.userId;
            startChatWithUser(userId);
        });
    });
}

async function openChat(chat) {
    currentChat = chat;
    
    // Update UI
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('chatContainer').style.display = 'flex';
    
    // Update active state
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.toggle('active', item.dataset.chatId === chat.id);
    });
    
    // Update header
    const chatHeader = document.querySelector('.chat-header');
    const chatAvatar = chatHeader.querySelector('.chat-avatar');
    
    if (chat.type === 'group') {
        // Group chat
        const participantCount = chat.participants.length;
        
        document.getElementById('chatName').textContent = chat.name || 'Групповой чат';
        document.getElementById('chatStatus').textContent = `${participantCount} ${getParticipantWord(participantCount)}`;
        
        // Update avatar for group
        chatAvatar.style.background = generateGradient(chat.name || 'Group');
        chatAvatar.innerHTML = '<span class="material-icons">group</span>';
        
        // Add group indicator to header
        chatHeader.setAttribute('data-chat-type', 'group');
        
    } else {
        // Private chat
        const otherParticipant = users.find(u => chat.participants.includes(u.id) && u.id !== currentUser.id);
        const chatName = otherParticipant?.name || 'Чат';
        const isOnline = otherParticipant && onlineUsers.has(otherParticipant.id);
        
        document.getElementById('chatName').textContent = chatName;
        document.getElementById('chatStatus').textContent = isOnline ? 'В сети' : 'Не в сети';
        
        // Update avatar for private
        chatAvatar.style.background = generateGradient(chatName);
        chatAvatar.innerHTML = otherParticipant ? getUserInitials(otherParticipant.name) : '<span class="material-icons">person</span>';
        
        // Remove group indicator
        chatHeader.removeAttribute('data-chat-type');
    }
    
    // Load messages
    await loadMessages(chat.id);
    
    // Mark as read
    socket.emit('messages:read', chat.id);
    
    // Focus input
    document.getElementById('messageTextarea').focus();
}

// Helper function for correct Russian word form
function getParticipantWord(count) {
    if (count % 10 === 1 && count % 100 !== 11) {
        return 'участник';
    } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
        return 'участника';
    } else {
        return 'участников';
    }
}

async function loadMessages(chatId) {
    try {
        const messages = await apiCall(`/api/messages/${chatId}`);
        renderMessages(messages);
    } catch (error) {
        showToast('Ошибка загрузки сообщений', 'error');
    }
}

function renderMessages(messages) {
    const messagesList = document.getElementById('messagesList');
    const isGroupChat = currentChat && currentChat.type === 'group';
    
    messagesList.innerHTML = messages.map(msg => {
        const isSent = msg.senderId === currentUser.id;
        const sender = users.find(u => u.id === msg.senderId);
        const isSystem = msg.type === 'system';
        
        return `
            <div class="message ${isSent ? 'sent' : 'received'} ${isSystem ? 'system' : ''}">
                ${!isSent && !isSystem ? `
                    <div class="message-avatar" style="background: ${generateGradient(sender?.name || 'U')}">
                        ${sender ? getUserInitials(sender.name) : 'U'}
                    </div>
                ` : ''}
                <div class="message-content">
                    ${isGroupChat && !isSent && !isSystem ? `
                        <div class="message-sender-name">${sender?.name || 'Пользователь'}</div>
                    ` : ''}
                    <div class="message-bubble ${isSystem ? 'system-bubble' : ''}">${msg.text}</div>
                    <div class="message-time">${formatTime(msg.createdAt)}</div>
                </div>
            </div>
        `;
    }).join('');
    
    // Scroll to bottom
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

function handleNewMessage(message) {
    console.log('New message received:', message);
    
    if (currentChat && message.chatId === currentChat.id) {
        // Add to current chat
        const messagesList = document.getElementById('messagesList');
        const isSent = message.senderId === currentUser.id;
        const sender = users.find(u => u.id === message.senderId);
        const isSystem = message.type === 'system';
        const isGroupChat = currentChat.type === 'group';
        
        const messageHtml = `
            <div class="message ${isSent ? 'sent' : 'received'} ${isSystem ? 'system' : ''}">
                ${!isSent && !isSystem ? `
                    <div class="message-avatar" style="background: ${generateGradient(sender?.name || 'U')}">
                        ${sender ? getUserInitials(sender.name) : 'U'}
                    </div>
                ` : ''}
                <div class="message-content">
                    ${isGroupChat && !isSent && !isSystem ? `
                        <div class="message-sender-name">${sender?.name || 'Пользователь'}</div>
                    ` : ''}
                    <div class="message-bubble ${isSystem ? 'system-bubble' : ''}">${message.text}</div>
                    <div class="message-time">${formatTime(message.createdAt)}</div>
                </div>
            </div>
        `;
        
        messagesList.insertAdjacentHTML('beforeend', messageHtml);
        
        // Scroll to bottom
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
        
        // Mark as read
        socket.emit('messages:read', currentChat.id);
    }
    
    // Update chat in list
    const chat = chats.find(c => c.id === message.chatId);
    if (chat) {
        chat.lastMessage = message;
        if (message.senderId !== currentUser.id && (!currentChat || currentChat.id !== message.chatId)) {
            chat.unreadCount = (chat.unreadCount || 0) + 1;
        }
        renderChats();
    }
    
    // Handle conference invitation - SHOW MODAL instead of auto-join
    if (message.type === 'system' && message.metadata?.action === 'start' && message.metadata?.conferenceId) {
        const conferenceId = message.metadata.conferenceId;
        console.log('Conference start detected:', conferenceId);
        
        // Show invitation modal if not the sender
        if (message.senderId !== currentUser.id) {
            const sender = users.find(u => u.id === message.senderId);
            showIncomingCallModal(sender?.name || 'Пользователь', conferenceId, message.chatId);
        }
    }
    
    // Show notification
    if (message.senderId !== currentUser.id && (!currentChat || currentChat.id !== message.chatId)) {
        const sender = users.find(u => u.id === message.senderId);
        showNotification(sender?.name || 'Новое сообщение', message.text);
    }
}

function sendMessage() {
    const textarea = document.getElementById('messageTextarea');
    const text = textarea.value.trim();
    
    if (!text || !currentChat) return;
    
    socket.emit('message:send', {
        chatId: currentChat.id,
        text,
        type: 'text'
    });
    
    textarea.value = '';
    textarea.style.height = 'auto';
    
    // Stop typing indicator
    socket.emit('typing:stop', currentChat.id);
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
    // Check if chat already exists
    const existingChat = chats.find(c => 
        c.type === 'private' && 
        c.participants.includes(userId) && 
        c.participants.includes(currentUser.id)
    );
    
    if (existingChat) {
        openChat(existingChat);
        closeModal();
        return;
    }
    
    // Create new chat
    try {
        const chat = await apiCall('/api/chats', {
            method: 'POST',
            body: JSON.stringify({
                type: 'private',
                participants: [userId]
            })
        });
        
        chats.push(chat);
        renderChats();
        openChat(chat);
        closeModal();
    } catch (error) {
        showToast('Ошибка создания чата', 'error');
    }
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
    
    // Play ringtone
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
        
        // Add click handlers for private chat
        usersList.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                createPrivateChat(userId);
            });
        });
    }
    
    if (groupUsersList) {
        groupUsersList.innerHTML = usersHtml;
        
        // Add click handlers for group chat (select/deselect)
        groupUsersList.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', () => {
                item.classList.toggle('selected');
                updateSelectedUsers();
            });
        });
    }
}

// Create private chat with user
async function createPrivateChat(userId) {
    try {
        const user = users.find(u => u.id === userId);
        if (!user) {
            showToast('Пользователь не найден', 'error');
            return;
        }
        
        // Check if chat already exists
        const existingChat = chats.find(c => 
            c.type === 'private' && 
            c.participants.some(p => p.id === userId)
        );
        
        if (existingChat) {
            // Open existing chat
            closeModal();
            openChat(existingChat);
            showToast('Чат открыт', 'success');
            return;
        }
        
        // Create new chat
        const response = await apiCall('/api/chats', {
            method: 'POST',
            body: JSON.stringify({
                type: 'private',
                participants: [userId]
            })
        });
        
        closeModal();
        
        // Wait for socket to receive chat
        setTimeout(() => {
            const newChat = chats.find(c => c.id === response.chat.id);
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

// Update selected users for group chat
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

// Remove selected user from group
window.removeSelectedUser = function(userId) {
    const userItem = document.querySelector(`#groupUsersList .user-item[data-user-id="${userId}"]`);
    if (userItem) {
        userItem.classList.remove('selected');
        updateSelectedUsers();
    }
};

// Create group chat
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
        
        const response = await apiCall('/api/chats', {
            method: 'POST',
            body: JSON.stringify({
                type: 'group',
                name: groupName,
                participants: participantIds
            })
        });
        
        closeModal();
        
        // Clear form
        document.getElementById('groupNameInput').value = '';
        selectedUserItems.forEach(item => item.classList.remove('selected'));
        updateSelectedUsers();
        
        // Wait for socket to receive chat
        setTimeout(() => {
            const newChat = chats.find(c => c.id === response.chat.id);
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

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
}

// ==================== Event Listeners ====================
function setupEventListeners() {
    // Auth forms
    document.getElementById('loginBtn').addEventListener('click', () => {
        const username = document.getElementById('loginUsername').value;
        const password = document.getElementById('loginPassword').value;
        login(username, password);
    });
    
    // Login on Enter key
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
    
    // Register on Enter key
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
    
    // Tabs
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
    
    // New chat
    const newChatBtns = [
        document.getElementById('newChatBtn'),
        document.getElementById('welcomeNewChat')
    ];
    
    newChatBtns.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', () => {
                document.getElementById('newChatModal').classList.add('active');
                // Render users list when modal opens
                if (typeof renderUsersList === 'function') {
                    renderUsersList();
                } else {
                    // Fallback render
                    setTimeout(() => {
                        if (typeof renderUsersList === 'function') {
                            renderUsersList();
                        }
                    }, 100);
                }
            });
        }
    });
    
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    document.getElementById('cancelChatBtn').addEventListener('click', closeModal);
    
    // Chat type selector
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            
            // Update active button
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Show/hide content
            document.getElementById('privateChat').style.display = type === 'private' ? 'block' : 'none';
            document.getElementById('groupChat').style.display = type === 'group' ? 'block' : 'none';
            
            // Render users list
            renderUsersList();
        });
    });
    
    // Create chat button
    document.getElementById('createChatBtn').addEventListener('click', () => {
        const activeType = document.querySelector('.type-btn.active').dataset.type;
        
        if (activeType === 'private') {
            // For private chat, user should click on user in list
            showToast('Выберите пользователя из списка', 'info');
        } else {
            // Create group chat
            createGroupChat();
        }
    });
    
    // Search users in modal
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

    
    // Message input
    const textarea = document.getElementById('messageTextarea');
    let typingTimeout;
    
    textarea.addEventListener('input', () => {
        // Auto-resize
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        
        // Typing indicator
        if (currentChat) {
            socket.emit('typing:start', currentChat.id);
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                socket.emit('typing:stop', currentChat.id);
            }, 1000);
        }
    });
    
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    document.getElementById('sendBtn').addEventListener('click', sendMessage);
    
    // Video/Audio calls
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
    
    // Set user info
    document.getElementById('currentUserName').textContent = currentUser.name;
    const avatar = document.getElementById('currentUserAvatar');
    avatar.style.background = generateGradient(currentUser.name);
    avatar.innerHTML = getUserInitials(currentUser.name);
    
    // Initialize socket
    initSocket();
    
    // Setup listeners
    setupEventListeners();
    
    // Initialize UI components after a short delay
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
    
    // Request notifications
    setTimeout(requestNotificationPermission, 2000);
}

// ==================== App Start ====================
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
    
    if (token && userStr) {
        try {
            currentUser = JSON.parse(userStr);
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