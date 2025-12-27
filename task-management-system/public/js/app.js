// ==================== Global Variables ====================
let socket = null;
let currentUser = null;
let currentChat = null;
let users = [];
let chats = [];
let onlineUsers = new Set();
let pendingChatUser = null;
let pendingStatusUpdates = new Map();


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



// ==================== SIDEBAR COLLAPSE ==================== 

let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

function initSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    
    if (!sidebar || !toggleBtn) return;
    
    // Применяем сохраненное состояние
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
    }
    
    // Обработчик клика
    toggleBtn.addEventListener('click', () => {
        sidebarCollapsed = !sidebarCollapsed;
        sidebar.classList.toggle('collapsed');
        
        // Сохраняем состояние
        localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
        
        // Обновляем тултип
        toggleBtn.title = sidebarCollapsed ? 'Развернуть панель' : 'Свернуть панель';
    });
}




function addTooltipsToSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    // Для кнопок табов
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const text = btn.querySelector('span:not(.material-icons)')?.textContent;
        if (text) {
            btn.setAttribute('data-tooltip', text);
        }
    });
    
    // Для чатов
    document.querySelectorAll('.chat-item').forEach(item => {
        const name = item.querySelector('.chat-name')?.textContent;
        if (name) {
            item.setAttribute('data-tooltip', name);
        }
    });
    
    // Для контактов
    document.querySelectorAll('.contact-item').forEach(item => {
        const name = item.querySelector('.contact-name, h4')?.textContent;
        if (name) {
            item.setAttribute('data-tooltip', name);
        }
    });
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
            loadUsers().then(() => {
        loadChats();
    });
            
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
    console.log('👤 User online status changed:', data.userId, 'Online:', data.online);
    
    if (data.online) {
        onlineUsers.add(data.userId);
    } else {
        onlineUsers.delete(data.userId);
    }
    // Обновляем статус в заголовке чата СРАЗУ
    updateChatHeaderStatus(data.userId, data.online);
    // ВАЖНО: Обновляем все места сразу
    updateOnlineStatus();
});

    
socket.on('users:list', (userIds) => {
    console.log('👥 Online users list received:', userIds.length, 'users');
    onlineUsers = new Set(userIds);
    
    // Обновляем заголовок чата если он открыт
    if (currentChat && currentChat.type === 'private') {
        const otherUserId = currentChat.participants.find(id => id !== currentUser.id);
        if (otherUserId) {
            updateChatHeaderStatus(otherUserId, onlineUsers.has(otherUserId));
        }
    } else if (pendingChatUser && pendingChatUser.id) {
        updateChatHeaderStatus(pendingChatUser.id, onlineUsers.has(pendingChatUser.id));
    }
    
    // Обновляем все остальное
    updateOnlineStatus();
});

    socket.on('chat:created', (chat) => {
        console.log('📨 Received chat:created event:', chat);
        
        try {
            // Проверяем что чат еще не добавлен
            const exists = chats.find(c => c.id === chat.id);
            if (!exists) {
                chats.push(chat);
                console.log('✅ Chat added to local array');
                
                // Обновляем список
                renderChats();
            } else {
                console.log('⚠️ Chat already exists, skipping');
            }
        } catch (error) {
            console.error('❌ Error handling chat:created:', error);
        }
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
    console.log('📨 Received message:new event:', message);
    
    // Если это наше сообщение и есть tempId, заменяем временное на реальное
    if (message.senderId === currentUser.id && message.tempId) {
        const tempMessageEl = document.querySelector(`[data-message-id="${message.tempId}"]`);
        
        if (tempMessageEl) {
            console.log('🔄 Replacing temp message:', message.tempId, '→', message.id);
            
            // Заменяем data-message-id на реальный
            tempMessageEl.setAttribute('data-message-id', message.id);
            
            // Обновляем статус на "отправлено"
            const statusEl = tempMessageEl.querySelector('.message-status');
            if (statusEl && currentChat && currentChat.type === 'private') {
                statusEl.className = 'message-status sent';
                statusEl.textContent = '✓';
            }
            
            // НОВОЕ: Применяем отложенные обновления статуса
            if (pendingStatusUpdates.has(message.id)) {
                console.log('📦 Applying pending status update for:', message.id);
                const pendingUpdate = pendingStatusUpdates.get(message.id);
                
                if (statusEl) {
                    if (pendingUpdate.read) {
                        statusEl.className = 'message-status read';
                        statusEl.textContent = '✓✓';
                        console.log('✅ Applied pending READ status');
                    } else if (pendingUpdate.delivered) {
                        statusEl.className = 'message-status delivered';
                        statusEl.textContent = '✓✓';
                        console.log('✅ Applied pending DELIVERED status');
                    }
                }
                
                pendingStatusUpdates.delete(message.id);
            }
            
            console.log('✅ Temp message replaced with real ID');
            return; // Не добавляем новое сообщение
        }
    }
    
    handleNewMessage(message);
});


// В начале файла с глобальными переменными
let pendingStatusUpdates = new Map(); // Храним обновления которые пришли до рендера сообщения

// Обработчик обновления статуса
socket.on('message:status-updated', (data) => {
    console.log('📊 Message status updated:', data);
    
    const { messageId, delivered, read } = data;
    
    // Находим сообщение в DOM
    const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
    
    if (!messageEl) {
        console.log('⚠️ Message element not found, saving to pending updates:', messageId);
        // Сохраняем обновление для применения позже
        pendingStatusUpdates.set(messageId, { delivered, read });
        return;
    }
    
    // НОВОЕ: Проверяем что это наше сообщение (только для них показываем статус)
    const isSentMessage = messageEl.classList.contains('sent');
    if (!isSentMessage) {
        console.log('⏭️ Skipping status update for received message:', messageId);
        return;
    }
    
    const statusEl = messageEl.querySelector('.message-status');
    if (!statusEl) {
        console.log('⚠️ Status element not found for message:', messageId);
        return;
    }
    
    console.log('Current status:', statusEl.className, statusEl.textContent);
    
    // Обновляем иконку статуса
    if (read) {
        statusEl.className = 'message-status read';
        statusEl.textContent = '✓✓';
        console.log('✅ Updated to READ status');
    } else if (delivered) {
        statusEl.className = 'message-status delivered';
        statusEl.textContent = '✓✓';
        console.log('✅ Updated to DELIVERED status');
    }
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
    if (!chatsList) {
        console.error('❌ Chats list container not found');
        return;
    }
    addTooltipsToSidebar();
    console.log('🎨 Rendering chats list, count:', chats.length);
    console.log('Users loaded:', users.length);

    if (chats.length === 0) {
        chatsList.innerHTML = `
            <div class="empty-state" style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                <span class="material-icons" style="font-size: 64px; opacity: 0.3;">chat_bubble_outline</span>
                <p>Нет активных чатов</p>
            </div>
        `;
        return;
    }

    // Сортируем по последнему обновлению
    const sortedChats = [...chats].sort((a, b) => 
        new Date(b.updatedAt || b.createdAt) - new Date(a.updatedAt || a.createdAt)
    );

    chatsList.innerHTML = sortedChats.map(chat => {
        let chatName = 'Чат';
        let chatAvatar = '';
        let chatSubtitle = '';
        
        if (chat.type === 'task') {
            // Чат задачи
            chatName = chat.name || 'Чат задачи';
            chatAvatar = `
                <div class="user-avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <span class="material-icons">task_alt</span>
                </div>
            `;
            chatSubtitle = 'Чат задачи';
        } else if (chat.type === 'group') {
            // Групповой чат
            chatName = chat.name || 'Группа';
            chatAvatar = `
                <div class="user-avatar" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <span class="material-icons">group</span>
                </div>
            `;
            chatSubtitle = `${chat.participants ? chat.participants.length : 0} участников`;
        } else {
            // Личный чат
            const otherUserId = chat.participants ? chat.participants.find(id => id !== currentUser.id) : null;
            
            if (!otherUserId) {
                console.warn('⚠️ No other user found in chat:', chat.id, 'Participants:', chat.participants);
                chatName = 'Неизвестный чат';
                chatAvatar = `
                    <div class="user-avatar" style="background: linear-gradient(135deg, #ccc 0%, #999 100%);">
                        <span class="material-icons">person</span>
                    </div>
                `;
                chatSubtitle = 'Пользователь не найден';
            } else {
                const otherUser = users.find(u => u.id === otherUserId);
                
                if (otherUser) {
                    chatName = otherUser.name;
                    
                    // Проверяем статус онлайн
                    const isOnline = onlineUsers.has(otherUser.id);
                    
                    if (otherUser.avatar) {
                        chatAvatar = `
                            <div class="user-avatar ${isOnline ? 'online' : ''}">
                                <img src="${otherUser.avatar}" alt="${otherUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            </div>
                        `;
                    } else {
                        chatAvatar = `
                            <div class="user-avatar ${isOnline ? 'online' : ''}" style="background: ${generateGradient(otherUser.name)}">
                                ${getUserInitials(otherUser.name)}
                            </div>
                        `;
                    }
                    chatSubtitle = isOnline ? 'В сети' : 'Не в сети';
                } else {
                    // ИСПРАВЛЕНО: Пользователь не загружен, но мы знаем его ID
                    console.warn('⚠️ User not found in users array:', otherUserId);
                    console.log('Available users:', users.map(u => ({ id: u.id, name: u.name })));
                    
                    chatName = 'Загрузка...';
                    chatAvatar = `
                        <div class="user-avatar" style="background: ${generateGradient(otherUserId)}">
                            ${getUserInitials('U')}
                        </div>
                    `;
                    chatSubtitle = 'Загрузка данных...';
                    
                    // НОВОЕ: Попробуем загрузить пользователя
                    loadMissingUser(otherUserId);
                }
            }
        }

        return `
            <div class="chat-item ${currentChat && currentChat.id === chat.id ? 'active' : ''}" 
                 data-chat-id="${chat.id}" 
                 onclick="openChatById('${chat.id}')">
                ${chatAvatar}
                <div class="chat-item-content">
                    <div class="chat-item-header">
                        <h4>${chatName}</h4>
                        <span class="chat-time">${formatTime(chat.updatedAt || chat.createdAt)}</span>
                    </div>
                    <p class="chat-preview">${chatSubtitle}</p>
                </div>
            </div>
        `;
    }).join('');


}


// Загружаем отсутствующего пользователя
async function loadMissingUser(userId) {
    if (!userId) return;
    
    // Проверяем что уже не загружаем этого пользователя
    if (window.loadingUsers && window.loadingUsers.has(userId)) {
        console.log('Already loading user:', userId);
        return;
    }
    
    if (!window.loadingUsers) {
        window.loadingUsers = new Set();
    }
    
    window.loadingUsers.add(userId);
    
    try {
        console.log('📥 Loading missing user:', userId);
        
        // Загружаем конкретного пользователя
        const user = await apiCall(`/api/users/${userId}`);
        
        if (user) {
            // Проверяем что пользователь еще не в массиве
            const exists = users.find(u => u.id === user.id);
            if (!exists) {
                users.push(user);
                console.log('✅ Added missing user:', user.name);
                
                // Перерисовываем чаты
                renderChats();
                renderContacts();
            }
        }
    } catch (error) {
        console.error('❌ Error loading missing user:', error);
    } finally {
        window.loadingUsers.delete(userId);
    }
}

// Глобальная функция для открытия чата
window.openChatById = function(chatId) {
    console.log('Opening chat by ID:', chatId);
    const chat = chats.find(c => c.id === chatId);
    if (chat) {
        openChat(chat);
    } else {
        console.error('Chat not found:', chatId);
    }
};

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

    addTooltipsToSidebar();
}


function updateChatHeaderStatus(userId, isOnline) {
    // Проверяем что этот пользователь сейчас открыт в чате
    let isCurrentChatUser = false;
    
    if (currentChat && currentChat.type === 'private') {
        const otherUserId = currentChat.participants.find(id => id !== currentUser.id);
        isCurrentChatUser = (otherUserId === userId);
    } else if (pendingChatUser && pendingChatUser.id === userId) {
        isCurrentChatUser = true;
    }
    
    if (!isCurrentChatUser) {
        console.log('⏭️ User not in current chat, skipping header update');
        return;
    }
    
    const chatStatus = document.getElementById('chatStatus');
    if (chatStatus) {
        chatStatus.textContent = isOnline ? 'В сети' : 'Не в сети';
        chatStatus.className = isOnline ? 'online' : '';
        console.log('✅ Updated chat header status:', userId, '→', isOnline ? 'online' : 'offline');
    } else {
        console.warn('⚠️ chatStatus element not found');
    }
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
    console.log('📱 Opening chat:', chat);
    console.log('Current user:', currentUser);
    console.log('Chat participants:', chat.participants);
    console.log('User is participant:', chat.participants?.includes(currentUser.id));
    
    if (!chat || !chat.id) {
        console.error('❌ Invalid chat object:', chat);
        return;
    }
    
    currentChat = chat;
    
    // Hide welcome screen and show chat container
    const welcomeScreen = document.getElementById('welcomeScreen');
    const chatContainer = document.getElementById('chatContainer');
    
    if (welcomeScreen) welcomeScreen.style.display = 'none';
    if (chatContainer) chatContainer.style.display = 'flex';
    
    // Update chat header
    const chatName = document.getElementById('chatName');
    const chatStatus = document.getElementById('chatStatus');
    const chatAvatar = document.querySelector('.chat-info .chat-avatar');
    
    // ВАЖНО: Сначала проверяем тип чата
    if (chat.type === 'task') {
        // Чат задачи
        console.log('📋 Displaying task chat');
        if (chatName) chatName.textContent = chat.name || 'Чат задачи';
        if (chatStatus) chatStatus.textContent = 'Чат задачи';
        if (chatAvatar) {
            chatAvatar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            chatAvatar.innerHTML = '<span class="material-icons">task_alt</span>';
        }
    } else if (chat.type === 'group') {
        // Групповой чат
        console.log('👥 Displaying group chat');
        if (chatName) chatName.textContent = chat.name || 'Группа';
        if (chatStatus) chatStatus.textContent = `${chat.participants.length} участников`;
        if (chatAvatar) {
            chatAvatar.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
            chatAvatar.innerHTML = '<span class="material-icons">group</span>';
        }
    } else {
        // Личный чат
        console.log('💬 Displaying private chat');
        const otherUserId = chat.participants.find(id => id !== currentUser.id);
        const otherUser = users.find(u => u.id === otherUserId);
        
        if (otherUser) {
             if (chatName) chatName.textContent = otherUser.name;
            
            // ИСПРАВЛЕНО: Проверяем статус онлайн
            const isOnline = onlineUsers.has(otherUser.id);
            console.log('User online status:', otherUser.id, '=', isOnline);
            
            if (chatStatus) {
                chatStatus.textContent = isOnline ? 'В сети' : 'Не в сети';
                chatStatus.className = isOnline ? 'online' : '';
                console.log('✅ Set chat status:', chatStatus.textContent);
            }
            if (chatAvatar) {
                if (otherUser.avatar) {
                    chatAvatar.style.background = 'transparent';
                    chatAvatar.innerHTML = `<img src="${otherUser.avatar}" alt="${otherUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                } else {
                    chatAvatar.style.background = generateGradient(otherUser.name);
                    chatAvatar.innerHTML = getUserInitials(otherUser.name);
                }
            }
        }
    }
    
    // Load messages
    try {
        console.log('📨 Loading messages for chat:', chat.id);
        const msgs = await apiCall(`/api/messages/${chat.id}`);
        console.log('✅ Loaded', msgs.length, 'messages');
        renderMessages(msgs);


        if (socket && chat.type === 'private') {
            console.log('👁️ Marking messages as read in chat:', chat.id);
            
            // Отправляем события для каждого непрочитанного сообщения
            msgs.forEach(msg => {
                if (msg.senderId !== currentUser.id && !msg.read) {
                    socket.emit('message:delivered', {
                        messageId: msg.id,
                        chatId: chat.id
                    });
                    
                    socket.emit('message:read', {
                        messageId: msg.id,
                        chatId: chat.id
                    });
                }
            });
        }
        
        // Scroll to bottom
        setTimeout(() => {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }, 100);
    } catch (error) {
        console.error('❌ Error loading messages:', error);
        console.error('Error details:', error.message);
        
        // Показываем пустой чат вместо ошибки
        console.log('Showing empty chat due to error');
        renderMessages([]);
    }
    
    // Update active chat in sidebar
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.toggle('active', item.dataset.chatId === chat.id);
    });
    
    // Join socket room for this chat
    if (socket) {
        console.log('🔌 Joining socket room:', chat.id);
        socket.emit('chat:join', chat.id);
    }
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
        
        const filesHtml = msg.metadata && msg.metadata.files ? renderMessageFiles(msg.metadata.files) : '';
        const hasText = msg.text && msg.text.trim().length > 0;
        
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
        
        // ИСПРАВЛЕНО: Проверяем отложенные обновления
        let statusIcon = '';
        if (isSent && currentChat && currentChat.type === 'private') {
            // Проверяем есть ли отложенное обновление
            const pendingUpdate = pendingStatusUpdates.get(msg.id);
            
            if (pendingUpdate) {
                console.log('📦 Found pending update for message:', msg.id, pendingUpdate);
                
                if (pendingUpdate.read) {
                    statusIcon = '<span class="message-status read">✓✓</span>';
                    msg.read = true; // Обновляем объект
                } else if (pendingUpdate.delivered) {
                    statusIcon = '<span class="message-status delivered">✓✓</span>';
                    msg.delivered = true; // Обновляем объект
                }
                
                // Удаляем из очереди
                pendingStatusUpdates.delete(msg.id);
            } else {
                // Используем статус из сообщения
                if (msg.read) {
                    statusIcon = '<span class="message-status read">✓✓</span>';
                } else if (msg.delivered) {
                    statusIcon = '<span class="message-status delivered">✓✓</span>';
                } else {
                    statusIcon = '<span class="message-status sent">✓</span>';
                }
            }
        }
        
        return `
            <div class="message ${isSent ? 'sent' : 'received'} ${isSystem ? 'system' : ''}" data-message-id="${msg.id}">
                ${messageAvatarHtml}
                <div class="message-content">
                    ${isGroupChat && !isSent && !isSystem ? `
                        <div class="message-sender-name" data-sender-id="${msg.senderId}">${sender?.name || 'Пользователь'}</div>
                    ` : ''}
                    ${filesHtml}
                    ${hasText ? `<div class="message-bubble ${isSystem ? 'system-bubble' : ''}">${msg.text}</div>` : ''}
                    <div class="message-time-status">
                        <span class="message-time" data-timestamp="${msg.createdAt}">${formatTime(msg.createdAt)}</span>
                        ${statusIcon}
                    </div>
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
        const hasText = message.text && message.text.trim().length > 0;
        
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
        
        // ИСПРАВЛЕНО: Проверяем отложенные обновления
        let statusIcon = '';
        if (isSent && currentChat.type === 'private') {
            const pendingUpdate = pendingStatusUpdates.get(message.id);
            
            if (pendingUpdate) {
                console.log('📦 Applying pending update to new message:', message.id);
                
                if (pendingUpdate.read) {
                    statusIcon = '<span class="message-status read">✓✓</span>';
                } else if (pendingUpdate.delivered) {
                    statusIcon = '<span class="message-status delivered">✓✓</span>';
                }
                
                pendingStatusUpdates.delete(message.id);
            } else {
                if (message.read) {
                    statusIcon = '<span class="message-status read">✓✓</span>';
                } else if (message.delivered) {
                    statusIcon = '<span class="message-status delivered">✓✓</span>';
                } else {
                    statusIcon = '<span class="message-status sent">✓</span>';
                }
            }
        }
        
        const messageHtml = `
            <div class="message ${isSent ? 'sent' : 'received'} ${isSystem ? 'system' : ''}" data-message-id="${message.id}">
                ${messageAvatarHtml}
                <div class="message-content">
                    ${isGroupChat && !isSent && !isSystem ? `
                        <div class="message-sender-name" data-sender-id="${message.senderId}">${sender?.name || 'Пользователь'}</div>
                    ` : ''}
                    ${filesHtml}
                    ${hasText ? `<div class="message-bubble ${isSystem ? 'system-bubble' : ''}">${message.text}</div>` : ''}
                    <div class="message-time-status">
                        <span class="message-time" data-timestamp="${message.createdAt}">${formatTime(message.createdAt)}</span>
                        ${statusIcon}
                    </div>
                </div>
            </div>
        `;
        
        messagesList.insertAdjacentHTML('beforeend', messageHtml);
        
        if (typeof makeMessageSendersClickable === 'function') {
            makeMessageSendersClickable();
        }
        
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
        
        // Если это входящее сообщение в ЛИЧНОМ чате - отправляем статусы
        if (!isSent && currentChat.type === 'private') {
            console.log('📨 Received message in private chat, sending status updates');
            
            socket.emit('message:delivered', {
                messageId: message.id,
                chatId: currentChat.id
            });
            
            socket.emit('message:read', {
                messageId: message.id,
                chatId: currentChat.id
            });
        }
    } else {
        // Сообщение пришло в другой чат
        if (message.senderId !== currentUser.id) {
            console.log('📨 Received message in background chat, sending delivered status');
            socket.emit('message:delivered', {
                messageId: message.id,
                chatId: message.chatId
            });
        }
    }
    
    // Обновляем список чатов
    const chat = chats.find(c => c.id === message.chatId);
    if (chat) {
        chat.lastMessage = message;
        if (message.senderId !== currentUser.id && (!currentChat || currentChat.id !== message.chatId)) {
            chat.unreadCount = (chat.unreadCount || 0) + 1;
        }
        renderChats();
    }
}

function sendMessage() {
    const textarea = document.getElementById('messageTextarea');
    const messageText = textarea ? textarea.value.trim() : '';
    
    console.log('📤 Sending message:', messageText);

    if (!messageText && selectedFiles.length === 0) {
        console.log('⚠️ Empty message');
        return;
    }

    if (!currentChat) {
        console.error('❌ No chat selected');
        showToast('Выберите чат', 'error');
        return;
    }

    const messageData = {
        chatId: currentChat.id,
        text: messageText,
        senderId: currentUser.id,
        senderName: currentUser.name,
        tempId: tempMessageId, // НОВОЕ: Передаем временный ID
        files: selectedFiles.map(file => ({
            name: file.name,
            size: file.size,
            url: file.url,
            type: file.type
        }))
    };

    console.log('Sending message data:', messageData);

    // НОВОЕ: Создаем временное сообщение в UI с начальным статусом
    const tempMessageId = `temp_${Date.now()}`;
    const messagesList = document.getElementById('messagesList');
    
    const filesHtml = selectedFiles.length > 0 ? renderMessageFiles(selectedFiles) : '';
    const hasText = messageText && messageText.trim().length > 0;
    
    // Временное сообщение с одной галочкой (отправлено)
    const tempMessageHtml = `
        <div class="message sent" data-message-id="${tempMessageId}">
            <div class="message-content">
                ${filesHtml}
                ${hasText ? `<div class="message-bubble">${messageText}</div>` : ''}
                <div class="message-time-status">
                    <span class="message-time" data-timestamp="${new Date().toISOString()}">${formatTime(new Date())}</span>
                    <span class="message-status sent">✓</span>
                </div>
            </div>
        </div>
    `;
    
    messagesList.insertAdjacentHTML('beforeend', tempMessageHtml);
    
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;

    // Emit to server
    socket.emit('message:send', messageData);

    // Clear input
    if (textarea) {
        textarea.value = '';
        textarea.style.height = 'auto';
    }
    
    // Clear files
    selectedFiles = [];
    const filePreview = document.getElementById('filePreview');
    if (filePreview) {
        filePreview.style.display = 'none';
    }
}

// ==================== Data Loading ====================
async function loadUsers() {
    try {
        console.log('📥 Loading users...');
        users = await apiCall('/api/users');
        console.log('✅ Loaded', users.length, 'users');
        renderContacts();
        
        // НОВОЕ: Перерисовываем чаты после загрузки пользователей
        renderChats();
    } catch (error) {
        console.error('❌ Error loading users:', error);
    }
}

async function loadChats() {
    try {
        console.log('📥 Loading chats...');
        chats = await apiCall('/api/chats');
        console.log('✅ Loaded', chats.length, 'chats');
        renderChats();
    } catch (error) {
        console.error('❌ Error loading chats:', error);
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
    console.log('🔄 Updating online status, online users:', onlineUsers.size);
    
    // 1. Обновляем в списке контактов
    renderContacts();
    
    // 2. Обновляем в списке чатов
    renderChats();
    
    // 3. Обновляем в открытом чате (заголовок)
    const chatStatus = document.getElementById('chatStatus');
    if (chatStatus) {
        let userId = null;
        let isOnline = false;
        
        // Если открыт существующий чат
        if (currentChat && currentChat.type === 'private') {
            userId = currentChat.participants.find(id => id !== currentUser.id);
            isOnline = userId ? onlineUsers.has(userId) : false;
        }
        // Если окно создания нового чата
        else if (pendingChatUser && pendingChatUser.id) {
            userId = pendingChatUser.id;
            isOnline = onlineUsers.has(userId);
        }
        
        if (userId) {
            chatStatus.textContent = isOnline ? 'В сети' : 'Не в сети';
            chatStatus.className = isOnline ? 'online' : '';
            console.log('✅ Updated chat header status in updateOnlineStatus:', userId, '→', isOnline);
        }
    }
    
    // 4. Обновляем в панели информации о чате (если открыта)
    const chatInfoPanel = document.getElementById('chatInfoPanel');
    if (chatInfoPanel && chatInfoPanel.classList.contains('open')) {
        updateChatInfoOnlineStatus();
    }
    
    // 5. Обновляем в модальных окнах создания чата (если открыты)
    updateNewChatModalOnlineStatus();
}


function updateChatInfoOnlineStatus() {
    if (!currentChat) return;
    
    const chatInfoStatus = document.getElementById('chatInfoStatus');
    const chatMembersList = document.getElementById('chatMembersList');
    
    if (currentChat.type === 'private') {
        const otherUserId = currentChat.participants.find(id => id !== currentUser.id);
        const isOnline = onlineUsers.has(otherUserId);
        
        if (chatInfoStatus) {
            chatInfoStatus.textContent = isOnline ? 'в сети' : 'не в сети';
            chatInfoStatus.className = isOnline ? 'online' : '';
        }
    } else if (currentChat.type === 'group' || currentChat.type === 'task') {
        // Обновляем статусы участников в списке
        if (chatMembersList) {
            const participants = Array.isArray(currentChat.participants) ? currentChat.participants : [];
            const members = participants
                .map(userId => users.find(u => u.id === userId))
                .filter(u => u);
            
            chatMembersList.innerHTML = members.map(member => {
                const isOnline = onlineUsers.has(member.id);
                const isSelf = member.id === currentUser.id;
                
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
        }
    }
}


function updateNewChatModalOnlineStatus() {
    // Обновляем статусы в модальном окне создания чата
    const usersList = document.getElementById('usersList');
    if (usersList && usersList.children.length > 0) {
        renderUsersList();
    }
    
    const groupUsersList = document.getElementById('groupUsersList');
    if (groupUsersList && groupUsersList.children.length > 0) {
        renderUsersList();
    }
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
    
    console.log('Opening chat info for:', chat);
    console.log('Chat participants:', chat.participants);
    
    const chatInfoAvatar = document.getElementById('chatInfoAvatar');
    const chatInfoName = document.getElementById('chatInfoName');
    const chatInfoStatus = document.getElementById('chatInfoStatus');
    const chatMembersSection = document.getElementById('chatMembersSection');
    const chatMembersList = document.getElementById('chatMembersList');
    
    const participants = Array.isArray(chat.participants) ? chat.participants : [];
    
    console.log('Processing participants:', participants);
    
    if (chat.type === 'task') {
        // ЧАТ ЗАДАЧИ
        chatInfoAvatar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        chatInfoAvatar.innerHTML = '<span class="material-icons">task_alt</span>';
        chatInfoName.textContent = chat.name || 'Чат задачи';
        chatInfoStatus.textContent = `${participants.length} ${getParticipantWord(participants.length)}`;
        
        chatMembersSection.style.display = 'block';
        
        // Получаем всех участников
        const members = participants
            .map(userId => {
                const user = users.find(u => u.id === userId);
                console.log('Looking for user:', userId, 'Found:', user);
                return user;
            })
            .filter(u => u); // Убираем undefined
        
        console.log('Members found:', members.length);
        
        if (members.length === 0) {
            chatMembersList.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--text-secondary);">Участники не найдены</p>';
            return;
        }
        
        chatMembersList.innerHTML = members.map(member => {
            const isOnline = onlineUsers.has(member.id);
            const isSelf = member.id === currentUser.id;
            
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
        
    } else if (chat.type === 'group') {
        // ГРУППОВОЙ ЧАТ
        chatInfoAvatar.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
        chatInfoAvatar.innerHTML = '<span class="material-icons">group</span>';
        chatInfoName.textContent = chat.name || 'Группа';
        chatInfoStatus.textContent = `${participants.length} ${getParticipantWord(participants.length)}`;
        
        chatMembersSection.style.display = 'block';
        
        const members = participants
            .map(userId => users.find(u => u.id === userId))
            .filter(u => u);
        
        chatMembersList.innerHTML = members.map(member => {
            const isOnline = onlineUsers.has(member.id);
            const isSelf = member.id === currentUser.id;
            
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
        // ЛИЧНЫЙ ЧАТ
        const otherParticipant = users.find(u => 
            participants.includes(u.id) && u.id !== currentUser.id
        );
        
        if (otherParticipant) {
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

function setupTasksTabHandlers() {
    // Переключение между чатами, задачами и админкой
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            
            // Убираем активный класс со всех вкладок
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Скрываем все области
            document.getElementById('chatArea').style.display = 'none';
            document.getElementById('tasksArea').style.display = 'none';
            document.getElementById('adminArea').style.display = 'none';
            document.getElementById('welcomeScreen').style.display = 'none';
            
            // Скрываем все tab-content в sidebar
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (tab === 'tasks') {
                document.getElementById('tasksArea').style.display = 'flex';
                document.getElementById('tasksTab').classList.add('active');
            } else if (tab === 'admin') {
                console.log('Opening admin panel...');
                document.getElementById('adminArea').style.display = 'flex';
                
                // Загружаем данные админки
                if (typeof loadAdminData === 'function') {
                    loadAdminData();
                } else {
                    console.error('loadAdminData function not found');
                }
            } else if (tab === 'chats') {
                document.getElementById('chatArea').style.display = 'flex';
                document.getElementById('chatsTab').classList.add('active');
                if (!currentChat) {
                    document.getElementById('welcomeScreen').style.display = 'flex';
                }
            } else if (tab === 'contacts') {
                document.getElementById('chatArea').style.display = 'flex';
                document.getElementById('contactsTab').classList.add('active');
            }
        });
    });
}

// ==================== Initialization ====================
function initApp() {
    document.getElementById('authScreen').style.display = 'none';
    document.getElementById('mainApp').style.display = 'flex';
    
    document.getElementById('currentUserName').textContent = currentUser.name;
    
    // Аватар текущего пользователя
    const avatar = document.getElementById('currentUserAvatar');
    if (currentUser.avatar) {
        avatar.style.background = 'transparent';
        avatar.innerHTML = `<img src="${currentUser.avatar}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
    } else {
        avatar.style.background = generateGradient(currentUser.name);
        avatar.innerHTML = getUserInitials(currentUser.name);
    }
    console.log(currentUser);
    // Показать кнопку админки если пользователь администратор
    if (currentUser.isAdmin) {
        const adminTab = document.getElementById('adminTab');
        if (adminTab) {
            adminTab.style.display = 'flex';
        }
    }
    
    initSocket();
    setupEventListeners();
    setupTasksTabHandlers(); 
    
    // ВАЖНО: Загружаем задачи и отделы после инициализации socket
    setTimeout(() => {
        if (typeof loadTasks === 'function') {
            loadTasks();
        }
        if (typeof loadDepartments === 'function') {
            loadDepartments();
        }
        
        // Инициализация socket listeners для задач
        if (typeof setupTasksSocketListeners === 'function') {
            setupTasksSocketListeners();
        }
        
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
    }, 500); // Увеличил задержку чтобы socket успел подключиться
    
    setTimeout(requestNotificationPermission, 2000);
}



// ==================== SOCKET LISTENERS ====================

// Будут добавлены после подключения socket в app.js
function setupTasksSocketListeners() {
    if (typeof socket === 'undefined' || !socket) {
        console.warn('Socket not available for tasks module');
        return;
    }
    
    socket.on('task:created', (task) => {
        console.log('📨 Received task:created event:', task);
        
        try {
            // Проверяем что задача еще не добавлена
            const exists = tasks.find(t => t.id === task.id);
            if (!exists) {
                tasks.push(task);
                console.log('✅ Task added to local array');
            } else {
                console.log('⚠️ Task already exists, skipping');
            }
            
            renderTasks();
            
            // Показываем уведомление только если задачу создал не текущий пользователь
            if (task.creatorId !== currentUser.id) {
                showToast('Новая задача создана: ' + task.title, 'info');
            }
        } catch (error) {
            console.error('❌ Error handling task:created:', error);
        }
    });
    
    socket.on('task:updated', (updatedTask) => {
        console.log('📨 Received task:updated event:', updatedTask);
        
        try {
            const index = tasks.findIndex(t => t.id === updatedTask.id);
            if (index !== -1) {
                tasks[index] = updatedTask;
                renderTasks();
                console.log('✅ Task updated in local array');
            } else {
                console.warn('⚠️ Updated task not found in local array');
            }
        } catch (error) {
            console.error('❌ Error handling task:updated:', error);
        }
    });
    
    socket.on('task:deleted', (taskId) => {
        console.log('📨 Received task:deleted event:', taskId);
        
        try {
            const oldLength = tasks.length;
            tasks = tasks.filter(t => t.id !== taskId);
            
            if (tasks.length < oldLength) {
                renderTasks();
                console.log('✅ Task removed from local array');
            } else {
                console.warn('⚠️ Deleted task not found in local array');
            }
        } catch (error) {
            console.error('❌ Error handling task:deleted:', error);
        }
    });
    
    socket.on('task:comment', (data) => {
        console.log('📨 Received task:comment event:', data);
        
        try {
            const task = tasks.find(t => t.id === data.taskId);
            if (task) {
                task.commentsCount = (task.commentsCount || 0) + 1;
                if (data.userId !== currentUser.id) {
                    task.hasUnread = true;
                }
                renderTasks();
                console.log('✅ Task comments updated');
            }
        } catch (error) {
            console.error('❌ Error handling task:comment:', error);
        }
    });
    
    console.log('✅ Tasks socket listeners setup');
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
     initSidebarToggle();
     addTooltipsToSidebar();
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