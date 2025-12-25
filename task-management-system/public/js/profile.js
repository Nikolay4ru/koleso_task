// ==================== USER PROFILE ====================

let isEditingProfile = false;
let originalProfileData = {};

// Open profile modal
document.getElementById('openProfileBtn')?.addEventListener('click', () => {
    openProfileModal();
});

document.getElementById('closeProfileBtn')?.addEventListener('click', () => {
    closeProfileModal();
});

function openProfileModal() {
    const modal = document.getElementById('profileModal');
    modal.classList.add('active');
    
    // Load current user data
    loadProfileData();
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    modal.classList.remove('active');
    
    // Cancel any editing
    if (isEditingProfile) {
        cancelProfileChanges();
    }
}

function loadProfileData() {
    if (!currentUser) return;
    
    // Update avatar
    const avatarDisplay = document.getElementById('profileAvatarDisplay');
    if (currentUser.avatar) {
        avatarDisplay.innerHTML = `<img src="${currentUser.avatar}" alt="${currentUser.name}">`;
    } else {
        avatarDisplay.style.background = generateGradient(currentUser.name);
        avatarDisplay.innerHTML = getUserInitials(currentUser.name);
    }
    
    // Update fields
    document.getElementById('profileNameInput').value = currentUser.name || '';
    document.getElementById('profileUsernameDisplay').value = currentUser.username || '';
    document.getElementById('profileEmailInput').value = currentUser.email || '';
    document.getElementById('profileUserIdDisplay').value = currentUser.id || '';
    
    // Store original data
    originalProfileData = {
        name: currentUser.name,
        email: currentUser.email,
        avatar: currentUser.avatar
    };
}

// Avatar upload
document.getElementById('changeAvatarBtn')?.addEventListener('click', () => {
    document.getElementById('avatarInput').click();
});

document.getElementById('avatarInput')?.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showToast('Файл слишком большой (макс. 5MB)', 'error');
        return;
    }
    
    // Check file type
    if (!file.type.startsWith('image/')) {
        showToast('Можно загружать только изображения', 'error');
        return;
    }
    
    try {
        // Upload avatar
        const formData = new FormData();
        formData.append('file', file);
        
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        const response = await fetch('/api/upload-avatar', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        if (!response.ok) throw new Error('Upload failed');
        
        const data = await response.json();
        
        // Update avatar
        currentUser.avatar = data.avatarUrl;
        
        // Update displays
        updateAvatarDisplays(data.avatarUrl);
        
        showToast('Аватар обновлен', 'success');
        
    } catch (error) {
        console.error('Avatar upload error:', error);
        showToast('Ошибка загрузки аватара', 'error');
    }
    
    // Reset input
    e.target.value = '';
});

function updateAvatarDisplays(avatarUrl) {
    // Profile modal
    const profileAvatar = document.getElementById('profileAvatarDisplay');
    profileAvatar.innerHTML = `<img src="${avatarUrl}" alt="${currentUser.name}">`;
    
    // Sidebar
    const sidebarAvatar = document.getElementById('currentUserAvatar');
    sidebarAvatar.innerHTML = `<img src="${avatarUrl}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
}

// Edit name
document.getElementById('editNameBtn')?.addEventListener('click', () => {
    const input = document.getElementById('profileNameInput');
    input.readOnly = false;
    input.focus();
    input.select();
    isEditingProfile = true;
    showProfileActions();
});

// Edit email
document.getElementById('editEmailBtn')?.addEventListener('click', () => {
    const input = document.getElementById('profileEmailInput');
    input.readOnly = false;
    input.focus();
    input.select();
    isEditingProfile = true;
    showProfileActions();
});

function showProfileActions() {
    document.getElementById('profileActions').style.display = 'flex';
}

function hideProfileActions() {
    document.getElementById('profileActions').style.display = 'none';
}

// Cancel changes
document.getElementById('cancelProfileChanges')?.addEventListener('click', () => {
    cancelProfileChanges();
});

function cancelProfileChanges() {
    // Restore original values
    document.getElementById('profileNameInput').value = originalProfileData.name;
    document.getElementById('profileEmailInput').value = originalProfileData.email;
    
    // Make read-only again
    document.getElementById('profileNameInput').readOnly = true;
    document.getElementById('profileEmailInput').readOnly = true;
    
    isEditingProfile = false;
    hideProfileActions();
}

// Save changes
document.getElementById('saveProfileChanges')?.addEventListener('click', async () => {
    const newName = document.getElementById('profileNameInput').value.trim();
    const newEmail = document.getElementById('profileEmailInput').value.trim();
    
    if (!newName) {
        showToast('Имя не может быть пустым', 'error');
        return;
    }
    
    if (!newEmail) {
        showToast('Email не может быть пустым', 'error');
        return;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(newEmail)) {
        showToast('Неверный формат email', 'error');
        return;
    }
    
    try {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        const response = await fetch('/api/profile/update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                name: newName,
                email: newEmail
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Update failed');
        }
        
        const data = await response.json();
        
        // Update current user
        currentUser.name = data.user.name;
        currentUser.email = data.user.email;
        
        // Update displays
        document.getElementById('currentUserName').textContent = data.user.name;
        
        // Update original data
        originalProfileData.name = data.user.name;
        originalProfileData.email = data.user.email;
        
        // Make read-only again
        document.getElementById('profileNameInput').readOnly = true;
        document.getElementById('profileEmailInput').readOnly = true;
        
        isEditingProfile = false;
        hideProfileActions();
        
        showToast('Профиль обновлен', 'success');
        
    } catch (error) {
        console.error('Profile update error:', error);
        showToast(error.message || 'Ошибка обновления профиля', 'error');
    }
});

// Logout
document.getElementById('logoutBtn')?.addEventListener('click', () => {
    if (confirm('Вы уверены, что хотите выйти?')) {
        logout();
    }
});

function logout() {
    // Clear tokens
    localStorage.removeItem('token');
    sessionStorage.removeItem('token');
    
    // Disconnect socket
    if (socket) {
        socket.disconnect();
    }
    
    // Clear data
    currentUser = null;
    currentChat = null;
    users = [];
    chats = [];
    onlineUsers.clear();
    
    // Hide main app, show auth
    document.getElementById('mainApp').style.display = 'none';
    document.getElementById('authContainer').style.display = 'flex';
    
    // Close profile modal
    closeProfileModal();
    
    showToast('Вы вышли из системы', 'success');
}

console.log('✅ Profile features loaded');