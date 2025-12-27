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
    console.log('🖼️ Change avatar button clicked');
    const avatarInput = document.getElementById('avatarInput');
    console.log('Avatar input element:', avatarInput);
    avatarInput.click();
});

document.getElementById('avatarInput')?.addEventListe

// Edit name
document.getElementById('editNameBtn')?.addEventListener('click', () => {
    const input = document.getElementById('profileNameInput');
    input.readOnly = false;
    input.focus();
    input.select();
    isEditingProfile = true;
    showProfileActions();
});


document.getElementById('avatarInput')?.addEventListener('change', async (e) => {
    console.log('📁 File input changed');
    console.log('Files:', e.target.files);
    
    const file = e.target.files[0];
    if (!file) {
        console.log('❌ No file selected');
        return;
    }
    
    console.log('📄 File details:', {
        name: file.name,
        size: file.size,
        type: file.type
    });
    
    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        console.log('❌ File too large');
        showToast('Файл слишком большой (макс. 5MB)', 'error');
        return;
    }
    
    // Check file type
    if (!file.type.startsWith('image/')) {
        console.log('❌ Not an image');
        showToast('Можно загружать только изображения', 'error');
        return;
    }
    
    try {
        console.log('⏳ Starting upload...');
        showToast('Загрузка аватара...', 'info');
        
        // Upload avatar
        const formData = new FormData();
        formData.append('file', file);
        
        console.log('FormData created');
        
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        console.log('Token:', token ? 'exists' : 'missing');
        
        console.log('Sending request to /api/upload-avatar');
        
        const response = await fetch('/api/upload-avatar', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (!response.ok) {
            const errorData = await response.json();
            console.error('❌ Server error:', errorData);
            throw new Error(errorData.error || 'Upload failed');
        }
        
        const data = await response.json();
        console.log('✅ Upload successful:', data);
        
        // Update current user
        currentUser.avatar = data.avatarUrl;
        console.log('Updated currentUser.avatar:', currentUser.avatar);
        
        // Update localStorage
        localStorage.setItem('user', JSON.stringify(currentUser));
        console.log('Updated localStorage');
        
        // Update all displays
        updateAvatarDisplays(data.avatarUrl);
        console.log('Updated avatar displays');
        
        showToast('Аватар обновлен', 'success');
        
    } catch (error) {
        console.error('❌ Avatar upload error:', error);
        showToast(error.message || 'Ошибка загрузки аватара', 'error');
    }
    
    // Reset input
    e.target.value = '';
    console.log('Reset file input');
});


function updateAvatarDisplays(avatarUrl) {
    console.log('🔄 Updating avatar displays with URL:', avatarUrl);
    
    // Profile modal
    const profileAvatar = document.getElementById('profileAvatarDisplay');
    if (profileAvatar) {
        profileAvatar.innerHTML = `<img src="${avatarUrl}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        console.log('✅ Updated profile modal avatar');
    } else {
        console.warn('⚠️ profileAvatarDisplay element not found');
    }
    
    // Sidebar
    const sidebarAvatar = document.getElementById('currentUserAvatar');
    if (sidebarAvatar) {
        sidebarAvatar.innerHTML = `<img src="${avatarUrl}" alt="${currentUser.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        console.log('✅ Updated sidebar avatar');
    } else {
        console.warn('⚠️ currentUserAvatar element not found');
    }
}

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

// Upload avatar
async function uploadAvatar(file) {
    try {
        const formData = new FormData();
        formData.append('avatar', file);
        
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        const response = await fetch('/api/upload-avatar', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        if (!response.ok) {
            if (response.status === 413) {
                throw new Error('Файл слишком большой (макс. 5MB)');
            }
            throw new Error('Upload failed');
        }
        
        const data = await response.json();
        return data.avatarUrl;
        
    } catch (error) {
        console.error('Avatar upload error:', error);
        throw error;
    }
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
        showToast('Сохранение...', 'info');
        
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
        
        console.log('Profile updated:', data);
        
        // Update current user
        currentUser.name = data.user.name;
        currentUser.email = data.user.email;
        
        // Update localStorage
        localStorage.setItem('user', JSON.stringify(currentUser));
        
        // Update displays
        const currentUserNameEl = document.getElementById('currentUserName');
        if (currentUserNameEl) {
            currentUserNameEl.textContent = data.user.name;
        }
        
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
    document.getElementById('authScreen').style.display = 'flex';
    
    // Close profile modal
    closeProfileModal();
    
    showToast('Вы вышли из системы', 'success');
}

console.log('✅ Profile features loaded');



// ==================== THEME SETTINGS ====================

document.getElementById('openThemeSettings')?.addEventListener('click', () => {
    openThemeModal();
});

document.getElementById('closeThemeModal')?.addEventListener('click', () => {
    closeThemeModal();
});

function openThemeModal() {
    const modal = document.getElementById('themeModal');
    modal.classList.add('active');
    
    // Обновляем активную тему
    updateThemeSelection();
}

function closeThemeModal() {
    const modal = document.getElementById('themeModal');
    modal.classList.remove('active');
}

function updateThemeSelection() {
    const currentTheme = window.themeManager.getCurrentTheme();
    
    document.querySelectorAll('.theme-option').forEach(option => {
        const theme = option.dataset.theme;
        option.classList.toggle('active', theme === currentTheme);
    });
    
    updateThemeLabel(currentTheme);
}

function updateThemeLabel(theme) {
    const label = document.getElementById('currentThemeLabel');
    if (!label) return;
    
    const labels = {
        'light': 'Светлая',
        'dark': 'Темная',
        'auto': 'Автоматически'
    };
    
    label.textContent = labels[theme] || 'Автоматически';
}

// Theme option click handlers
document.querySelectorAll('.theme-option').forEach(option => {
    option.addEventListener('click', () => {
        const theme = option.dataset.theme;
        
        // Применяем тему
        window.themeManager.setTheme(theme);
        
        // Обновляем UI
        updateThemeSelection();
        
        // Закрываем модал через небольшую задержку
        setTimeout(() => {
            closeThemeModal();
        }, 300);
        
        showToast(`Тема изменена на: ${option.querySelector('h4').textContent}`, 'success');
    });
});

// Обновляем метку темы при открытии профиля
const originalLoadProfileData = loadProfileData;
loadProfileData = function() {
    originalLoadProfileData.call(this);
    if (window.themeManager) {
        updateThemeLabel(window.themeManager.getCurrentTheme());
    }
};