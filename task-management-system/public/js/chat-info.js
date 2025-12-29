// ==================== CHAT INFO PANEL FUNCTIONS ====================

function openChatInfo() {
    if (!currentChat) return;
    
    console.log('📋 Opening chat info for:', currentChat);
    
    const panel = document.getElementById('chatInfoPanel');
    const backdrop = document.getElementById('chatInfoBackdrop');
    
    // Заполняем данные
    fillChatInfo(currentChat);
    
    // Показываем панель
    panel.classList.add('open');
    backdrop.classList.add('active');
}

function closeChatInfo() {
    const panel = document.getElementById('chatInfoPanel');
    const backdrop = document.getElementById('chatInfoBackdrop');
    
    panel.classList.remove('open');
    backdrop.classList.remove('active');
}

function fillChatInfo(chat) {
    // Определяем тип чата
    const isGroup = chat.type === 'group';
    const isTask = chat.type === 'task';
    
    // Avatar
    const avatarEl = document.getElementById('chatInfoAvatar');
    const nameEl = document.getElementById('chatInfoName');
    const statusEl = document.getElementById('chatInfoStatus');
    
    if (isGroup || isTask) {
        // Групповой или task чат
        avatarEl.innerHTML = `<span class="material-icons">${isTask ? 'task' : 'group'}</span>`;
        avatarEl.style.background = generateGradient(chat.name);
        nameEl.textContent = chat.name;
        
        if (isGroup) {
            const memberCount = chat.participants?.length || 0;
            statusEl.textContent = `${memberCount} участников`;
            statusEl.classList.remove('online');
            
            // Показываем список участников
            showGroupMembers(chat.participants);
        } else {
            statusEl.textContent = 'Задача';
            statusEl.classList.remove('online');
            hideGroupMembers();
        }
    } else {
        // Приватный чат
        const otherUser = users.find(u => u.id !== currentUser.id && chat.participants.includes(u.id));
        
        if (otherUser) {
            if (otherUser.avatar) {
                avatarEl.innerHTML = `<img src="${otherUser.avatar}" alt="${otherUser.name}">`;
                avatarEl.style.background = 'transparent';
            } else {
                avatarEl.innerHTML = getUserInitials(otherUser.name);
                avatarEl.style.background = generateGradient(otherUser.name);
            }
            
            nameEl.textContent = otherUser.name;
            
            const isOnline = onlineUsers.has(otherUser.id);
            statusEl.textContent = isOnline ? 'в сети' : 'не в сети';
            statusEl.classList.toggle('online', isOnline);
        }
        
        hideGroupMembers();
    }
}

function showGroupMembers(participantIds) {
    const section = document.getElementById('chatMembersSection');
    const list = document.getElementById('chatMembersList');
    const title = document.getElementById('chatMembersTitle');
    
    if (!participantIds || participantIds.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    title.textContent = `Участники • ${participantIds.length}`;
    
    const membersHtml = participantIds.map(userId => {
        const user = users.find(u => u.id === userId);
        if (!user) return '';
        
        const isOnline = onlineUsers.has(userId);
        const isCurrent = userId === currentUser.id;
        
        let avatarContent;
        let avatarStyle = '';
        
        if (user.avatar) {
            avatarContent = `<img src="${user.avatar}" alt="${user.name}">`;
            avatarStyle = 'background: transparent;';
        } else {
            avatarContent = getUserInitials(user.name);
            avatarStyle = `background: ${generateGradient(user.name)};`;
        }
        
        return `
            <div class="member-item">
                <div class="member-avatar ${isOnline ? 'online' : ''}" style="${avatarStyle}">
                    ${avatarContent}
                </div>
                <div class="member-info">
                    <div class="member-name">${user.name}${isCurrent ? ' (Вы)' : ''}</div>
                    <div class="member-status ${isOnline ? 'online' : ''}">
                        ${isOnline ? 'в сети' : 'не в сети'}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    list.innerHTML = membersHtml;
    section.style.display = 'block';
}

function hideGroupMembers() {
    const section = document.getElementById('chatMembersSection');
    section.style.display = 'none';
}

function toggleNotifications() {
    if (!currentChat) return;
    
    // TODO: Реализовать отключение уведомлений
    showToast('Функция в разработке', 'info');
}

function openChatSearch() {
    if (!currentChat) return;
    
    // TODO: Реализовать поиск в чате
    showToast('Функция в разработке', 'info');
}

function clearChatHistory() {
    if (!currentChat) return;
    
    if (confirm('Вы уверены, что хотите очистить историю чата?')) {
        // TODO: Реализовать очистку истории
        showToast('Функция в разработке', 'info');
    }
}

function leaveChat() {
    if (!currentChat) return;
    
    if (currentChat.type === 'private') {
        showToast('Нельзя покинуть приватный чат', 'error');
        return;
    }
    
    if (confirm('Вы уверены, что хотите покинуть этот чат?')) {
        // TODO: Реализовать выход из чата
        showToast('Функция в разработке', 'info');
    }
}

// Event Listeners
document.getElementById('closeChatInfoBtn')?.addEventListener('click', closeChatInfo);
document.getElementById('chatInfoBtn')?.addEventListener('click', openChatInfo);
document.getElementById('chatInfoBackdrop')?.addEventListener('click', closeChatInfo);

// Делаем chat header кликабельным
document.getElementById('chatHeaderInfo')?.addEventListener('click', openChatInfo);

// Info items
document.getElementById('muteNotifications')?.addEventListener('click', toggleNotifications);
document.getElementById('searchInChat')?.addEventListener('click', openChatSearch);
document.getElementById('clearHistory')?.addEventListener('click', clearChatHistory);
document.getElementById('leaveChat')?.addEventListener('click', leaveChat);

// Закрытие панели по Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const panel = document.getElementById('chatInfoPanel');
        if (panel && panel.classList.contains('open')) {
            closeChatInfo();
        }
    }
});

// Make functions globally available
window.openChatInfo = openChatInfo;
window.closeChatInfo = closeChatInfo;

console.log('✅ Chat Info Panel functions loaded');