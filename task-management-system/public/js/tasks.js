// ==================== TASKS MANAGEMENT WITH GAMIFICATION ====================
// Полная интеграция gamified функционала напрямую в tasks.js

let tasks = [];
let departments = [];
let currentTaskView = 'board';
let currentTaskFilter = 'all';

// ==================== GAMIFICATION STATE ====================

const gamification = {
    enabled: () => window.innerWidth <= 768,
    data: {
        streak: 0,
        completedToday: 0,
        totalTasks: 0
    },
    
    // Загрузка streak из localStorage
    loadStreak() {
        const lastDate = localStorage.getItem('task_streak_date');
        const streak = parseInt(localStorage.getItem('task_streak') || '0');
        const today = new Date().toDateString();
        
        if (lastDate === today) {
            this.data.streak = streak;
        } else {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (lastDate === yesterday.toDateString()) {
                this.data.streak = streak + 1;
                localStorage.setItem('task_streak', this.data.streak);
                localStorage.setItem('task_streak_date', today);
            } else {
                this.data.streak = 1;
                localStorage.setItem('task_streak', '1');
                localStorage.setItem('task_streak_date', today);
            }
        }
    },
    
    // Обновление статистики
    updateStats() {
        if (!this.enabled()) return;
        
        const all = tasks;
        const done = all.filter(t => t.status === 'done');
        this.data.completedToday = done.length;
        this.data.totalTasks = all.length;
        
        const percent = all.length > 0 ? Math.round((done.length / all.length) * 100) : 0;
        
        // Обновляем UI
        const elCompleted = document.getElementById('gamified-completed');
        const elPercent = document.getElementById('gamified-percent');
        const elFill = document.getElementById('gamified-fill');
        const elStreak = document.getElementById('gamified-streak');
        
        if (elCompleted) elCompleted.textContent = `${done.length}/${all.length}`;
        if (elPercent) elPercent.textContent = `${percent}%`;
        if (elFill) elFill.style.width = `${percent}%`;
        if (elStreak) elStreak.textContent = this.data.streak;
        
        this.updateMessage(percent);
    },
    
    // Обновление мотивационного сообщения
    updateMessage(percent) {
        const el = document.getElementById('gamified-message');
        if (!el) return;
        
        const messages = [
            { t: 0, i: '💪', m: 'Start your first task!' },
            { t: 25, i: '🚀', m: "You're on fire!" },
            { t: 50, i: '⚡', m: 'Halfway there!' },
            { t: 75, i: '🌟', m: 'Almost done!' },
            { t: 100, i: '🎉', m: 'All completed!' }
        ];
        
        const msg = messages.reverse().find(m => percent >= m.t);
        if (msg) {
            el.innerHTML = `<span style="font-size: 16px;">${msg.i}</span> <span>${msg.m}</span>`;
        }
    },
    
    // Achievement popup
    showAchievement(title, desc) {
        if (!this.enabled()) return;
        
        const popup = document.createElement('div');
        popup.style.cssText = `
            position: fixed;
            top: max(80px, calc(env(safe-area-inset-top, 0px) + 60px));
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            padding: 16px 24px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(255,215,0,0.6);
            z-index: 10000;
            max-width: 90%;
            animation: slideDown 0.5s ease;
        `;
        
        popup.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 36px;">🏆</span>
                <div>
                    <h4 style="margin: 0 0 4px; font-size: 16px; font-weight: 700;">${title}</h4>
                    <p style="margin: 0; font-size: 13px;">${desc}</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(popup);
        
        if (navigator.vibrate) navigator.vibrate([50, 100, 50]);
        
        setTimeout(() => {
            popup.style.animation = 'slideUp 0.4s ease';
            setTimeout(() => popup.remove(), 400);
        }, 3000);
    },
    
    // Confetti анимация
    showConfetti() {
        if (!this.enabled()) return;
        
        const container = document.createElement('div');
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        `;
        
        const colors = ['#FF6B35', '#00B8A9', '#FFD700', '#00D4C4', '#FF9500'];
        for (let i = 0; i < 50; i++) {
            const piece = document.createElement('div');
            piece.style.cssText = `
                position: absolute;
                width: 10px;
                height: 10px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${Math.random() * 100}%;
                animation: confettiFall ${Math.random() * 2 + 2}s ease forwards;
            `;
            container.appendChild(piece);
        }
        
        document.body.appendChild(container);
        setTimeout(() => container.remove(), 4000);
    }
};

// ==================== LOAD TASKS ====================

async function loadTasks() {
    try {
        const data = await apiCall('/api/tasks');
        tasks = data;
        renderTasks();
        
        if (gamification.enabled()) {
            gamification.updateStats();
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
        showToast('Ошибка загрузки задач', 'error');
    }
}

async function loadDepartments() {
    try {
        const data = await apiCall('/api/departments');
        departments = data;
    } catch (error) {
        console.error('Error loading departments:', error);
    }
}

// ==================== RENDER TASKS ====================

function renderTasks() {
    if (currentTaskView === 'board') {
        renderKanbanBoard();
    } else {
        renderTasksList();
    }
    
    // Обновляем gamification после рендера
    if (gamification.enabled()) {
        setTimeout(() => {
            gamification.updateStats();
            enhanceTaskCardsForMobile();
        }, 100);
    }
}

function renderKanbanBoard() {
    const boardContainer = document.getElementById('kanbanBoard');
    if (!boardContainer) return;

    const statuses = [
        { id: 'todo', name: 'К выполнению', color: '#8696a0' },
        { id: 'in_progress', name: 'В работе', color: '#0088cc' },
        { id: 'review', name: 'На проверке', color: '#f39c12' },
        { id: 'done', name: 'Выполнено', color: '#00a884' }
    ];

    const filteredTasks = getFilteredTasks();

    boardContainer.innerHTML = statuses.map(status => {
        const statusTasks = filteredTasks.filter(t => t.status === status.id);
        
        return `
            <div class="kanban-column" data-status="${status.id}">
                <div class="kanban-column-header" style="border-bottom: 3px solid ${status.color}">
                    <h3>${status.name}</h3>
                    <span class="task-count">${statusTasks.length}</span>
                </div>
                <div class="kanban-column-content" data-status="${status.id}">
                    ${statusTasks.map(task => renderTaskCard(task)).join('')}
                </div>
            </div>
        `;
    }).join('');

    setupDragAndDrop();
}

function renderTaskCard(task) {
    const assignee = users.find(u => u.id === task.assigneeId);
    const department = departments.find(d => d.id === task.departmentId);
    
    const isOverdue = task.dueDate && new Date(task.dueDate) < new Date() && task.status !== 'done';
    const daysLeft = task.dueDate ? Math.ceil((new Date(task.dueDate) - new Date()) / (1000 * 60 * 60 * 24)) : null;
    
    let dueDateClass = '';
    let dueDateText = '';
    if (task.dueDate) {
        if (isOverdue) {
            dueDateClass = 'overdue';
            dueDateText = 'Просрочено';
        } else if (daysLeft <= 1) {
            dueDateClass = 'urgent';
            dueDateText = 'Сегодня';
        } else if (daysLeft <= 3) {
            dueDateClass = 'soon';
            dueDateText = `${daysLeft} дн.`;
        } else {
            dueDateText = formatDate(task.dueDate);
        }
    }

    const priorityAttr = task.priority ? `data-priority="${task.priority}"` : '';

    return `
        <div class="task-card" data-task-id="${task.id}" ${priorityAttr} draggable="true">
            ${task.priority === 'high' ? '<div class="task-priority-indicator high"></div>' : ''}
            ${task.priority === 'urgent' ? '<div class="task-priority-indicator urgent"></div>' : ''}
            
            <div class="task-card-header">
                <h4 class="task-title">${task.title}</h4>
                ${task.hasUnread ? '<span class="task-unread-dot"></span>' : ''}
            </div>
            
            ${task.description ? `<p class="task-description">${task.description.substring(0, 100)}${task.description.length > 100 ? '...' : ''}</p>` : ''}
            
            <div class="task-card-meta">
                ${department ? `<span class="task-department">${department.name}</span>` : ''}
                ${task.dueDate ? `<span class="task-due-date ${dueDateClass}">
                    <span class="material-icons">schedule</span>
                    ${dueDateText}
                </span>` : ''}
            </div>
            
            <div class="task-card-footer">
                <div class="task-assignee">
                    ${assignee ? `
                        <div class="task-avatar" style="background: ${assignee.avatar ? 'transparent' : generateGradient(assignee.name)}">
                            ${assignee.avatar ? `<img src="${assignee.avatar}" alt="${assignee.name}">` : getUserInitials(assignee.name)}
                        </div>
                        <span class="task-assignee-name">${assignee.name}</span>
                    ` : '<span class="task-no-assignee">Не назначено</span>'}
                </div>
                
                <div class="task-actions">
                    ${(task.watchersCount || 0) > 0 ? `
                        <span class="task-watchers">
                            <span class="material-icons">visibility</span>
                            ${task.watchersCount}
                        </span>
                    ` : ''}
                    ${(task.commentsCount || 0) > 0 ? `
                        <span class="task-comments">
                            <span class="material-icons">chat_bubble_outline</span>
                            ${task.commentsCount}
                        </span>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function renderTasksList() {
    const listContainer = document.getElementById('tasksList');
    if (!listContainer) return;

    const filteredTasks = getFilteredTasks();
    
    if (filteredTasks.length === 0) {
        listContainer.innerHTML = `
            <div class="tasks-empty">
                <span class="material-icons">task_alt</span>
                <h3>Нет задач</h3>
                <p>Создайте первую задачу</p>
            </div>
        `;
        return;
    }

    listContainer.innerHTML = filteredTasks.map(task => {
        const assignee = users.find(u => u.id === task.assigneeId);
        const department = departments.find(d => d.id === task.departmentId);
        
        const isOverdue = task.dueDate && new Date(task.dueDate) < new Date() && task.status !== 'done';
        const daysLeft = task.dueDate ? Math.ceil((new Date(task.dueDate) - new Date()) / (1000 * 60 * 60 * 24)) : null;
        
        let dueDateClass = '';
        if (task.dueDate) {
            if (isOverdue) {
                dueDateClass = 'urgent';
            } else if (daysLeft !== null && daysLeft <= 3) {
                dueDateClass = 'soon';
            }
        }
        
        let priorityBadge = '';
        if (task.priority === 'high' || task.priority === 'urgent') {
            priorityBadge = `
                <span class="task-priority-chip ${task.priority}">
                    <span class="material-icons">flag</span>
                    ${task.priority === 'high' ? 'Высокий' : 'Срочно'}
                </span>
            `;
        }
        
        const priorityAttr = task.priority ? `data-priority="${task.priority}"` : '';
        
        return `
            <div class="task-list-item ${task.status === 'done' ? 'completed' : ''}" 
                 data-task-id="${task.id}" 
                 ${priorityAttr}
                 onclick="event.target.closest('.task-list-checkbox, .task-list-actions') || openTaskDetails('${task.id}')">
                <div class="task-list-checkbox" onclick="event.stopPropagation()">
                    <input type="checkbox" id="task-checkbox-${task.id}" ${task.status === 'done' ? 'checked' : ''} onchange="toggleTaskComplete('${task.id}', this.checked)">
                </div>
                
                <div class="task-list-content">
                    <div class="task-list-header">
                        <h3 class="task-title">${task.title}</h3>
                        ${task.hasUnread ? '<span class="task-unread-dot"></span>' : ''}
                    </div>
                    
                    ${task.description ? `<p class="task-description">${task.description.substring(0, 120)}${task.description.length > 120 ? '...' : ''}</p>` : ''}
                    
                    <div class="task-list-meta">
                        <span class="task-status-badge ${task.status}">${getStatusName(task.status)}</span>
                        ${assignee ? `
                            <span class="task-assignee-chip">
                                <span class="material-icons">person</span>
                                ${assignee.name}
                            </span>
                        ` : ''}
                        ${priorityBadge}
                    </div>
                    
                    <div class="task-list-footer">
                        <div class="task-list-footer-left">
                            ${task.dueDate ? `
                                <span class="task-due-date-chip ${dueDateClass}">
                                    <span class="material-icons">schedule</span>
                                    ${formatDate(task.dueDate)}
                                </span>
                            ` : ''}
                            ${department ? `<span class="task-department">${department.name}</span>` : ''}
                        </div>
                        
                        <div class="task-list-actions" onclick="event.stopPropagation()">
                            <button class="icon-btn" onclick="openTaskChat('${task.id}')" title="Комментарии">
                                <span class="material-icons">comment</span>
                                ${task.commentsCount > 0 ? `<span class="badge">${task.commentsCount}</span>` : ''}
                            </button>
                            <button class="icon-btn" onclick="openTaskDetails('${task.id}')" title="Наблюдатели">
                                <span class="material-icons">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// ==================== MOBILE ENHANCEMENTS ====================


function enhanceTaskCardsForMobile() {
    if (!gamification.enabled()) return;
    
    const cards = document.querySelectorAll('.task-card');
    console.log('🎨 Enhancing', cards.length, 'cards');
    
    cards.forEach(card => {
        if (card.dataset.mobileEnhanced === 'true') return;
        card.dataset.mobileEnhanced = 'true';
        
        const taskId = card.dataset.taskId;
        const task = tasks.find(t => t.id === taskId);
        if (!task) return;
        
        // Добавляем progress bar (улучшенный)
        if (!card.querySelector('.task-progress-mobile')) {
            const progress = getTaskProgress(task);
            const progressHtml = `
                <div class="task-progress-mobile">
                    <div class="progress-header">
                        <span class="progress-label">Progress</span>
                        <span class="progress-value">${progress}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress}%"></div>
                    </div>
                </div>
            `;
            
            const footer = card.querySelector('.task-card-footer');
            if (footer) {
                footer.insertAdjacentHTML('afterend', progressHtml);
            } else {
                card.insertAdjacentHTML('beforeend', progressHtml);
            }
        }
    });
}

function getTaskProgress(task) {
    const map = {
        'todo': 0,
        'in_progress': 50,
        'review': 75,
        'done': 100
    };
    return map[task.status] || 0;
}

function getFilteredTasks() {
    let filtered = tasks;
    
    switch (currentTaskFilter) {
        case 'my':
            filtered = tasks.filter(t => t.assigneeId === currentUser.id);
            break;
        case 'created':
            filtered = tasks.filter(t => t.creatorId === currentUser.id);
            break;
        case 'watching':
            filtered = tasks.filter(t => t.watchers && t.watchers.includes(currentUser.id));
            break;
    }
    
    return filtered.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

// ==================== PERMISSIONS ====================

function canEditTask(task) {
    return task.creatorId === currentUser.id;
}

function canDeleteTask(task) {
    return task.creatorId === currentUser.id;
}

function canCompleteTask(task) {
    return task.creatorId === currentUser.id;
}

function canReopenTask(task) {
    return task.creatorId === currentUser.id && task.status === 'done';
}

function canStartTask(task) {
    return task.assigneeId === currentUser.id && task.status === 'todo';
}

function canSendToReview(task) {
    return task.assigneeId === currentUser.id && task.status === 'in_progress';
}

function canMoveTask(task, newStatus) {
    const isCreator = task.creatorId === currentUser.id;
    const isAssignee = task.assigneeId === currentUser.id;
    
    if (isCreator) {
        if (task.status === 'todo' && (newStatus === 'in_progress' || newStatus === 'review')) {
            return false;
        }
        return true;
    }
    
    if (isAssignee) {
        if (task.status === 'todo' && newStatus === 'in_progress') return true;
        if (task.status === 'in_progress' && newStatus === 'review') return true;
        return false;
    }
    
    return false;
}

function getUserRole(task) {
    if (task.creatorId === currentUser.id) return 'creator';
    if (task.assigneeId === currentUser.id) return 'assignee';
    if (task.watchers && task.watchers.includes(currentUser.id)) return 'watcher';
    return null;
}

// ==================== DRAG AND DROP (FIXED FOR MOBILE) ====================

let touchStartX = 0;
let touchStartY = 0;
let isDragging = false;

function setupDragAndDrop() {
    const cards = document.querySelectorAll('.task-card');
    const columns = document.querySelectorAll('.kanban-column-content');
    
    cards.forEach(card => {
        const taskId = card.dataset.taskId;
        const task = tasks.find(t => t.id === taskId);
        if (!task) return;
        
        const userRole = getUserRole(task);
        if (userRole === 'watcher' || !userRole) {
            card.draggable = false;
            card.style.cursor = 'pointer';
        } else {
            card.draggable = true;
            card.style.cursor = 'grab';
            
            // FIX: Предотвращаем конфликт с browser back gesture
            card.addEventListener('touchstart', handleTouchStart, { passive: true });
            card.addEventListener('touchmove', handleTouchMove, { passive: false });
            card.addEventListener('touchend', handleTouchEnd);
        }
        
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        card.addEventListener('click', (e) => {
            if (!e.target.closest('button') && !isDragging) {
                openTaskDetails(card.dataset.taskId);
            }
        });
    });
    
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
        column.addEventListener('dragleave', handleDragLeave);
        column.addEventListener('dragenter', handleDragEnter);
    });
}

// FIX: Touch handlers для предотвращения browser back
function handleTouchStart(e) {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    isDragging = false;
}

function handleTouchMove(e) {
    if (!touchStartX || !touchStartY) return;
    
    const diffX = Math.abs(e.touches[0].clientX - touchStartX);
    const diffY = Math.abs(e.touches[0].clientY - touchStartY);
    
    // Если свайп горизонтальный (перетаскивание карточки)
    if (diffX > 10 && diffX > diffY) {
        isDragging = true;
        e.preventDefault(); // Предотвращаем browser back
    }
}

function handleTouchEnd(e) {
    touchStartX = 0;
    touchStartY = 0;
    setTimeout(() => {
        isDragging = false;
    }, 100);
}

let draggedElement = null;
let draggedTask = null;

function handleDragStart(e) {
    draggedElement = this;
    const taskId = this.dataset.taskId;
    draggedTask = tasks.find(t => t.id === taskId);
    
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    draggedElement = null;
    draggedTask = null;
    
    document.querySelectorAll('.kanban-column-content').forEach(col => {
        col.classList.remove('drag-over');
        col.classList.remove('drag-forbidden');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) e.preventDefault();
    if (!draggedTask) return false;
    
    const newStatus = this.dataset.status;
    if (draggedTask.status === newStatus) {
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    const canMove = canMoveTask(draggedTask, newStatus);
    e.dataTransfer.dropEffect = canMove ? 'move' : 'none';
    return false;
}

function handleDragEnter(e) {
    if (!draggedTask) return;
    
    const newStatus = this.dataset.status;
    if (draggedTask.status === newStatus) {
        this.classList.remove('drag-over', 'drag-forbidden');
        return;
    }
    
    const canMove = canMoveTask(draggedTask, newStatus);
    if (!canMove) {
        this.classList.add('drag-forbidden');
        this.classList.remove('drag-over');
    } else {
        this.classList.remove('drag-forbidden');
        this.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    if (e.target === this) {
        this.classList.remove('drag-over', 'drag-forbidden');
    }
}

async function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    if (e.preventDefault) e.preventDefault();
    
    this.classList.remove('drag-over', 'drag-forbidden');
    
    if (!draggedTask || !draggedElement) return false;
    
    const taskId = draggedTask.id;
    const newStatus = this.dataset.status;
    
    if (draggedTask.status === newStatus) return false;
    
    const canMove = canMoveTask(draggedTask, newStatus);
    
    if (!canMove) {
        const role = getUserRole(draggedTask);
        let message = '';
        
        if (role === 'creator') {
            if (draggedTask.status === 'todo' && (newStatus === 'in_progress' || newStatus === 'review')) {
                message = 'Только исполнитель может начать работу над задачей';
            } else {
                message = 'Недопустимое изменение статуса';
            }
        } else if (role === 'assignee') {
            if (draggedTask.status === 'todo' && newStatus !== 'in_progress') {
                message = 'Вы можете перевести задачу только в статус "В работе"';
            } else if (draggedTask.status === 'in_progress' && newStatus !== 'review') {
                message = 'Вы можете отправить задачу только на проверку';
            } else if (draggedTask.status === 'review') {
                message = 'Только постановщик может принять работу или вернуть задачу';
            } else if (draggedTask.status === 'done') {
                message = 'Только постановщик может открыть выполненную задачу';
            } else {
                message = 'У вас нет прав для этого действия';
            }
        } else {
            message = 'У вас нет прав для изменения этой задачи';
        }
        
        showToast(message, 'warning');
        return false;
    }
    
    try {
        await updateTaskStatus(taskId, newStatus);
    } catch (error) {
        console.error('Error updating status:', error);
        showToast('Ошибка изменения статуса', 'error');
    }
    
    return false;
}

async function updateTaskStatus(taskId, newStatus) {
    try {
        await apiCall(`/api/tasks/${taskId}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus })
        });
        
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            task.status = newStatus;
            
            // Gamification при завершении
            if (newStatus === 'done' && gamification.enabled()) {
                setTimeout(() => {
                    gamification.showAchievement('Task Completed!', '🎉 Great job!');
                    gamification.showConfetti();
                    if (navigator.vibrate) navigator.vibrate([10, 50, 10]);
                }, 300);
            }
        }
        
        renderTasks();
        showToast('Статус задачи обновлен', 'success');
    } catch (error) {
        console.error('Error updating task status:', error);
        showToast('Ошибка обновления статуса', 'error');
    }
}

// ==================== CREATE/EDIT TASK ====================

function openCreateTaskModal() {
    const modal = document.getElementById('taskModal');
    modal.classList.add('active');
    
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('taskModalTitle').textContent = 'Новая задача';
    
    renderDepartmentsSelect();
    renderUsersSelect();
}

function renderDepartmentsSelect() {
    const select = document.getElementById('taskDepartment');
    if (!select) return;
    
    select.innerHTML = `
        <option value="">Выберите отдел</option>
        ${departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('')}
    `;
}

function renderUsersSelect() {
    const assigneeSelect = document.getElementById('taskAssignee');
    const watchersContainer = document.getElementById('taskWatchersContainer');
    
    if (assigneeSelect) {
        assigneeSelect.innerHTML = `
            <option value="">Не назначено</option>
            ${users.filter(u => u.id !== currentUser.id).map(u => `
                <option value="${u.id}">${u.name}</option>
            `).join('')}
        `;
    }
    
    if (watchersContainer) {
        watchersContainer.innerHTML = users.filter(u => u.id !== currentUser.id).map(u => `
            <label class="user-checkbox">
                <input type="checkbox" name="watchers" value="${u.id}">
                <div class="user-checkbox-content">
                    <div class="user-avatar-small" style="background: ${u.avatar ? 'transparent' : generateGradient(u.name)}">
                        ${u.avatar ? `<img src="${u.avatar}" alt="${u.name}">` : getUserInitials(u.name)}
                    </div>
                    <span>${u.name}</span>
                </div>
            </label>
        `).join('');
    }
}

async function saveTask() {
    const taskId = document.getElementById('taskId').value;
    const title = document.getElementById('taskTitle').value.trim();
    const description = document.getElementById('taskDescription').value.trim();
    const departmentId = document.getElementById('taskDepartment').value;
    const assigneeId = document.getElementById('taskAssignee').value;
    const priority = document.getElementById('taskPriority').value;
    const dueDate = document.getElementById('taskDueDate').value;
    
    const watchersCheckboxes = document.querySelectorAll('input[name="watchers"]:checked');
    const watchers = Array.from(watchersCheckboxes).map(cb => cb.value);
    
    if (!title) {
        showToast('Введите название задачи', 'warning');
        return;
    }
    
    if (!departmentId) {
        showToast('Выберите отдел', 'warning');
        return;
    }
    
    try {
        const taskData = {
            title,
            description,
            departmentId,
            assigneeId: assigneeId || null,
            priority,
            dueDate: dueDate || null,
            watchers
        };
        
        let savedTask;
        if (taskId) {
            savedTask = await apiCall(`/api/tasks/${taskId}`, {
                method: 'PUT',
                body: JSON.stringify(taskData)
            });
            
            const index = tasks.findIndex(t => t.id === taskId);
            if (index !== -1) {
                tasks[index] = savedTask;
            }
        } else {
            savedTask = await apiCall('/api/tasks', {
                method: 'POST',
                body: JSON.stringify(taskData)
            });
            
            if (savedTask.chatId) {
                try {
                    const taskChat = await apiCall(`/api/chats/${savedTask.chatId}`);
                    if (taskChat && !chats.find(c => c.id === taskChat.id)) {
                        chats.push(taskChat);
                    }
                } catch (err) {
                    console.warn('Could not fetch task chat:', err.message);
                }
            }
            
            tasks.push(savedTask);
        }
        
        closeTaskModal();
        renderTasks();
        showToast(taskId ? 'Задача обновлена' : 'Задача создана', 'success');
        
    } catch (error) {
        console.error('Error saving task:', error);
        showToast('Ошибка сохранения задачи: ' + (error.message || 'Неизвестная ошибка'), 'error');
    }
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.remove('active');
}

// ==================== TASK DETAILS (продолжение в следующем сообщении) ====================

// ... (остальной код tasks.js без изменений)

// ==================== HELPER FUNCTIONS ====================

function getStatusName(status) {
    const names = {
        'todo': 'К выполнению',
        'in_progress': 'В работе',
        'review': 'На проверке',
        'done': 'Выполнено'
    };
    return names[status] || status;
}

function getPriorityName(priority) {
    const names = {
        'low': 'Низкий',
        'normal': 'Обычный',
        'high': 'Высокий',
        'urgent': 'Срочный'
    };
    return names[priority] || priority;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Сегодня';
    } else if (date.toDateString() === tomorrow.toDateString()) {
        return 'Завтра';
    } else {
        return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
    }
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('ru-RU', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ==================== VIEW SWITCHING ====================

function switchTaskView(view) {
    currentTaskView = view;
    
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    
    const kanbanBoard = document.getElementById('kanbanBoard');
    const tasksList = document.getElementById('tasksList');
    const tasksArea = document.getElementById('tasksArea');
    
    if (tasksArea) {
        tasksArea.setAttribute('data-view', view);
    }
    
    if (view === 'board') {
        if (kanbanBoard) kanbanBoard.style.display = 'flex';
        if (tasksList) tasksList.style.display = 'none';
    } else {
        if (kanbanBoard) kanbanBoard.style.display = 'none';
        if (tasksList) tasksList.style.display = 'block';
    }
    
    renderTasks();
}

function switchTaskFilter(filter) {
    currentTaskFilter = filter;
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });
    
    renderTasks();
}

async function toggleTaskComplete(taskId, completed) {
    const newStatus = completed ? 'done' : 'todo';
    await updateTaskStatus(taskId, newStatus);
}

// ==================== INITIALIZATION ====================

function initTasksModule() {
    console.log('✅ Initializing tasks module with gamification...');
    
    // Load streak data
    if (gamification.enabled()) {
        gamification.loadStreak();
        injectGamifiedHeader();
    }
    
    // Event listeners
    const createTaskBtn = document.getElementById('createTaskBtn');
    const createTaskBtnSidebar = document.getElementById('createTaskBtnSidebar');
    const closeTaskModalBtn = document.getElementById('closeTaskModalBtn');
    const cancelTaskBtn = document.getElementById('cancelTaskBtn');
    const saveTaskBtn = document.getElementById('saveTaskBtn');
    
    if (createTaskBtn) createTaskBtn.addEventListener('click', openCreateTaskModal);
    if (createTaskBtnSidebar) createTaskBtnSidebar.addEventListener('click', openCreateTaskModal);
    if (closeTaskModalBtn) closeTaskModalBtn.addEventListener('click', closeTaskModal);
    if (cancelTaskBtn) cancelTaskBtn.addEventListener('click', closeTaskModal);
    if (saveTaskBtn) saveTaskBtn.addEventListener('click', saveTask);
    
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => switchTaskView(btn.dataset.view));
    });
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => switchTaskFilter(btn.dataset.filter));
    });
    
    // Resize handler
    window.addEventListener('resize', () => {
        if (gamification.enabled() && !document.querySelector('.tasks-greeting')) {
            injectGamifiedHeader();
        }
    });
    
    console.log('✅ Tasks module initialized');
}

// ==================== GAMIFIED HEADER INJECTION ====================

function injectGamifiedHeader() {
    const header = document.querySelector('.tasks-header');
    if (!header || header.querySelector('.tasks-header-main')) return;
    
    const userName = currentUser?.name || 'User';
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    const emoji = hour < 12 ? '☀️' : hour < 18 ? '👋' : '🌙';
    
    // Очищаем header (сохраняем только кнопку создания)
    const createBtn = header.querySelector('#createTaskBtn');
    header.innerHTML = '';
    
    const html = `
        <!-- Main title row -->
        <div class="tasks-header-main">
            <div class="tasks-header-title">
                <h2>📋 Задачи</h2>
            </div>
            
            <!-- View toggle -->
            <div class="view-toggle mobile-view-toggle">
                <button class="view-toggle-btn ${currentTaskView === 'board' ? 'active' : ''}" data-view="board" onclick="switchTaskView('board')">
                    <span class="material-icons">view_kanban</span>
                </button>
                <button class="view-toggle-btn ${currentTaskView === 'list' ? 'active' : ''}" data-view="list" onclick="switchTaskView('list')">
                    <span class="material-icons">view_list</span>
                </button>
            </div>
        </div>
        
        <!-- Greeting card -->
        <div class="tasks-greeting">
            <span class="tasks-greeting__emoji">${emoji}</span>
            <div class="tasks-greeting__text">
                <h3 class="tasks-greeting__title">${greeting}, ${userName}!</h3>
                <p class="tasks-greeting__subtitle">Let's crush some tasks today</p>
            </div>
        </div>
        
        <!-- Stats row (2 columns) -->
        <div class="tasks-stats-row">
            <div class="stat-card stat-card--streak">
                <span class="stat-card__icon">🔥</span>
                <div class="stat-card__content">
                    <div class="stat-card__value" id="gamified-streak">${gamification.data.streak}</div>
                    <div class="stat-card__label">Day Streak</div>
                </div>
            </div>
            
            <div class="stat-card stat-card--tasks">
                <span class="stat-card__icon">⭐</span>
                <div class="stat-card__content">
                    <div class="stat-card__value" id="gamified-completed">0/0</div>
                    <div class="stat-card__label">Tasks</div>
                </div>
            </div>
        </div>
        
        <!-- Daily progress -->
        <div class="daily-progress">
            <div class="daily-progress__header">
                <h3 class="daily-progress__title">📊 Daily Progress</h3>
                <span class="daily-progress__stats" id="gamified-percent">0%</span>
            </div>
            <div class="daily-progress__bar">
                <div class="daily-progress__fill" id="gamified-fill" style="width: 0%"></div>
            </div>
            <p class="daily-progress__message" id="gamified-message">
                <span class="daily-progress__message-icon">💪</span>
                <span>Start your first task!</span>
            </p>
        </div>
        
        <!-- Filters -->
        <div class="tasks-filters mobile-filters">
            <button class="filter-btn ${currentTaskFilter === 'all' ? 'active' : ''}" data-filter="all" onclick="switchTaskFilter('all')">
                Все
            </button>
            <button class="filter-btn ${currentTaskFilter === 'my' ? 'active' : ''}" data-filter="my" onclick="switchTaskFilter('my')">
                Мои
            </button>
            <button class="filter-btn ${currentTaskFilter === 'created' ? 'active' : ''}" data-filter="created" onclick="switchTaskFilter('created')">
                Созданные
            </button>
            <button class="filter-btn ${currentTaskFilter === 'watching' ? 'active' : ''}" data-filter="watching" onclick="switchTaskFilter('watching')">
                Наблюдаю
            </button>
        </div>
    `;
    
    header.insertAdjacentHTML('afterbegin', html);
    
    // Возвращаем кнопку создания если была
    if (createBtn) {
        header.appendChild(createBtn);
    }
    
    console.log('✅ Improved gamified header injected');
}

// ==================== ANIMATIONS CSS ====================

// Добавляем стили для анимаций
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            transform: translateX(-50%) translateY(-120%);
            opacity: 0;
        }
        to {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        from {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        to {
            transform: translateX(-50%) translateY(-120%);
            opacity: 0;
        }
    }
    
    @keyframes confettiFall {
        from {
            transform: translateY(-10vh) rotate(0deg);
            opacity: 1;
        }
        to {
            transform: translateY(110vh) rotate(720deg);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTasksModule);
} else {
    initTasksModule();
}

// ==================== SOCKET LISTENERS ====================

function setupTasksSocketListeners() {
    if (typeof socket === 'undefined' || !socket) {
        console.warn('Socket not available for tasks module');
        return;
    }
    
    socket.on('task:created', (task) => {
        tasks.push(task);
        renderTasks();
        showToast('Новая задача создана', 'info');
    });
    
    socket.on('task:updated', (updatedTask) => {
        const index = tasks.findIndex(t => t.id === updatedTask.id);
        if (index !== -1) {
            tasks[index] = updatedTask;
            renderTasks();
        }
    });
    
    socket.on('task:deleted', (taskId) => {
        tasks = tasks.filter(t => t.id !== taskId);
        renderTasks();
    });
    
    socket.on('task:comment', (data) => {
        const task = tasks.find(t => t.id === data.taskId);
        if (task) {
            task.commentsCount = (task.commentsCount || 0) + 1;
            if (data.userId !== currentUser.id) {
                task.hasUnread = true;
            }
            renderTasks();
        }
    });
    
    console.log('✅ Tasks socket listeners setup');
}

// ==================== GLOBAL EXPORTS ====================

window.loadTasks = loadTasks;
window.loadDepartments = loadDepartments;
window.setupTasksSocketListeners = setupTasksSocketListeners;
window.toggleTaskComplete = toggleTaskComplete;
window.openTaskDetails = openTaskDetails;
window.closeTaskDetailsModal = closeTaskDetailsModal;

// Expose gamification for manual control if needed
window.taskGamification = gamification;

console.log('✅ Tasks module loaded with integrated gamification');



// ==================== TASK DETAILS MODAL ====================
// Продолжение tasks-integrated.js

async function openTaskDetails(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    const modal = document.getElementById('taskDetailsModal');
    const content = document.getElementById('taskDetailsContent');
    
    const assignee = users.find(u => u.id === task.assigneeId);
    const creator = users.find(u => u.id === task.creatorId);
    const department = departments.find(d => d.id === task.departmentId);
    const taskWatchers = task.watchers ? task.watchers.map(wId => users.find(u => u.id === wId)).filter(Boolean) : [];
    
    const userRole = getUserRole(task);
    const isCreator = task.creatorId === currentUser.id;
    const isAssignee = task.assigneeId === currentUser.id;
    
    content.innerHTML = `
        <div class="task-details-header">
            <div>
                <h2>${task.title}</h2>
                <div class="task-details-meta">
                    <span class="task-status-badge ${task.status}">${getStatusName(task.status)}</span>
                    ${task.priority !== 'normal' ? `<span class="task-priority-badge ${task.priority}">${getPriorityName(task.priority)}</span>` : ''}
                    ${department ? `<span class="task-department-badge">${department.name}</span>` : ''}
                    ${userRole ? `<span class="task-role-badge">${getRoleName(userRole)}</span>` : ''}
                </div>
            </div>
            <button class="icon-btn" onclick="closeTaskDetailsModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        
        <div class="task-details-body">
            ${task.description ? `
                <div class="task-details-section">
                    <h4>Описание</h4>
                    <p>${task.description}</p>
                </div>
            ` : ''}
            
            <div class="task-details-section">
                <h4>Детали</h4>
                <div class="task-details-grid">
                    <div class="task-detail-item">
                        <span class="task-detail-label">Постановщик</span>
                        <div class="task-detail-value">
                            ${creator ? `
                                <div class="user-chip">
                                    <div class="user-avatar-small" style="background: ${creator.avatar ? 'transparent' : generateGradient(creator.name)}">
                                        ${creator.avatar ? `<img src="${creator.avatar}" alt="${creator.name}">` : getUserInitials(creator.name)}
                                    </div>
                                    <span>${creator.name}</span>
                                </div>
                            ` : 'Не указан'}
                        </div>
                    </div>
                    
                    <div class="task-detail-item">
                        <span class="task-detail-label">Исполнитель</span>
                        <div class="task-detail-value">
                            ${assignee ? `
                                <div class="user-chip">
                                    <div class="user-avatar-small" style="background: ${assignee.avatar ? 'transparent' : generateGradient(assignee.name)}">
                                        ${assignee.avatar ? `<img src="${assignee.avatar}" alt="${assignee.name}">` : getUserInitials(assignee.name)}
                                    </div>
                                    <span>${assignee.name}</span>
                                </div>
                            ` : 'Не назначено'}
                        </div>
                    </div>
                    
                    ${task.dueDate ? `
                        <div class="task-detail-item">
                            <span class="task-detail-label">Срок выполнения</span>
                            <div class="task-detail-value">
                                <span class="material-icons">schedule</span>
                                ${formatDate(task.dueDate)}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="task-detail-item">
                        <span class="task-detail-label">Создано</span>
                        <div class="task-detail-value">
                            ${formatDateTime(task.createdAt)}
                        </div>
                    </div>
                </div>
            </div>
            
            ${taskWatchers.length > 0 ? `
                <div class="task-details-section">
                    <h4>Наблюдатели (${taskWatchers.length})</h4>
                    <div class="task-watchers-list">
                        ${taskWatchers.map(w => `
                            <div class="user-chip">
                                <div class="user-avatar-small" style="background: ${w.avatar ? 'transparent' : generateGradient(w.name)}">
                                    ${w.avatar ? `<img src="${w.avatar}" alt="${w.name}">` : getUserInitials(w.name)}
                                </div>
                                <span>${w.name}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
        
        <div class="task-details-footer">
            ${isCreator ? `
                <button class="btn-secondary" onclick="editTask('${task.id}')">
                    <span class="material-icons">edit</span>
                    Редактировать
                </button>
                
                ${task.status === 'review' ? `
                    <button class="btn-success" onclick="updateTaskStatus('${task.id}', 'done')">
                        <span class="material-icons">check_circle</span>
                        Принять
                    </button>
                    <button class="btn-warning" onclick="updateTaskStatus('${task.id}', 'in_progress')">
                        <span class="material-icons">replay</span>
                        В работу
                    </button>
                ` : ''}
                
                ${task.status === 'done' ? `
                    <button class="btn-warning" onclick="updateTaskStatus('${task.id}', 'todo')">
                        <span class="material-icons">replay</span>
                        Открыть
                    </button>
                ` : ''}
                
                ${task.status !== 'done' ? `
                    <button class="btn-success" onclick="updateTaskStatus('${task.id}', 'done'); closeTaskDetailsModal();">
                        <span class="material-icons">check_circle</span>
                        Завершить
                    </button>
                ` : ''}
            ` : ''}
            
            ${isAssignee && !isCreator ? `
                ${task.status === 'todo' ? `
                    <button class="btn-primary" onclick="updateTaskStatus('${task.id}', 'in_progress'); closeTaskDetailsModal();">
                        <span class="material-icons">play_arrow</span>
                        Начать
                    </button>
                ` : ''}
                
                ${task.status === 'in_progress' ? `
                    <button class="btn-success" onclick="updateTaskStatus('${task.id}', 'review'); closeTaskDetailsModal();">
                        <span class="material-icons">send</span>
                        На проверку
                    </button>
                ` : ''}
            ` : ''}
            
            <button class="btn-primary" onclick="openTaskChat('${task.id}')">
                <span class="material-icons">chat</span>
                Чат
                ${task.commentsCount > 0 ? `<span class="badge">${task.commentsCount}</span>` : ''}
            </button>
        </div>
    `;
    
    modal.classList.add('active');
}

function getRoleName(role) {
    const names = {
        'creator': 'Постановщик',
        'assignee': 'Исполнитель',
        'watcher': 'Наблюдатель'
    };
    return names[role] || role;
}

function closeTaskDetailsModal() {
    document.getElementById('taskDetailsModal').classList.remove('active');
}

async function editTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    closeTaskDetailsModal();
    
    const modal = document.getElementById('taskModal');
    modal.classList.add('active');
    
    document.getElementById('taskId').value = task.id;
    document.getElementById('taskTitle').value = task.title;
    document.getElementById('taskDescription').value = task.description || '';
    document.getElementById('taskDepartment').value = task.departmentId;
    document.getElementById('taskAssignee').value = task.assigneeId || '';
    document.getElementById('taskPriority').value = task.priority;
    document.getElementById('taskDueDate').value = task.dueDate ? task.dueDate.split('T')[0] : '';
    
    document.getElementById('taskModalTitle').textContent = 'Редактировать задачу';
    
    renderDepartmentsSelect();
    renderUsersSelect();
    
    setTimeout(() => {
        document.getElementById('taskDepartment').value = task.departmentId;
        document.getElementById('taskAssignee').value = task.assigneeId || '';
        
        if (task.watchers) {
            task.watchers.forEach(watcherId => {
                const checkbox = document.querySelector(`input[name="watchers"][value="${watcherId}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
    }, 100);
}

// ==================== TASK CHAT ====================

async function openTaskChat(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) {
        console.error('Task not found:', taskId);
        showToast('Задача не найдена', 'error');
        return;
    }
    
    let taskChat = chats.find(c => c.taskId === taskId);
    
    if (!taskChat && task.chatId) {
        taskChat = chats.find(c => c.id === task.chatId);
    }
    
    if (!taskChat) {
        try {
            taskChat = await apiCall('/api/task-chats', {
                method: 'POST',
                body: JSON.stringify({ taskId })
            });
            
            if (!chats.find(c => c.id === taskChat.id)) {
                chats.push(taskChat);
                
                if (typeof renderChats === 'function') {
                    renderChats();
                }
            }
        } catch (error) {
            console.error('Error creating task chat:', error);
            showToast('Ошибка создания чата', 'error');
            return;
        }
    } else {
        if (!chats.find(c => c.id === taskChat.id)) {
            chats.push(taskChat);
            if (typeof renderChats === 'function') {
                renderChats();
            }
        }
    }
    
    closeTaskDetailsModal();
    closeTaskModal();
    
    const chatsTab = document.querySelector('.tab-btn[data-tab="chats"]');
    if (chatsTab) {
        chatsTab.click();
    }
    
    setTimeout(() => {
        if (typeof openChat === 'function') {
            openChat(taskChat);
        } else {
            console.error('openChat function not found');
            showToast('Ошибка открытия чата', 'error');
        }
    }, 200);
    
    task.hasUnread = false;
    renderTasks();
}

window.openTaskChat = openTaskChat;
window.editTask = editTask;

// ==================== EXPORTS ====================

console.log('✅ Tasks module part 2 loaded');