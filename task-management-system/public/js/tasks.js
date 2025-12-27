// ==================== TASKS MANAGEMENT ====================

let tasks = [];
let departments = [];
let currentTaskView = 'board'; // 'board' or 'list'
let currentTaskFilter = 'all'; // 'all', 'my', 'created', 'watching'

// ==================== LOAD TASKS ====================

async function loadTasks() {
    try {
        const data = await apiCall('/api/tasks');
        tasks = data;
        renderTasks();
    } catch (error) {
        console.error('Error loading tasks:', error);
        showToast('Ошибка загрузки задач', 'error');
    }
}

async function loadDepartments() {
    try {
        const data = await apiCall('/api/departments');
        departments = data;
        // renderDepartmentsList вызывается только при открытии модала
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

    // Add drag and drop
    setupDragAndDrop();
}

function renderTaskCard(task) {
    const assignee = users.find(u => u.id === task.assigneeId);
    const creator = users.find(u => u.id === task.creatorId);
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

    return `
        <div class="task-card" data-task-id="${task.id}" draggable="true">
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
        const creator = users.find(u => u.id === task.creatorId);
        const department = departments.find(d => d.id === task.departmentId);
        
        const isOverdue = task.dueDate && new Date(task.dueDate) < new Date() && task.status !== 'done';
        
        return `
            <div class="task-list-item ${task.status === 'done' ? 'completed' : ''}" data-task-id="${task.id}">
                <div class="task-list-checkbox">
                    <input type="checkbox" ${task.status === 'done' ? 'checked' : ''} onchange="toggleTaskComplete('${task.id}', this.checked)">
                </div>
                
                <div class="task-list-content">
                    <div class="task-list-header">
                        <h4 class="task-title">${task.title}</h4>
                        ${task.hasUnread ? '<span class="task-unread-dot"></span>' : ''}
                    </div>
                    
                    <div class="task-list-meta">
                        ${department ? `<span class="task-department">${department.name}</span>` : ''}
                        ${assignee ? `<span class="task-assignee-chip">${assignee.name}</span>` : ''}
                        ${task.dueDate ? `<span class="task-due-date ${isOverdue ? 'overdue' : ''}">${formatDate(task.dueDate)}</span>` : ''}
                        <span class="task-status-badge ${task.status}">${getStatusName(task.status)}</span>
                    </div>
                </div>
                
                <div class="task-list-actions">
                    <button class="icon-btn" onclick="openTaskChat('${task.id}')">
                        <span class="material-icons">chat</span>
                        ${task.commentsCount > 0 ? `<span class="badge">${task.commentsCount}</span>` : ''}
                    </button>
                    <button class="icon-btn" onclick="openTaskDetails('${task.id}')">
                        <span class="material-icons">more_vert</span>
                    </button>
                </div>
            </div>
        `;
    }).join('');
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
    // Только постановщик может редактировать задачу
    return task.creatorId === currentUser.id;
}

function canDeleteTask(task) {
    // Только постановщик может удалить задачу
    return task.creatorId === currentUser.id;
}

function canCompleteTask(task) {
    // Только постановщик может закрыть задачу
    return task.creatorId === currentUser.id;
}

function canReopenTask(task) {
    // Только постановщик может вернуть задачу в работу
    return task.creatorId === currentUser.id && task.status === 'done';
}

function canStartTask(task) {
    // Исполнитель может начать задачу (todo -> in_progress)
    return task.assigneeId === currentUser.id && task.status === 'todo';
}

function canSendToReview(task) {
    // Исполнитель может отправить на проверку (in_progress -> review)
    return task.assigneeId === currentUser.id && task.status === 'in_progress';
}

function canMoveTask(task, newStatus) {
    const isCreator = task.creatorId === currentUser.id;
    const isAssignee = task.assigneeId === currentUser.id;
    
    console.log('canMoveTask check:', {
        isCreator,
        isAssignee,
        currentStatus: task.status,
        newStatus: newStatus,
        creatorId: task.creatorId,
        assigneeId: task.assigneeId,
        currentUserId: currentUser.id
    });
    
    // ПОСТАНОВЩИК
    if (isCreator) {
        // Постановщик НЕ МОЖЕТ переводить из "К выполнению" в "В работе" или "На проверке"
        if (task.status === 'todo' && (newStatus === 'in_progress' || newStatus === 'review')) {
            console.log('❌ Creator cannot: todo -> in_progress/review');
            return false;
        }
        
        // Все остальные переходы разрешены
        console.log('✅ Creator can make this move');
        return true;
    }
    
    // ИСПОЛНИТЕЛЬ
    if (isAssignee) {
        // Исполнитель может ТОЛЬКО: todo -> in_progress, in_progress -> review
        if (task.status === 'todo' && newStatus === 'in_progress') {
            console.log('✅ Assignee: todo -> in_progress');
            return true;
        }
        if (task.status === 'in_progress' && newStatus === 'review') {
            console.log('✅ Assignee: in_progress -> review');
            return true;
        }
        
        // ВСЕ остальные переходы ЗАПРЕЩЕНЫ
        console.log('❌ Assignee cannot make this move');
        return false;
    }
    
    // Наблюдатель или посторонний - нет прав
    console.log('❌ No rights (watcher or stranger)');
    return false;
}


function getUserRole(task) {
    if (task.creatorId === currentUser.id) return 'creator';
    if (task.assigneeId === currentUser.id) return 'assignee';
    if (task.watchers && task.watchers.includes(currentUser.id)) return 'watcher';
    return null;
}

// ==================== DRAG AND DROP ====================

function setupDragAndDrop() {
    const cards = document.querySelectorAll('.task-card');
    const columns = document.querySelectorAll('.kanban-column-content');
    
    cards.forEach(card => {
        const taskId = card.dataset.taskId;
        const task = tasks.find(t => t.id === taskId);
        
        if (!task) return;
        
        // Проверяем может ли пользователь вообще перемещать эту задачу
        const userRole = getUserRole(task);
        if (userRole === 'watcher' || !userRole) {
            // Наблюдатели и посторонние не могут перемещать
            card.draggable = false;
            card.style.cursor = 'pointer';
        } else {
            card.draggable = true;
            card.style.cursor = 'grab';
        }
        
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        card.addEventListener('click', (e) => {
            if (!e.target.closest('button')) {
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
    if (e.preventDefault) {
        e.preventDefault();
    }
    
    if (!draggedTask) return false;
    
    const newStatus = this.dataset.status;
    
    // Если это та же колонка - разрешаем
    if (draggedTask.status === newStatus) {
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    // Проверяем права
    const canMove = canMoveTask(draggedTask, newStatus);
    
    if (canMove) {
        e.dataTransfer.dropEffect = 'move';
    } else {
        e.dataTransfer.dropEffect = 'none';
    }
    
    return false;
}

function handleDragEnter(e) {
    if (!draggedTask) return;
    
    const newStatus = this.dataset.status;
    
    // Если это та же колонка - не подсвечиваем
    if (draggedTask.status === newStatus) {
        this.classList.remove('drag-over');
        this.classList.remove('drag-forbidden');
        return;
    }
    
    // Проверяем можно ли переместить
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
    // Проверяем что мы действительно покидаем элемент, а не его дочерний элемент
    if (e.target === this) {
        this.classList.remove('drag-over');
        this.classList.remove('drag-forbidden');
    }
}



async function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    if (e.preventDefault) {
        e.preventDefault();
    }
    
    this.classList.remove('drag-over');
    this.classList.remove('drag-forbidden');
    
    if (!draggedTask || !draggedElement) {
        console.log('No dragged task');
        return false;
    }
    
    const taskId = draggedTask.id;
    const newStatus = this.dataset.status;
    
    console.log('=== DROP TASK ===');
    console.log('Task:', draggedTask.title);
    console.log('Current status:', draggedTask.status);
    console.log('New status:', newStatus);
    
    // Если статус не изменился, ничего не делаем
    if (draggedTask.status === newStatus) {
        console.log('Same status, ignoring');
        return false;
    }
    
    // Проверяем права на перемещение
    const canMove = canMoveTask(draggedTask, newStatus);
    console.log('Can move:', canMove);
    
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
        } else if (role === 'watcher') {
            message = 'Вы наблюдатель и не можете изменять статус задачи';
        } else {
            message = 'У вас нет прав для изменения этой задачи';
        }
        
        console.log('❌ Permission denied:', message);
        showToast(message, 'warning');
        
        return false;
    }
    
    console.log('✅ Updating task status...');
    
    // Update task status
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
    
    // Reset form
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('taskModalTitle').textContent = 'Новая задача';
    
    // Load departments and users
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
            // Update existing task
            console.log('Updating task:', taskId);
            savedTask = await apiCall(`/api/tasks/${taskId}`, {
                method: 'PUT',
                body: JSON.stringify(taskData)
            });
            
            const index = tasks.findIndex(t => t.id === taskId);
            if (index !== -1) {
                tasks[index] = savedTask;
            }
        } else {
            // Create new task
            console.log('Creating new task');
            savedTask = await apiCall('/api/tasks', {
                method: 'POST',
                body: JSON.stringify(taskData)
            });
            
            console.log('✅ Task created:', savedTask);
            
            // Если чат был создан вместе с задачей, добавим его в массив
            if (savedTask.chatId) {
                try {
                    const taskChat = await apiCall(`/api/chats/${savedTask.chatId}`);
                    if (taskChat && !chats.find(c => c.id === taskChat.id)) {
                        chats.push(taskChat);
                        console.log('✅ Task chat added to chats array');
                    }
                } catch (err) {
                    console.warn('⚠️ Could not fetch task chat:', err.message);
                }
            }
            
            tasks.push(savedTask);
        }
        
        closeTaskModal();
        renderTasks();
        showToast(taskId ? 'Задача обновлена' : 'Задача создана', 'success');
        
    } catch (error) {
        console.error('Error saving task:', error);
        console.error('Error details:', error.message);
        showToast('Ошибка сохранения задачи: ' + (error.message || 'Неизвестная ошибка'), 'error');
    }
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.remove('active');
}

// ==================== TASK DETAILS ====================

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
            
            ${!isCreator && !isAssignee && userRole === 'watcher' ? `
                <div class="task-details-section">
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                        <p style="margin: 0; color: var(--text-secondary);">
                            <span class="material-icons" style="vertical-align: middle; font-size: 20px;">visibility</span>
                            Вы наблюдатель этой задачи. Вы можете просматривать и комментировать, но не можете редактировать.
                        </p>
                    </div>
                </div>
            ` : ''}
        </div>
        
        <div class="task-details-footer">
            <!-- Кнопки для ПОСТАНОВЩИКА -->
            ${isCreator ? `
                <button class="btn-secondary" onclick="editTask('${task.id}')">
                    <span class="material-icons">edit</span>
                    Редактировать
                </button>
                
                <!-- Постановщик НЕ МОЖЕТ переводить из todo в in_progress/review -->
                <!-- Это делает только исполнитель -->
                
                ${task.status === 'in_progress' ? `
                    <button class="btn-warning" onclick="changeTaskStatus('${task.id}', 'todo')">
                        <span class="material-icons">replay</span>
                        Вернуть к выполнению
                    </button>
                ` : ''}
                
                ${task.status === 'review' ? `
                    <button class="btn-success" onclick="changeTaskStatus('${task.id}', 'done')">
                        <span class="material-icons">check_circle</span>
                        Принять работу
                    </button>
                    <button class="btn-warning" onclick="changeTaskStatus('${task.id}', 'in_progress')">
                        <span class="material-icons">replay</span>
                        Вернуть в работу
                    </button>
                    <button class="btn-secondary" onclick="changeTaskStatus('${task.id}', 'todo')">
                        <span class="material-icons">undo</span>
                        Вернуть к выполнению
                    </button>
                ` : ''}
                
                ${task.status === 'done' ? `
                    <button class="btn-warning" onclick="changeTaskStatus('${task.id}', 'todo')">
                        <span class="material-icons">replay</span>
                        Открыть заново
                    </button>
                ` : ''}
                
                ${task.status !== 'done' ? `
                    <button class="btn-success" onclick="changeTaskStatus('${task.id}', 'done')">
                        <span class="material-icons">check_circle</span>
                        Завершить
                    </button>
                ` : ''}
            ` : ''}
            
            <!-- Кнопки для ИСПОЛНИТЕЛЯ (если не постановщик) -->
            ${isAssignee && !isCreator ? `
                ${task.status === 'todo' ? `
                    <button class="btn-primary" onclick="changeTaskStatus('${task.id}', 'in_progress')">
                        <span class="material-icons">play_arrow</span>
                        Начать работу
                    </button>
                ` : ''}
                
                ${task.status === 'in_progress' ? `
                    <button class="btn-success" onclick="changeTaskStatus('${task.id}', 'review')">
                        <span class="material-icons">send</span>
                        На проверку
                    </button>
                ` : ''}
            ` : ''}
            
            <!-- Чат доступен ВСЕМ участникам -->
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



// Действия исполнителя
async function startTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task || !canStartTask(task)) {
        showToast('У вас нет прав для этого действия', 'error');
        return;
    }
    
    await updateTaskStatus(taskId, 'in_progress');
    closeTaskDetailsModal();
    showToast('Задача переведена в работу', 'success');
}

async function sendToReview(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task || !canSendToReview(task)) {
        showToast('У вас нет прав для этого действия', 'error');
        return;
    }
    
    await updateTaskStatus(taskId, 'review');
    closeTaskDetailsModal();
    showToast('Задача отправлена на проверку', 'success');
}

// Действия постановщика
async function approveTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task || !canCompleteTask(task)) {
        showToast('У вас нет прав для этого действия', 'error');
        return;
    }
    
    await updateTaskStatus(taskId, 'done');
    closeTaskDetailsModal();
    showToast('Задача выполнена', 'success');
}

async function returnTaskToWork(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task || task.creatorId !== currentUser.id) {
        showToast('У вас нет прав для этого действия', 'error');
        return;
    }
    
    await updateTaskStatus(taskId, 'in_progress');
    closeTaskDetailsModal();
    showToast('Задача возвращена в работу', 'success');
}

async function reopenTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task || !canReopenTask(task)) {
        showToast('У вас нет прав для этого действия', 'error');
        return;
    }
    
    await updateTaskStatus(taskId, 'todo');
    closeTaskDetailsModal();
    showToast('Задача открыта заново', 'success');
}

// Глобальные функции
window.startTask = startTask;
window.sendToReview = sendToReview;
window.approveTask = approveTask;
window.returnTaskToWork = returnTaskToWork;
window.reopenTask = reopenTask;

function closeTaskDetailsModal() {
    document.getElementById('taskDetailsModal').classList.remove('active');
}

async function editTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    closeTaskDetailsModal();
    
    const modal = document.getElementById('taskModal');
    modal.classList.add('active');
    
    // Fill form with task data
    document.getElementById('taskId').value = task.id;
    document.getElementById('taskTitle').value = task.title;
    document.getElementById('taskDescription').value = task.description || '';
    document.getElementById('taskDepartment').value = task.departmentId;
    document.getElementById('taskAssignee').value = task.assigneeId || '';
    document.getElementById('taskPriority').value = task.priority;
    document.getElementById('taskDueDate').value = task.dueDate ? task.dueDate.split('T')[0] : '';
    
    document.getElementById('taskModalTitle').textContent = 'Редактировать задачу';
    
    // Load selects first
    renderDepartmentsSelect();
    renderUsersSelect();
    
    // Then set values
    setTimeout(() => {
        document.getElementById('taskDepartment').value = task.departmentId;
        document.getElementById('taskAssignee').value = task.assigneeId || '';
        
        // Set watchers
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
        console.error('❌ Task not found:', taskId);
        showToast('Задача не найдена', 'error');
        return;
    }
    
    console.log('📞 Opening chat for task:', taskId, 'Task data:', task);
    
    // Найти чат для задачи
    let taskChat = chats.find(c => c.taskId === taskId);
    
    // Если чат уже был создан вместе с задачей
    if (!taskChat && task.chatId) {
        taskChat = chats.find(c => c.id === task.chatId);
    }
    
    if (!taskChat) {
        try {
            console.log('🔨 Creating new task chat for task:', taskId);
            
            // Создать чат для задачи
            taskChat = await apiCall('/api/task-chats', {
                method: 'POST',
                body: JSON.stringify({ taskId })
            });
            
            console.log('✅ Task chat created:', taskChat);
            
            // Добавляем в массив чатов
            if (!chats.find(c => c.id === taskChat.id)) {
                chats.push(taskChat);
                console.log('✅ Chat added to chats array');
                
                // ВАЖНО: Обновляем список чатов в сайдбаре
                if (typeof renderChats === 'function') {
                    renderChats();
                    console.log('✅ Chats list updated');
                }
            }
            
        } catch (error) {
            console.error('❌ Error creating task chat:', error);
            console.error('Error details:', error.message);
            showToast('Ошибка создания чата: ' + (error.message || 'Неизвестная ошибка'), 'error');
            return;
        }
    } else {
        console.log('✅ Task chat already exists:', taskChat.id);
        
        // Убедимся что чат есть в списке
        if (!chats.find(c => c.id === taskChat.id)) {
            chats.push(taskChat);
            if (typeof renderChats === 'function') {
                renderChats();
            }
        }
    }
    
    // Закрыть модальные окна задач
    closeTaskDetailsModal();
    closeTaskModal();
    
    // Переключиться на вкладку чатов
    const chatsTab = document.querySelector('.tab-btn[data-tab="chats"]');
    if (chatsTab) {
        console.log('🔄 Switching to chats tab');
        chatsTab.click();
    }
    
    // Небольшая задержка чтобы вкладка переключилась и список обновился
    setTimeout(() => {
        // Открыть чат
        if (typeof openChat === 'function') {
            console.log('📱 Opening chat:', taskChat);
            openChat(taskChat);
        } else {
            console.error('❌ openChat function not found');
            showToast('Ошибка открытия чата', 'error');
        }
    }, 200);
    
    // Пометить что у задачи больше нет непрочитанных
    task.hasUnread = false;
    renderTasks();
}

// ==================== TOGGLE TASK COMPLETE ====================

async function toggleTaskComplete(taskId, completed) {
    const newStatus = completed ? 'done' : 'todo';
    await updateTaskStatus(taskId, newStatus);
}

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
    
    document.getElementById('kanbanBoard').style.display = view === 'board' ? 'flex' : 'none';
    document.getElementById('tasksList').style.display = view === 'list' ? 'block' : 'none';
    
    renderTasks();
}

function switchTaskFilter(filter) {
    currentTaskFilter = filter;
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });
    
    renderTasks();
}

// ==================== INITIALIZATION ====================

function initTasksModule() {
    console.log('Initializing tasks module...');
    
    // Event listeners
    const createTaskBtn = document.getElementById('createTaskBtn');
    const createTaskBtnSidebar = document.getElementById('createTaskBtnSidebar');
    const closeTaskModalBtn = document.getElementById('closeTaskModalBtn');
    const cancelTaskBtn = document.getElementById('cancelTaskBtn');
    const saveTaskBtn = document.getElementById('saveTaskBtn');
    
    if (createTaskBtn) {
        createTaskBtn.addEventListener('click', openCreateTaskModal);
    }
    
    if (createTaskBtnSidebar) {
        createTaskBtnSidebar.addEventListener('click', openCreateTaskModal);
    }
    
    if (closeTaskModalBtn) {
        closeTaskModalBtn.addEventListener('click', closeTaskModal);
    }
    
    if (cancelTaskBtn) {
        cancelTaskBtn.addEventListener('click', closeTaskModal);
    }
    
    if (saveTaskBtn) {
        saveTaskBtn.addEventListener('click', saveTask);
    }
    
    // View toggle buttons
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => switchTaskView(btn.dataset.view));
    });
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => switchTaskFilter(btn.dataset.filter));
    });
    
    console.log('✅ Tasks module initialized');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTasksModule);
} else {
    initTasksModule();
}

// ==================== SOCKET LISTENERS ====================

// Будут добавлены после подключения socket в app.js
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

// Make functions globally available
window.openTaskChat = openTaskChat;
window.toggleTaskComplete = toggleTaskComplete;
window.openTaskDetails = openTaskDetails;
window.editTask = editTask;
window.closeTaskDetailsModal = closeTaskDetailsModal;
window.loadTasks = loadTasks;
window.loadDepartments = loadDepartments;
window.setupTasksSocketListeners = setupTasksSocketListeners;

console.log('✅ Tasks module loaded');