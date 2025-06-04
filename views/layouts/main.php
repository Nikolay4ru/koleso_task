<?php
// Получаем текущий URL для активного пункта меню
$currentPage = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Получаем количество непрочитанных уведомлений
// Проверяем, что у нас есть подключение к БД и пользователь авторизован
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    // Используем AJAX для получения количества уведомлений, чтобы не создавать зависимость от $db
    // Количество будет загружено асинхронно
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/dashboard">
            <i class="bi bi-kanban me-2"></i>
            Система задач
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === '/dashboard' ? 'active' : '' ?>" href="/dashboard">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Главная
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= strpos($currentPage, '/tasks') !== false ? 'active' : '' ?>" href="#" id="tasksDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-list-task me-1"></i>
                        Задачи
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/tasks/kanban">
                                <i class="bi bi-kanban me-2"></i>
                                Канбан доска
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/tasks/list">
                                <i class="bi bi-list-ul me-2"></i>
                                Список задач
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/tasks/create">
                                <i class="bi bi-plus-circle me-2"></i>
                                Создать задачу
                            </a>
                        </li>
                    </ul>
                </li>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear me-1"></i>
                        Администрирование
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/admin/users">
                                <i class="bi bi-people me-2"></i>
                                Пользователи
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/departments">
                                <i class="bi bi-building me-2"></i>
                                Отделы
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/admin/reports">
                                <i class="bi bi-graph-up me-2"></i>
                                Отчеты
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <!-- Уведомления -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                            <span id="notification-count">0</span>
                            <span class="visually-hidden">непрочитанных уведомлений</span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                            Уведомления
                            <a href="/notifications" class="text-decoration-none small">Все</a>
                        </h6>
                        <div id="notification-list">
                            <div class="text-center text-muted p-3">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                
                <!-- Профиль пользователя -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['user_name'] ?? 'Пользователь') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/profile">
                                <i class="bi bi-person me-2"></i>
                                Мой профиль
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/profile/settings">
                                <i class="bi bi-gear me-2"></i>
                                Настройки
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/logout">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Выйти
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar-dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.3rem;
}

.nav-link {
    font-weight: 500;
    transition: all 0.2s;
}

.nav-link:hover {
    transform: translateY(-1px);
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.notification-dropdown {
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e9ecef;
    transition: background 0.2s;
    cursor: pointer;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #e8f4ff;
    border-left: 3px solid #007bff;
}

.notification-item:last-child {
    border-bottom: none;
}
</style>

<script>
// Загружаем количество непрочитанных уведомлений при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationBadge();
});

// Функция обновления счетчика уведомлений
function updateNotificationBadge() {
    fetch('/notifications/recent')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notification-badge');
            const count = document.getElementById('notification-count');
            
            if (data.unread_count > 0) {
                count.textContent = data.unread_count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading notification count:', error);
        });
}

// Загрузка уведомлений при клике на иконку
document.getElementById('notificationDropdown').addEventListener('click', function() {
    fetch('/notifications/recent')
        .then(response => response.json())
        .then(data => {
            const listContainer = document.getElementById('notification-list');
            
            if (data.notifications.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center text-muted p-3">
                        Нет новых уведомлений
                    </div>
                `;
            } else {
                listContainer.innerHTML = data.notifications.map(notification => `
                    <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
                         onclick="markAsRead(${notification.id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 small">${escapeHtml(notification.title)}</h6>
                                <p class="text-muted small mb-0">${escapeHtml(notification.message)}</p>
                            </div>
                            <small class="text-muted">
                                ${formatTimeAgo(notification.created_at)}
                            </small>
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            document.getElementById('notification-list').innerHTML = `
                <div class="text-center text-danger p-3">
                    Ошибка загрузки уведомлений
                </div>
            `;
        });
});

// Отметить уведомление как прочитанное
function markAsRead(notificationId) {
    fetch(`/notifications/mark-read/${notificationId}`, {
        method: 'POST'
    }).then(() => {
        // Обновляем счетчик
        updateNotificationBadge();
        
        // Убираем класс unread с элемента
        const element = document.querySelector(`[onclick="markAsRead(${notificationId})"]`);
        if (element) {
            element.classList.remove('unread');
        }
    });
}

// Форматирование времени
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'только что';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' мин. назад';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' ч. назад';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' дн. назад';
    
    // Если больше недели, показываем дату
    return date.toLocaleDateString('ru-RU');
}

// Экранирование HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Обновляем счетчик каждые 30 секунд
setInterval(updateNotificationBadge, 30000);
</script>