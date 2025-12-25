// ==================== Modal Management ====================
let selectedChatType = 'private';
let selectedUsers = new Set();

function initModals() {
    // Chat type selector
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            selectedChatType = btn.dataset.type;
            
            document.getElementById('privateChat').style.display = 
                selectedChatType === 'private' ? 'block' : 'none';
            document.getElementById('groupChat').style.display = 
                selectedChatType === 'group' ? 'block' : 'none';
            
            selectedUsers.clear();
            updateSelectedUsers();
        });
    });
    
    // User search for private chat
    document.getElementById('userSearchInput').addEventListener('input', (e) => {
        filterUsers(e.target.value, 'usersList');
    });
    
    // User search for group chat
    document.getElementById('groupUserSearchInput').addEventListener('input', (e) => {
        filterUsers(e.target.value, 'groupUsersList');
    });
    
    // Create chat button
    document.getElementById('createChatBtn').addEventListener('click', createNewChat);
    
    // Close modal on background click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    });
}

function filterUsers(query, listId) {
    const list = document.getElementById(listId);
    const items = list.querySelectorAll('.user-item');
    
    items.forEach(item => {
        const name = item.dataset.userName.toLowerCase();
        const username = item.dataset.username.toLowerCase();
        const searchQuery = query.toLowerCase();
        
        if (name.includes(searchQuery) || username.includes(searchQuery)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function renderUsersList() {
    const privateList = document.getElementById('usersList');
    const groupList = document.getElementById('groupUsersList');
    
    const filteredUsers = users.filter(u => u.id !== currentUser.id);
    
    const userHTML = filteredUsers.map(user => {
        const isOnline = onlineUsers.has(user.id);
        return `
            <div class="user-item" data-user-id="${user.id}" data-user-name="${user.name}" data-username="${user.username}">
                <div class="chat-avatar ${isOnline ? 'online' : ''}" style="background: ${generateGradient(user.name)}">
                    ${user.avatar ? `<img src="${user.avatar}" alt="${user.name}">` : getUserInitials(user.name)}
                </div>
                <div class="chat-details">
                    <div class="chat-name">${user.name}</div>
                    <div class="chat-preview">@${user.username}</div>
                </div>
            </div>
        `;
    }).join('');
    
    privateList.innerHTML = userHTML;
    groupList.innerHTML = userHTML;
    
    // Add click handlers for private chat
    privateList.querySelectorAll('.user-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.dataset.userId;
            startChatWithUser(userId);
        });
    });
    
    // Add click handlers for group chat
    groupList.querySelectorAll('.user-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.dataset.userId;
            toggleUserSelection(userId, item);
        });
    });
}

function toggleUserSelection(userId, element) {
    if (selectedUsers.has(userId)) {
        selectedUsers.delete(userId);
        element.classList.remove('selected');
    } else {
        selectedUsers.add(userId);
        element.classList.add('selected');
    }
    updateSelectedUsers();
}

function updateSelectedUsers() {
    const container = document.getElementById('selectedUsers');
    
    if (selectedUsers.size === 0) {
        container.innerHTML = '<p style="color: var(--text-secondary); padding: 12px;">Выберите участников группы</p>';
        return;
    }
    
    const html = Array.from(selectedUsers).map(userId => {
        const user = users.find(u => u.id === userId);
        if (!user) return '';
        
        return `
            <div class="selected-user-chip">
                <span>${user.name}</span>
                <button class="remove-user" data-user-id="${userId}">
                    <span class="material-icons">close</span>
                </button>
            </div>
        `;
    }).join('');
    
    container.innerHTML = `<div class="selected-users-container">${html}</div>`;
    
    // Add remove handlers
    container.querySelectorAll('.remove-user').forEach(btn => {
        btn.addEventListener('click', () => {
            const userId = btn.dataset.userId;
            selectedUsers.delete(userId);
            
            // Update UI
            const item = document.querySelector(`#groupUsersList .user-item[data-user-id="${userId}"]`);
            if (item) item.classList.remove('selected');
            
            updateSelectedUsers();
        });
    });
}

async function createNewChat() {
    if (selectedChatType === 'private') {
        // Private chats are created on user click
        showToast('Выберите пользователя для начала чата', 'warning');
        return;
    }
    
    if (selectedChatType === 'group') {
        if (selectedUsers.size === 0) {
            showToast('Добавьте участников группы', 'warning');
            return;
        }
        
        const groupName = document.getElementById('groupNameInput').value.trim();
        if (!groupName) {
            showToast('Введите название группы', 'warning');
            return;
        }
        
        try {
            const chat = await apiCall('/api/chats', {
                method: 'POST',
                body: JSON.stringify({
                    type: 'group',
                    name: groupName,
                    participants: Array.from(selectedUsers)
                })
            });
            
            chats.push(chat);
            renderChats();
            openChat(chat);
            closeModal();
            
            // Reset form
            document.getElementById('groupNameInput').value = '';
            selectedUsers.clear();
            updateSelectedUsers();
            
            showToast('Группа создана');
        } catch (error) {
            showToast('Ошибка создания группы', 'error');
        }
    }
}

// ==================== Search Functionality ====================
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        
        if (document.querySelector('.tab-btn.active').dataset.tab === 'chats') {
            filterChats(query);
        } else {
            filterContacts(query);
        }
    });
}

function filterChats(query) {
    const chatItems = document.querySelectorAll('#chatsList .chat-item');
    
    chatItems.forEach(item => {
        const chatId = item.dataset.chatId;
        const chat = chats.find(c => c.id === chatId);
        
        if (!chat) return;
        
        const otherParticipant = chat.type === 'private' 
            ? users.find(u => chat.participants.includes(u.id) && u.id !== currentUser.id)
            : null;
        
        const chatName = (chat.name || otherParticipant?.name || '').toLowerCase();
        const lastMessage = (chat.lastMessage?.text || '').toLowerCase();
        
        if (chatName.includes(query) || lastMessage.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterContacts(query) {
    const contactItems = document.querySelectorAll('#contactsList .chat-item');
    
    contactItems.forEach(item => {
        const userId = item.dataset.userId;
        const user = users.find(u => u.id === userId);
        
        if (!user) return;
        
        const name = user.name.toLowerCase();
        const username = user.username.toLowerCase();
        
        if (name.includes(query) || username.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// ==================== Notification Banner ====================
function initNotificationBanner() {
    if ('Notification' in window && Notification.permission === 'default') {
        const banner = document.getElementById('notificationBanner');
        banner.style.display = 'flex';
        
        document.getElementById('enableNotifications').addEventListener('click', async () => {
            await requestNotificationPermission();
            banner.style.display = 'none';
        });
        
        document.getElementById('dismissNotifications').addEventListener('click', () => {
            banner.style.display = 'none';
        });
    }
}

// ==================== Keyboard Shortcuts ====================
function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Cmd/Ctrl + K - Focus search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('searchInput').focus();
        }
        
        // Cmd/Ctrl + N - New chat
        if ((e.metaKey || e.ctrlKey) && e.key === 'n') {
            e.preventDefault();
            document.getElementById('newChatBtn').click();
        }
        
        // Escape - Close modal
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// ==================== Mobile Support ====================
function initMobileSupport() {
    let touchStartX = 0;
    let touchEndX = 0;
    
    const sidebar = document.querySelector('.sidebar');
    
    document.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    document.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 100;
        
        if (touchEndX < touchStartX - swipeThreshold) {
            // Swipe left - hide sidebar
            sidebar.classList.remove('active');
        }
        
        if (touchEndX > touchStartX + swipeThreshold) {
            // Swipe right - show sidebar
            sidebar.classList.add('active');
        }
    }
}

// ==================== Online Status Animation ====================
function animateOnlineStatus(userId, isOnline) {
    const avatars = document.querySelectorAll(`[data-user-id="${userId}"] .chat-avatar`);
    
    avatars.forEach(avatar => {
        if (isOnline) {
            avatar.classList.add('online');
            avatar.style.animation = 'pulse 0.5s ease-out';
        } else {
            avatar.classList.remove('online');
        }
        
        setTimeout(() => {
            avatar.style.animation = '';
        }, 500);
    });
}

// ==================== Auto-scroll Messages ====================
function setupAutoScroll() {
    const container = document.getElementById('messagesContainer');
    let isUserScrolling = false;
    let scrollTimeout;
    
    container.addEventListener('scroll', () => {
        const { scrollTop, scrollHeight, clientHeight } = container;
        const isAtBottom = scrollHeight - scrollTop - clientHeight < 100;
        
        isUserScrolling = !isAtBottom;
        
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            isUserScrolling = false;
        }, 1000);
    });
    
    // Auto-scroll on new message if at bottom
    const observer = new MutationObserver(() => {
        if (!isUserScrolling) {
            container.scrollTop = container.scrollHeight;
        }
    });
    
    observer.observe(document.getElementById('messagesList'), {
        childList: true
    });
}

// ==================== Link Preview ====================
function detectLinks(text) {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return text.match(urlRegex);
}

function addLinkPreview(message) {
    const links = detectLinks(message.text);
    if (!links) return;
    
    // Implement link preview functionality
    // This would require a backend endpoint to fetch metadata
}

// ==================== Initialize All ====================
document.addEventListener('DOMContentLoaded', () => {
    // Таймаут для инициализации после загрузки основного app.js
    setTimeout(() => {
        if (currentUser) {
            initModals();
            initSearch();
            initNotificationBanner();
            initKeyboardShortcuts();
            initMobileSupport();
            setupAutoScroll();
        }
    }, 100);
});

// ==================== Export Functions ====================
window.renderUsersList = renderUsersList;
window.initModals = initModals;

// ==================== CSS for Additional Features ====================
const additionalStyles = `
.user-item {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: background var(--transition-fast);
    border-radius: var(--radius-md);
    margin: 4px 8px;
}

.user-item:hover {
    background: var(--divider);
}

.user-item.selected {
    background: rgba(26, 115, 232, 0.1);
    border: 1px solid var(--primary-color);
}

.selected-users-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
}

.selected-user-chip {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: var(--primary-color);
    color: white;
    border-radius: 16px;
    font-size: 13px;
}

.selected-user-chip .remove-user {
    border: none;
    background: transparent;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 0;
    margin-left: 4px;
}

.selected-user-chip .material-icons {
    font-size: 16px;
}

.notification-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: var(--primary-color);
    color: white;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1001;
    box-shadow: var(--shadow-md);
}

.notification-banner button {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 6px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.notification-banner button:hover {
    background: rgba(255, 255, 255, 0.3);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.chat-type-selector {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.type-btn {
    flex: 1;
    padding: 16px;
    border: 2px solid var(--border-color);
    background: var(--surface);
    border-radius: var(--radius-lg);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    transition: all var(--transition-fast);
}

.type-btn:hover {
    border-color: var(--primary-light);
    background: var(--divider);
}

.type-btn.active {
    border-color: var(--primary-color);
    background: rgba(26, 115, 232, 0.05);
}

.type-btn .material-icons {
    font-size: 32px;
    color: var(--text-secondary);
}

.type-btn.active .material-icons {
    color: var(--primary-color);
}

.search-users {
    margin-bottom: 16px;
}

.search-users input {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 14px;
    background: var(--background);
    color: var(--text-primary);
}

.search-users input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.users-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    margin-top: 8px;
}
`;

// Add styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);