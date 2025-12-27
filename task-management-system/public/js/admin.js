// ==================== ADMIN PANEL ====================

let adminUsers = [];
let adminDepartments = [];
let adminTasks = [];
let adminStats = {};
let currentAdminView = 'dashboard'; // dashboard, users, departments, tasks, settings

// ==================== CHECK ADMIN ACCESS ====================

function checkAdminAccess() {
    if (!currentUser || !currentUser.isAdmin) {
        showToast('Доступ запрещен. Требуются права администратора.', 'error');
        // Переключиться на вкладку чатов
        const chatsTab = document.querySelector('.tab-btn[data-tab="chats"]');
        if (chatsTab) chatsTab.click();
        return false;
    }
    return true;
}

// ==================== LOAD ADMIN DATA ====================

async function loadAdminData() {
    if (!checkAdminAccess()) return;
    
    console.log('Loading admin data...');
    
    // Показываем индикатор загрузки
    const adminContent = document.getElementById('adminContent');
    if (adminContent) {
        adminContent.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; gap: 16px;">
                <div class="spinner"></div>
                <p style="color: var(--text-secondary);">Загрузка данных...</p>
            </div>
        `;
    }
    
    try {
        await Promise.all([
            loadAdminUsers(),
            loadAdminDepartments(),
            loadAdminTasks(),
            loadAdminStats()
        ]);
        
        console.log('Admin data loaded successfully');
        
        // Рендерим панель управления по умолчанию
        renderAdminDashboard();
        
    } catch (error) {
        console.error('Error loading admin data:', error);
        showToast('Ошибка загрузки данных', 'error');
        
        if (adminContent) {
            adminContent.innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    <span class="material-icons" style="font-size: 64px; opacity: 0.3;">error_outline</span>
                    <h3>Ошибка загрузки данных</h3>
                    <p>Попробуйте обновить страницу</p>
                </div>
            `;
        }
    }
}

async function loadAdminUsers() {
    try {
        adminUsers = await apiCall('/api/admin/users');
        if (currentAdminView === 'users') {
            renderAdminUsers();
        }
    } catch (error) {
        console.error('Error loading admin users:', error);
    }
}

async function loadAdminDepartments() {
    try {
        adminDepartments = await apiCall('/api/admin/departments');
        if (currentAdminView === 'departments') {
            renderAdminDepartments();
        }
    } catch (error) {
        console.error('Error loading admin departments:', error);
    }
}

async function loadAdminTasks() {
    try {
        adminTasks = await apiCall('/api/admin/tasks');
        if (currentAdminView === 'tasks') {
            renderAdminTasks();
        }
    } catch (error) {
        console.error('Error loading admin tasks:', error);
    }
}

async function loadAdminStats() {
    try {
        adminStats = await apiCall('/api/admin/stats');
        if (currentAdminView === 'dashboard') {
            renderAdminDashboard();
        }
    } catch (error) {
        console.error('Error loading admin stats:', error);
    }
}

// ==================== RENDER ADMIN DASHBOARD ====================

function renderAdminDashboard() {
    const container = document.getElementById('adminContent');
    if (!container) return;
    
    container.innerHTML = `
        <div class="admin-dashboard">
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <span class="material-icons">people</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">${adminStats.totalUsers || 0}</div>
                        <div class="stat-label">Пользователей</div>
                        <div class="stat-change positive">+${adminStats.newUsersThisMonth || 0} за месяц</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <span class="material-icons">task_alt</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">${adminStats.totalTasks || 0}</div>
                        <div class="stat-label">Задач</div>
                        <div class="stat-change">${adminStats.activeTasks || 0} активных</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <span class="material-icons">business</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">${adminStats.totalDepartments || 0}</div>
                        <div class="stat-label">Отделов</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <span class="material-icons">chat</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">${adminStats.totalMessages || 0}</div>
                        <div class="stat-label">Сообщений</div>
                        <div class="stat-change">+${adminStats.messagesToday || 0} сегодня</div>
                    </div>
                </div>
            </div>
            
            <div class="admin-dashboard-grid">
                <div class="admin-panel-section">
                    <div class="admin-section-header">
                        <h3>Активность пользователей</h3>
                    </div>
                    <div class="admin-section-content">
                        <div class="user-activity-list">
                            ${renderUserActivityList()}
                        </div>
                    </div>
                </div>
                
                <div class="admin-panel-section">
                    <div class="admin-section-header">
                        <h3>Последние задачи</h3>
                    </div>
                    <div class="admin-section-content">
                        <div class="recent-tasks-list">
                            ${renderRecentTasksList()}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-panel-section">
                <div class="admin-section-header">
                    <h3>Статистика по отделам</h3>
                </div>
                <div class="admin-section-content">
                    <div class="departments-stats">
                        ${renderDepartmentsStats()}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderUserActivityList() {
    if (!adminStats.recentActivity || adminStats.recentActivity.length === 0) {
        return '<p class="empty-state">Нет активности</p>';
    }
    
    return adminStats.recentActivity.map(activity => {
        const user = adminUsers.find(u => u.id === activity.userId);
        return `
            <div class="activity-item">
                <div class="user-avatar-small" style="background: ${user?.avatar ? 'transparent' : generateGradient(user?.name || 'User')}">
                    ${user?.avatar ? `<img src="${user.avatar}" alt="${user.name}">` : getUserInitials(user?.name || 'U')}
                </div>
                <div class="activity-details">
                    <div class="activity-user">${user?.name || 'Пользователь'}</div>
                    <div class="activity-action">${activity.action}</div>
                    <div class="activity-time">${formatTime(activity.timestamp)}</div>
                </div>
            </div>
        `;
    }).join('');
}

function renderRecentTasksList() {
    const recentTasks = adminTasks.slice(0, 5);
    
    if (recentTasks.length === 0) {
        return '<p class="empty-state">Нет задач</p>';
    }
    
    return recentTasks.map(task => `
        <div class="task-item-small" onclick="openTaskDetails('${task.id}')">
            <div class="task-status-indicator ${task.status}"></div>
            <div class="task-info">
                <div class="task-title-small">${task.title}</div>
                <div class="task-meta-small">
                    <span>${getStatusName(task.status)}</span>
                    ${task.dueDate ? `• <span>${formatDate(task.dueDate)}</span>` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

function renderDepartmentsStats() {
    if (!adminStats.departmentStats || adminStats.departmentStats.length === 0) {
        return '<p class="empty-state">Нет данных по отделам</p>';
    }
    
    return adminStats.departmentStats.map(dept => `
        <div class="dept-stat-item">
            <div class="dept-stat-header">
                <h4>${dept.name}</h4>
                <span class="dept-stat-count">${dept.employeeCount} сотр.</span>
            </div>
            <div class="dept-stat-bars">
                <div class="stat-bar-row">
                    <span class="stat-bar-label">Задач</span>
                    <div class="stat-bar">
                        <div class="stat-bar-fill" style="width: ${(dept.tasksCount / adminStats.totalTasks * 100) || 0}%; background: var(--accent-primary);"></div>
                    </div>
                    <span class="stat-bar-value">${dept.tasksCount}</span>
                </div>
                <div class="stat-bar-row">
                    <span class="stat-bar-label">Завершено</span>
                    <div class="stat-bar">
                        <div class="stat-bar-fill" style="width: ${(dept.completedTasks / dept.tasksCount * 100) || 0}%; background: var(--accent-primary);"></div>
                    </div>
                    <span class="stat-bar-value">${dept.completedTasks}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// ==================== RENDER USERS MANAGEMENT ====================

function renderAdminUsers() {
    const container = document.getElementById('adminContent');
    if (!container) return;
    
    container.innerHTML = `
        <div class="admin-users">
            <div class="admin-toolbar">
                <div class="admin-search">
                    <span class="material-icons">search</span>
                    <input type="text" id="adminUserSearch" placeholder="Поиск пользователей...">
                </div>
                <button class="btn-primary" onclick="openCreateUserModal()">
                    <span class="material-icons">person_add</span>
                    Добавить пользователя
                </button>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Email</th>
                            <th>Имя пользователя</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="adminUsersTableBody">
                        ${renderAdminUsersTable()}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    // Search functionality
    document.getElementById('adminUserSearch')?.addEventListener('input', (e) => {
        filterAdminUsers(e.target.value);
    });
}

function renderAdminUsersTable() {
    if (adminUsers.length === 0) {
        return '<tr><td colspan="7" class="empty-state">Нет пользователей</td></tr>';
    }
    
    return adminUsers.map(user => `
        <tr>
            <td>
                <div class="user-cell">
                    <div class="user-avatar-small" style="background: ${user.avatar ? 'transparent' : generateGradient(user.name)}">
                        ${user.avatar ? `<img src="${user.avatar}" alt="${user.name}">` : getUserInitials(user.name)}
                    </div>
                    <span>${user.name}</span>
                </div>
            </td>
            <td>${user.email}</td>
            <td>${user.username}</td>
            <td>
                <span class="role-badge ${user.isAdmin ? 'admin' : 'user'}">
                    ${user.isAdmin ? 'Администратор' : 'Пользователь'}
                </span>
            </td>
            <td>
                <span class="status-badge ${user.isActive ? 'active' : 'inactive'}">
                    ${user.isActive !== false ? 'Активен' : 'Заблокирован'}
                </span>
            </td>
            <td>${new Date(user.createdAt).toLocaleDateString('ru-RU')}</td>
            <td>
                <div class="table-actions">
                    <button class="icon-btn" onclick="editUser('${user.id}')" title="Редактировать">
                        <span class="material-icons">edit</span>
                    </button>
                    ${!user.isAdmin ? `
                        <button class="icon-btn" onclick="toggleUserRole('${user.id}')" title="Сделать админом">
                            <span class="material-icons">admin_panel_settings</span>
                        </button>
                        <button class="icon-btn" onclick="toggleUserStatus('${user.id}')" title="${user.isActive !== false ? 'Заблокировать' : 'Активировать'}">
                            <span class="material-icons">${user.isActive !== false ? 'block' : 'check_circle'}</span>
                        </button>
                        <button class="icon-btn danger" onclick="deleteUser('${user.id}')" title="Удалить">
                            <span class="material-icons">delete</span>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function filterAdminUsers(query) {
    const tbody = document.getElementById('adminUsersTableBody');
    if (!tbody) return;
    
    const filtered = adminUsers.filter(user => 
        user.name.toLowerCase().includes(query.toLowerCase()) ||
        user.email.toLowerCase().includes(query.toLowerCase()) ||
        user.username.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Пользователи не найдены</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map(user => `
        <tr>
            <td>
                <div class="user-cell">
                    <div class="user-avatar-small" style="background: ${user.avatar ? 'transparent' : generateGradient(user.name)}">
                        ${user.avatar ? `<img src="${user.avatar}" alt="${user.name}">` : getUserInitials(user.name)}
                    </div>
                    <span>${user.name}</span>
                </div>
            </td>
            <td>${user.email}</td>
            <td>${user.username}</td>
            <td>
                <span class="role-badge ${user.isAdmin ? 'admin' : 'user'}">
                    ${user.isAdmin ? 'Администратор' : 'Пользователь'}
                </span>
            </td>
            <td>
                <span class="status-badge ${user.isActive ? 'active' : 'inactive'}">
                    ${user.isActive !== false ? 'Активен' : 'Заблокирован'}
                </span>
            </td>
            <td>${new Date(user.createdAt).toLocaleDateString('ru-RU')}</td>
            <td>
                <div class="table-actions">
                    <button class="icon-btn" onclick="editUser('${user.id}')" title="Редактировать">
                        <span class="material-icons">edit</span>
                    </button>
                    ${!user.isAdmin ? `
                        <button class="icon-btn" onclick="toggleUserRole('${user.id}')" title="Сделать админом">
                            <span class="material-icons">admin_panel_settings</span>
                        </button>
                        <button class="icon-btn" onclick="toggleUserStatus('${user.id}')" title="${user.isActive !== false ? 'Заблокировать' : 'Активировать'}">
                            <span class="material-icons">${user.isActive !== false ? 'block' : 'check_circle'}</span>
                        </button>
                        <button class="icon-btn danger" onclick="deleteUser('${user.id}')" title="Удалить">
                            <span class="material-icons">delete</span>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// ==================== USER ACTIONS ====================

function openCreateUserModal() {
    const modal = document.getElementById('adminUserModal');
    modal.classList.add('active');
    
    document.getElementById('adminUserForm').reset();
    document.getElementById('adminUserId').value = '';
    document.getElementById('adminUserModalTitle').textContent = 'Добавить пользователя';
    document.getElementById('adminUserPassword').required = true;
}

function editUser(userId) {
    const user = adminUsers.find(u => u.id === userId);
    if (!user) return;
    
    const modal = document.getElementById('adminUserModal');
    modal.classList.add('active');
    
    document.getElementById('adminUserId').value = user.id;
    document.getElementById('adminUserName').value = user.name;
    document.getElementById('adminUserEmail').value = user.email;
    document.getElementById('adminUserUsername').value = user.username;
    document.getElementById('adminUserRole').value = user.isAdmin ? 'admin' : 'user';
    document.getElementById('adminUserPassword').required = false;
    document.getElementById('adminUserPassword').value = '';
    
    document.getElementById('adminUserModalTitle').textContent = 'Редактировать пользователя';
}

async function saveAdminUser() {
    const userId = document.getElementById('adminUserId').value;
    const name = document.getElementById('adminUserName').value.trim();
    const email = document.getElementById('adminUserEmail').value.trim();
    const username = document.getElementById('adminUserUsername').value.trim();
    const password = document.getElementById('adminUserPassword').value;
    const role = document.getElementById('adminUserRole').value;
    
    if (!name || !email || !username) {
        showToast('Заполните все обязательные поля', 'warning');
        return;
    }
    
    if (!userId && !password) {
        showToast('Введите пароль для нового пользователя', 'warning');
        return;
    }
    
    try {
        const userData = {
            name,
            email,
            username,
            isAdmin: role === 'admin'
        };
        
        if (password) {
            userData.password = password;
        }
        
        if (userId) {
            // Update
            await apiCall(`/api/admin/users/${userId}`, {
                method: 'PUT',
                body: JSON.stringify(userData)
            });
            showToast('Пользователь обновлен', 'success');
        } else {
            // Create
            await apiCall('/api/admin/users', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            showToast('Пользователь создан', 'success');
        }
        
        closeAdminUserModal();
        loadAdminUsers();
        
    } catch (error) {
        console.error('Error saving user:', error);
        showToast(error.message || 'Ошибка сохранения пользователя', 'error');
    }
}

function closeAdminUserModal() {
    document.getElementById('adminUserModal').classList.remove('active');
}

async function toggleUserRole(userId) {
    if (!confirm('Вы уверены, что хотите изменить роль пользователя?')) return;
    
    try {
        await apiCall(`/api/admin/users/${userId}/toggle-role`, {
            method: 'POST'
        });
        
        showToast('Роль пользователя изменена', 'success');
        loadAdminUsers();
    } catch (error) {
        console.error('Error toggling user role:', error);
        showToast('Ошибка изменения роли', 'error');
    }
}

async function toggleUserStatus(userId) {
    const user = adminUsers.find(u => u.id === userId);
    const action = user.isActive !== false ? 'заблокировать' : 'активировать';
    
    if (!confirm(`Вы уверены, что хотите ${action} пользователя?`)) return;
    
    try {
        await apiCall(`/api/admin/users/${userId}/toggle-status`, {
            method: 'POST'
        });
        
        showToast(`Пользователь ${user.isActive !== false ? 'заблокирован' : 'активирован'}`, 'success');
        loadAdminUsers();
    } catch (error) {
        console.error('Error toggling user status:', error);
        showToast('Ошибка изменения статуса', 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('Вы уверены, что хотите удалить этого пользователя? Это действие необратимо!')) return;
    
    try {
        await apiCall(`/api/admin/users/${userId}`, {
            method: 'DELETE'
        });
        
        showToast('Пользователь удален', 'success');
        loadAdminUsers();
    } catch (error) {
        console.error('Error deleting user:', error);
        showToast('Ошибка удаления пользователя', 'error');
    }
}

// ==================== RENDER DEPARTMENTS MANAGEMENT ====================

function renderAdminDepartments() {
    const container = document.getElementById('adminContent');
    if (!container) return;
    
    container.innerHTML = `
        <div class="admin-departments">
            <div class="admin-toolbar">
                <h2>Управление отделами</h2>
                <button class="btn-primary" onclick="openCreateDepartmentModal()">
                    <span class="material-icons">add</span>
                    Добавить отдел
                </button>
            </div>
            
            <div class="departments-grid">
                ${renderDepartmentsGrid()}
            </div>
        </div>
    `;
}

function renderDepartmentsGrid() {
    if (adminDepartments.length === 0) {
        return '<div class="empty-state">Нет отделов</div>';
    }
    
    return adminDepartments.map(dept => {
        const deptStats = adminStats.departmentStats?.find(d => d.id === dept.id) || {};
        
        return `
            <div class="department-card">
                <div class="department-card-header">
                    <h3>${dept.name}</h3>
                    <div class="department-actions">
                        <button class="icon-btn" onclick="editDepartment('${dept.id}')">
                            <span class="material-icons">edit</span>
                        </button>
                        <button class="icon-btn danger" onclick="deleteDepartment('${dept.id}')">
                            <span class="material-icons">delete</span>
                        </button>
                    </div>
                </div>
                
                <div class="department-card-body">
                    <div class="department-stat">
                        <span class="material-icons">people</span>
                        <div>
                            <div class="stat-value">${deptStats.employeeCount || 0}</div>
                            <div class="stat-label">Сотрудников</div>
                        </div>
                    </div>
                    
                    <div class="department-stat">
                        <span class="material-icons">task_alt</span>
                        <div>
                            <div class="stat-value">${deptStats.tasksCount || 0}</div>
                            <div class="stat-label">Задач</div>
                        </div>
                    </div>
                    
                    <div class="department-stat">
                        <span class="material-icons">check_circle</span>
                        <div>
                            <div class="stat-value">${deptStats.completedTasks || 0}</div>
                            <div class="stat-label">Завершено</div>
                        </div>
                    </div>
                </div>
                
                ${dept.description ? `
                    <div class="department-card-footer">
                        <p>${dept.description}</p>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

function openCreateDepartmentModal() {
    const modal = document.getElementById('adminDepartmentModal');
    modal.classList.add('active');
    
    document.getElementById('adminDepartmentForm').reset();
    document.getElementById('adminDepartmentId').value = '';
    document.getElementById('adminDepartmentModalTitle').textContent = 'Добавить отдел';
}

function editDepartment(deptId) {
    const dept = adminDepartments.find(d => d.id === deptId);
    if (!dept) return;
    
    const modal = document.getElementById('adminDepartmentModal');
    modal.classList.add('active');
    
    document.getElementById('adminDepartmentId').value = dept.id;
    document.getElementById('adminDepartmentName').value = dept.name;
    document.getElementById('adminDepartmentDescription').value = dept.description || '';
    
    document.getElementById('adminDepartmentModalTitle').textContent = 'Редактировать отдел';
}

async function saveAdminDepartment() {
    const deptId = document.getElementById('adminDepartmentId').value;
    const name = document.getElementById('adminDepartmentName').value.trim();
    const description = document.getElementById('adminDepartmentDescription').value.trim();
    
    if (!name) {
        showToast('Введите название отдела', 'warning');
        return;
    }
    
    try {
        const deptData = { name, description };
        
        if (deptId) {
            await apiCall(`/api/admin/departments/${deptId}`, {
                method: 'PUT',
                body: JSON.stringify(deptData)
            });
            showToast('Отдел обновлен', 'success');
        } else {
            await apiCall('/api/admin/departments', {
                method: 'POST',
                body: JSON.stringify(deptData)
            });
            showToast('Отдел создан', 'success');
        }
        
        closeAdminDepartmentModal();
        loadAdminDepartments();
        
    } catch (error) {
        console.error('Error saving department:', error);
        showToast('Ошибка сохранения отдела', 'error');
    }
}

function closeAdminDepartmentModal() {
    document.getElementById('adminDepartmentModal').classList.remove('active');
}

async function deleteDepartment(deptId) {
    if (!confirm('Вы уверены, что хотите удалить этот отдел?')) return;
    
    try {
        await apiCall(`/api/admin/departments/${deptId}`, {
            method: 'DELETE'
        });
        
        showToast('Отдел удален', 'success');
        loadAdminDepartments();
        
    } catch (error) {
        console.error('Error deleting department:', error);
        showToast('Ошибка удаления отдела', 'error');
    }
}

// ==================== RENDER TASKS MANAGEMENT ====================

function renderAdminTasks() {
    const container = document.getElementById('adminContent');
    if (!container) return;
    
    container.innerHTML = `
        <div class="admin-tasks">
            <div class="admin-toolbar">
                <div class="admin-search">
                    <span class="material-icons">search</span>
                    <input type="text" id="adminTaskSearch" placeholder="Поиск задач...">
                </div>
                <div class="admin-filters">
                    <select id="adminTaskStatusFilter">
                        <option value="">Все статусы</option>
                        <option value="todo">К выполнению</option>
                        <option value="in_progress">В работе</option>
                        <option value="review">На проверке</option>
                        <option value="done">Выполнено</option>
                    </select>
                    <select id="adminTaskDepartmentFilter">
                        <option value="">Все отделы</option>
                        ${adminDepartments.map(d => `<option value="${d.id}">${d.name}</option>`).join('')}
                    </select>
                </div>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Задача</th>
                            <th>Отдел</th>
                            <th>Исполнитель</th>
                            <th>Постановщик</th>
                            <th>Статус</th>
                            <th>Приоритет</th>
                            <th>Срок</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="adminTasksTableBody">
                        ${renderAdminTasksTable()}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    // Filters
    document.getElementById('adminTaskSearch')?.addEventListener('input', filterAdminTasks);
    document.getElementById('adminTaskStatusFilter')?.addEventListener('change', filterAdminTasks);
    document.getElementById('adminTaskDepartmentFilter')?.addEventListener('change', filterAdminTasks);
}

function renderAdminTasksTable() {
    if (adminTasks.length === 0) {
        return '<tr><td colspan="8" class="empty-state">Нет задач</td></tr>';
    }
    
    return adminTasks.map(task => {
        const assignee = adminUsers.find(u => u.id === task.assigneeId);
        const creator = adminUsers.find(u => u.id === task.creatorId);
        const department = adminDepartments.find(d => d.id === task.departmentId);
        const isOverdue = task.dueDate && new Date(task.dueDate) < new Date() && task.status !== 'done';
        
        return `
            <tr>
                <td>
                    <div class="task-cell">
                        <div class="task-title-cell">${task.title}</div>
                        ${task.description ? `<div class="task-description-cell">${task.description.substring(0, 50)}...</div>` : ''}
                    </div>
                </td>
                <td>${department?.name || '-'}</td>
                <td>${assignee?.name || 'Не назначено'}</td>
                <td>${creator?.name || '-'}</td>
                <td>
                    <span class="task-status-badge ${task.status}">${getStatusName(task.status)}</span>
                </td>
                <td>
                    ${task.priority !== 'normal' ? `<span class="task-priority-badge ${task.priority}">${getPriorityName(task.priority)}</span>` : '-'}
                </td>
                <td>
                    ${task.dueDate ? `<span class="${isOverdue ? 'text-error' : ''}">${formatDate(task.dueDate)}</span>` : '-'}
                </td>
                <td>
                    <div class="table-actions">
                        <button class="icon-btn" onclick="openTaskDetails('${task.id}')" title="Просмотр">
                            <span class="material-icons">visibility</span>
                        </button>
                        <button class="icon-btn danger" onclick="deleteAdminTask('${task.id}')" title="Удалить">
                            <span class="material-icons">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function filterAdminTasks() {
    const searchQuery = document.getElementById('adminTaskSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('adminTaskStatusFilter')?.value || '';
    const departmentFilter = document.getElementById('adminTaskDepartmentFilter')?.value || '';
    
    const filtered = adminTasks.filter(task => {
        const matchesSearch = task.title.toLowerCase().includes(searchQuery) || 
                            (task.description && task.description.toLowerCase().includes(searchQuery));
        const matchesStatus = !statusFilter || task.status === statusFilter;
        const matchesDepartment = !departmentFilter || task.departmentId === departmentFilter;
        
        return matchesSearch && matchesStatus && matchesDepartment;
    });
    
    const tbody = document.getElementById('adminTasksTableBody');
    if (!tbody) return;
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">Задачи не найдены</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map(task => {
        const assignee = adminUsers.find(u => u.id === task.assigneeId);
        const creator = adminUsers.find(u => u.id === task.creatorId);
        const department = adminDepartments.find(d => d.id === task.departmentId);
        const isOverdue = task.dueDate && new Date(task.dueDate) < new Date() && task.status !== 'done';
        
        return `
            <tr>
                <td>
                    <div class="task-cell">
                        <div class="task-title-cell">${task.title}</div>
                        ${task.description ? `<div class="task-description-cell">${task.description.substring(0, 50)}...</div>` : ''}
                    </div>
                </td>
                <td>${department?.name || '-'}</td>
                <td>${assignee?.name || 'Не назначено'}</td>
                <td>${creator?.name || '-'}</td>
                <td>
                    <span class="task-status-badge ${task.status}">${getStatusName(task.status)}</span>
                </td>
                <td>
                    ${task.priority !== 'normal' ? `<span class="task-priority-badge ${task.priority}">${getPriorityName(task.priority)}</span>` : '-'}
                </td>
                <td>
                    ${task.dueDate ? `<span class="${isOverdue ? 'text-error' : ''}">${formatDate(task.dueDate)}</span>` : '-'}
                </td>
                <td>
                    <div class="table-actions">
                        <button class="icon-btn" onclick="openTaskDetails('${task.id}')" title="Просмотр">
                            <span class="material-icons">visibility</span>
                        </button>
                        <button class="icon-btn danger" onclick="deleteAdminTask('${task.id}')" title="Удалить">
                            <span class="material-icons">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function deleteAdminTask(taskId) {
    if (!confirm('Вы уверены, что хотите удалить эту задачу?')) return;
    
    try {
        await apiCall(`/api/tasks/${taskId}`, {
            method: 'DELETE'
        });
        
        showToast('Задача удалена', 'success');
        loadAdminTasks();
        
    } catch (error) {
        console.error('Error deleting task:', error);
        showToast('Ошибка удаления задачи', 'error');
    }
}

// ==================== VIEW SWITCHING ====================

function switchAdminView(view) {
    currentAdminView = view;
    
    document.querySelectorAll('.admin-nav-item').forEach(item => {
        item.classList.toggle('active', item.dataset.view === view);
    });
    
    switch (view) {
        case 'dashboard':
            renderAdminDashboard();
            break;
        case 'users':
            renderAdminUsers();
            break;
        case 'departments':
            renderAdminDepartments();
            break;
        case 'tasks':
            renderAdminTasks();
            break;
    }
}

// ==================== INITIALIZATION ====================

function initAdminPanel() {
    console.log('Initializing admin panel...');
    
    // Event listeners for user modal
    const closeAdminUserModalBtn = document.getElementById('closeAdminUserModalBtn');
    const cancelAdminUserBtn = document.getElementById('cancelAdminUserBtn');
    const saveAdminUserBtn = document.getElementById('saveAdminUserBtn');
    
    if (closeAdminUserModalBtn) {
        closeAdminUserModalBtn.addEventListener('click', closeAdminUserModal);
    }
    
    if (cancelAdminUserBtn) {
        cancelAdminUserBtn.addEventListener('click', closeAdminUserModal);
    }
    
    if (saveAdminUserBtn) {
        saveAdminUserBtn.addEventListener('click', saveAdminUser);
    }
    
    // Event listeners for department modal
    const closeAdminDepartmentModalBtn = document.getElementById('closeAdminDepartmentModalBtn');
    const cancelAdminDepartmentBtn = document.getElementById('cancelAdminDepartmentBtn');
    const saveAdminDepartmentBtn = document.getElementById('saveAdminDepartmentBtn');
    
    if (closeAdminDepartmentModalBtn) {
        closeAdminDepartmentModalBtn.addEventListener('click', closeAdminDepartmentModal);
    }
    
    if (cancelAdminDepartmentBtn) {
        cancelAdminDepartmentBtn.addEventListener('click', closeAdminDepartmentModal);
    }
    
    if (saveAdminDepartmentBtn) {
        saveAdminDepartmentBtn.addEventListener('click', saveAdminDepartment);
    }
    
    // Admin navigation - делаем обработку ПОСЛЕ того как элементы появятся в DOM
    setTimeout(() => {
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.addEventListener('click', () => {
                console.log('Admin nav clicked:', item.dataset.view);
                switchAdminView(item.dataset.view);
            });
        });
    }, 500);
    
    setupMobileAdmin();
    
    console.log('✅ Admin panel initialized');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminPanel);
} else {
    initAdminPanel();
}

// Make functions globally available
window.loadAdminData = loadAdminData;
window.switchAdminView = switchAdminView;
window.openCreateUserModal = openCreateUserModal;
window.editUser = editUser;
window.saveAdminUser = saveAdminUser;
window.closeAdminUserModal = closeAdminUserModal;
window.toggleUserRole = toggleUserRole;
window.toggleUserStatus = toggleUserStatus;
window.deleteUser = deleteUser;
window.openCreateDepartmentModal = openCreateDepartmentModal;
window.editDepartment = editDepartment;
window.saveAdminDepartment = saveAdminDepartment;
window.closeAdminDepartmentModal = closeAdminDepartmentModal;
window.deleteDepartment = deleteDepartment;
window.deleteAdminTask = deleteAdminTask;

console.log('✅ Admin module loaded');



function setupMobileAdmin() {
    if (window.innerWidth <= 768) {
        // Создаем кнопку для мобильного меню
        const mobileToggle = document.createElement('button');
        mobileToggle.className = 'admin-mobile-toggle';
        mobileToggle.innerHTML = '<span class="material-icons">menu</span>';
        mobileToggle.style.display = 'none';
        
        const adminArea = document.getElementById('adminArea');
        if (adminArea) {
            adminArea.appendChild(mobileToggle);
        }
        
        // Создаем overlay
        const overlay = document.createElement('div');
        overlay.className = 'admin-overlay';
        if (adminArea) {
            adminArea.appendChild(overlay);
        }
        
        const sidebar = document.querySelector('.admin-sidebar');
        
        mobileToggle.addEventListener('click', () => {
            sidebar?.classList.toggle('open');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', () => {
            sidebar?.classList.remove('open');
            overlay.classList.remove('active');
        });
        
        // Показываем кнопку когда открыта админка
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'style') {
                    const isVisible = adminArea.style.display !== 'none';
                    mobileToggle.style.display = isVisible ? 'flex' : 'none';
                }
            });
        });
        
        observer.observe(adminArea, { attributes: true });
    }
}