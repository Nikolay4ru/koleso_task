<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задачи</title>
    <style>
        :root {
            --ios-primary: #007AFF;
            --ios-secondary: #5856D6;
            --ios-success: #34C759;
            --ios-danger: #FF3B30;
            --ios-warning: #FF9500;
            --ios-purple: #AF52DE;
            --ios-pink: #FF2D55;
            --ios-teal: #5AC8FA;
            --ios-indigo: #5856D6;
            --ios-gray: #8E8E93;
            --ios-gray-2: #AEAEB2;
            --ios-gray-3: #C7C7CC;
            --ios-gray-4: #D1D1D6;
            --ios-gray-5: #E5E5EA;
            --ios-gray-6: #F2F2F7;
            --ios-bg: #F2F2F7;
            --ios-card: #FFFFFF;
            --ios-text: #000000;
            --ios-text-secondary: #3C3C43;
            --ios-text-tertiary: #8E8E93;
            --ios-border: rgba(0,0,0,0.04);
            --ios-shadow: 0 3px 8px rgba(0,0,0,0.12), 0 3px 1px rgba(0,0,0,0.04);
            --ios-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
            background: var(--ios-bg);
            color: var(--ios-text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Навигация */
        .navbar {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--ios-border);
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-title {
            font-size: 34px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .navbar-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        /* Кнопки */
        .btn {
            background: var(--ios-primary);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.9;
            transform: scale(0.98);
        }

        .btn-secondary {
            background: var(--ios-gray-5);
            color: var(--ios-text);
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            justify-content: center;
            border-radius: 18px;
            background: var(--ios-gray-6);
            color: var(--ios-gray);
        }

        .btn-icon:hover {
            background: var(--ios-gray-5);
        }

        /* Контейнер */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--ios-card);
            border-radius: 16px;
            padding: 16px;
            box-shadow: var(--ios-shadow-sm);
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--ios-shadow);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--ios-primary), var(--ios-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 13px;
            color: var(--ios-text-tertiary);
            margin-top: 4px;
        }

        /* Фильтры */
        .filters-section {
            background: var(--ios-card);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: var(--ios-shadow-sm);
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .filters-title {
            font-size: 20px;
            font-weight: 600;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .filter-group label {
            display: block;
            font-size: 13px;
            color: var(--ios-text-tertiary);
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--ios-gray-5);
            border-radius: 10px;
            font-size: 17px;
            background: white;
            color: var(--ios-text);
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%238E8E93' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--ios-primary);
            box-shadow: 0 0 0 4px rgba(0,122,255,0.1);
        }

        /* Поиск */
        .search-box {
            position: relative;
            margin-bottom: 16px;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: none;
            border-radius: 10px;
            background: var(--ios-gray-6);
            font-size: 17px;
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            background: white;
            box-shadow: var(--ios-shadow);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ios-gray-2);
            pointer-events: none;
        }

        /* Таблица */
        .table-container {
            background: var(--ios-card);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--ios-shadow-sm);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--ios-gray-5);
        }

        .table-title {
            font-size: 20px;
            font-weight: 600;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--ios-gray-6);
            padding: 12px 20px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--ios-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody tr {
            border-bottom: 1px solid var(--ios-gray-5);
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: var(--ios-gray-6);
        }

        tbody td {
            padding: 16px 20px;
            font-size: 17px;
        }

        /* Checkbox */
        .checkbox-wrapper {
            display: inline-block;
            position: relative;
        }

        .checkbox {
            width: 22px;
            height: 22px;
            appearance: none;
            border: 2px solid var(--ios-gray-3);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkbox:checked {
            background: var(--ios-primary);
            border-color: var(--ios-primary);
        }

        .checkbox:checked::after {
            content: '';
            position: absolute;
            top: 5px;
            left: 8px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Статусы */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-backlog {
            background: var(--ios-gray-5);
            color: var(--ios-gray);
        }

        .status-todo {
            background: rgba(0,122,255,0.1);
            color: var(--ios-primary);
        }

        .status-in_progress {
            background: rgba(255,149,0,0.1);
            color: var(--ios-warning);
        }

        .status-waiting_approval {
            background: rgba(175,82,222,0.1);
            color: var(--ios-purple);
        }

        .status-done {
            background: rgba(52,199,89,0.1);
            color: var(--ios-success);
        }

        /* Приоритеты */
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .priority-low {
            background: var(--ios-gray-5);
            color: var(--ios-gray);
        }

        .priority-medium {
            background: rgba(0,122,255,0.1);
            color: var(--ios-primary);
        }

        .priority-high {
            background: rgba(255,149,0,0.1);
            color: var(--ios-warning);
        }

        .priority-urgent {
            background: rgba(255,59,48,0.1);
            color: var(--ios-danger);
        }

        /* Аватары исполнителей */
        .assignees {
            display: flex;
            align-items: center;
            gap: -8px;
        }

        .assignee-avatar {
            width: 32px;
            height: 32px;
            border-radius: 16px;
            background: var(--ios-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            border: 2px solid white;
            position: relative;
            z-index: 1;
        }

        .assignee-avatar:hover {
            z-index: 2;
            transform: scale(1.1);
        }

        .assignee-count {
            margin-left: 12px;
            background: var(--ios-gray-5);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: var(--ios-gray);
        }

        /* Дедлайн */
        .deadline {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
        }

        .deadline-overdue {
            color: var(--ios-danger);
            font-weight: 600;
        }

        .deadline-soon {
            color: var(--ios-warning);
        }

        /* Действия */
        .actions {
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        tr:hover .actions {
            opacity: 1;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            background: var(--ios-gray-6);
            color: var(--ios-gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--ios-primary);
            color: white;
        }

        /* Мобильная адаптация */
        @media (max-width: 768px) {
            .navbar-title {
                font-size: 28px;
            }

            .container {
                padding: 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .assignees {
                flex-wrap: wrap;
                gap: 4px;
            }
        }

        /* Темная тема */
        @media (prefers-color-scheme: dark) {
            :root {
                --ios-bg: #000000;
                --ios-card: #1C1C1E;
                --ios-text: #FFFFFF;
                --ios-text-secondary: #EBEBF5;
                --ios-text-tertiary: #8E8E93;
                --ios-border: rgba(255,255,255,0.08);
                --ios-gray-5: #2C2C2E;
                --ios-gray-6: #1C1C1E;
                --ios-shadow: 0 3px 8px rgba(0,0,0,0.5);
                --ios-shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            }

            .navbar {
                background: rgba(28,28,30,0.9);
            }

            .search-input {
                background: var(--ios-gray-5);
                color: white;
            }

            .filter-select {
                background: var(--ios-gray-5);
                border-color: var(--ios-gray-4);
                color: white;
            }
        }

        /* Анимации */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-container {
            animation: slideIn 0.5s ease-out;
        }

        /* Быстрые фильтры */
        .quick-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quick-filter {
            padding: 8px 16px;
            border-radius: 20px;
            background: var(--ios-gray-6);
            color: var(--ios-gray);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .quick-filter:hover {
            background: var(--ios-gray-5);
        }

        .quick-filter.active {
            background: var(--ios-primary);
            color: white;
        }

        /* Пагинация */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 20px;
            border-top: 1px solid var(--ios-gray-5);
        }

        .page-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            background: var(--ios-gray-6);
            color: var(--ios-gray);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:hover {
            background: var(--ios-gray-5);
        }

        .page-btn.active {
            background: var(--ios-primary);
            color: white;
        }

        .page-info {
            font-size: 15px;
            color: var(--ios-text-tertiary);
            margin: 0 16px;
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar">
        <div class="navbar-content">
            <h1 class="navbar-title">Задачи</h1>
            <div class="navbar-actions">
                <button class="btn-icon" onclick="window.location.href='/tasks/kanban'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </button>
                <a href="/tasks/create" class="btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Новая задача
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="totalTasks">0</div>
                <div class="stat-label">Всего задач</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="activeTasks">0</div>
                <div class="stat-label">Активных</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="completedTasks">0</div>
                <div class="stat-label">Выполнено</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="overdueTasks">0</div>
                <div class="stat-label">Просрочено</div>
            </div>
        </div>

        <!-- Быстрые фильтры -->
        <div class="quick-filters">
            <button class="quick-filter" data-filter="all">Все задачи</button>
            <button class="quick-filter" data-filter="my-tasks">Мои задачи</button>
            <button class="quick-filter" data-filter="assigned-to-me">Назначены мне</button>
            <button class="quick-filter" data-filter="created-by-me">Созданные мной</button>
            <button class="quick-filter" data-filter="overdue">Просроченные</button>
        </div>

        <!-- Фильтры -->
        <div class="filters-section">
            <div class="filters-header">
                <h2 class="filters-title">Фильтры</h2>
                <button class="btn-secondary" onclick="resetFilters()">Сбросить</button>
            </div>
            
            <!-- Поиск -->
            <div class="search-box">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" class="search-input" placeholder="Поиск по задачам..." id="searchInput">
            </div>

            <div class="filters-grid">
                <div class="filter-group">
                    <label>Статус</label>
                    <select class="filter-select" id="filterStatus" multiple size="5">
                        <option value="backlog">Очередь задач</option>
                        <option value="todo">К выполнению</option>
                        <option value="in_progress">В работе</option>
                        <option value="waiting_approval">Ожидает проверки</option>
                        <option value="done">Выполнено</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Приоритет</label>
                    <select class="filter-select" id="filterPriority" multiple size="4">
                        <option value="low">Низкий</option>
                        <option value="medium">Средний</option>
                        <option value="high">Высокий</option>
                        <option value="urgent">Срочный</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Исполнитель</label>
                    <select class="filter-select" id="filterAssignee">
                        <option value="">Все исполнители</option>
                        <option value="1">Иван Иванов</option>
                        <option value="2">Петр Петров</option>
                        <option value="3">Мария Сидорова</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Отдел</label>
                    <select class="filter-select" id="filterDepartment">
                        <option value="">Все отделы</option>
                        <option value="it">IT</option>
                        <option value="sales">Продажи</option>
                        <option value="hr">HR</option>
                        <option value="marketing">Маркетинг</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Таблица -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Список задач</h2>
                <div class="table-actions">
                    <button class="btn-icon" onclick="exportTasks('excel')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </button>
                    <button class="btn-icon" onclick="printTasks()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                    </button>
                </div>
            </div>

            <table id="tasksTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="checkbox" id="selectAll">
                        </th>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Статус</th>
                        <th>Приоритет</th>
                        <th>Исполнители</th>
                        <th>Дедлайн</th>
                        <th>Создал</th>
                        <th>Отдел</th>
                        <th width="100">Действия</th>
                    </tr>
                </thead>
                <tbody id="tasksTableBody">
                    <!-- Задачи будут загружены динамически -->
                </tbody>
            </table>

            <div class="pagination">
                <button class="page-btn" onclick="prevPage()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <span class="page-info">Страница <span id="currentPage">1</span> из <span id="totalPages">1</span></span>
                <button class="page-btn" onclick="nextPage()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Примерные данные задач
        let tasks = [
            {
                id: 1,
                title: 'Разработка нового функционала',
                status: 'in_progress',
                priority: 'high',
                assignees: [{id: 1, name: 'Иван Иванов', initials: 'ИИ'}, {id: 2, name: 'Петр Петров', initials: 'ПП'}],
                deadline: '2025-01-10',
                creator: {id: 1, name: 'Мария Сидорова'},
                department: 'IT',
                description: 'Разработка нового модуля для системы'
            },
            {
                id: 2,
                title: 'Тестирование приложения',
                status: 'todo',
                priority: 'medium',
                assignees: [{id: 3, name: 'Анна Андреева', initials: 'АА'}],
                deadline: '2025-01-15',
                creator: {id: 2, name: 'Иван Иванов'},
                department: 'IT',
                description: 'Полное тестирование нового релиза'
            },
            {
                id: 3,
                title: 'Подготовка презентации',
                status: 'waiting_approval',
                priority: 'urgent',
                assignees: [{id: 4, name: 'Елена Егорова', initials: 'ЕЕ'}],
                deadline: '2025-01-05',
                creator: {id: 3, name: 'Алексей Алексеев'},
                department: 'Маркетинг',
                description: 'Презентация для инвесторов'
            },
            {
                id: 4,
                title: 'Обновление документации',
                status: 'done',
                priority: 'low',
                assignees: [{id: 5, name: 'Дмитрий Дмитриев', initials: 'ДД'}],
                deadline: '2025-01-20',
                creator: {id: 1, name: 'Мария Сидорова'},
                department: 'IT',
                description: 'Обновить техническую документацию'
            },
            {
                id: 5,
                title: 'Анализ продаж за квартал',
                status: 'backlog',
                priority: 'medium',
                assignees: [{id: 6, name: 'Ольга Олегова', initials: 'ОО'}, {id: 7, name: 'Сергей Сергеев', initials: 'СС'}],
                deadline: null,
                creator: {id: 4, name: 'Николай Николаев'},
                department: 'Продажи',
                description: 'Подготовить отчет по продажам'
            }
        ];

        let filteredTasks = [...tasks];
        let currentPage = 1;
        const tasksPerPage = 10;
        let currentUserId = 1; // ID текущего пользователя

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
            renderTasks();
            initializeEventListeners();
        });

        // Обновление статистики
        function updateStats() {
            document.getElementById('totalTasks').textContent = tasks.length;
            document.getElementById('activeTasks').textContent = tasks.filter(t => t.status !== 'done' && t.status !== 'backlog').length;
            document.getElementById('completedTasks').textContent = tasks.filter(t => t.status === 'done').length;
            
            const now = new Date();
            const overdue = tasks.filter(t => {
                if (!t.deadline || t.status === 'done') return false;
                return new Date(t.deadline) < now;
            }).length;
            document.getElementById('overdueTasks').textContent = overdue;
        }

        // Рендеринг задач
        function renderTasks() {
            const tbody = document.getElementById('tasksTableBody');
            tbody.innerHTML = '';

            const start = (currentPage - 1) * tasksPerPage;
            const end = start + tasksPerPage;
            const paginatedTasks = filteredTasks.slice(start, end);

            paginatedTasks.forEach(task => {
                const row = createTaskRow(task);
                tbody.appendChild(row);
            });

            updatePagination();
        }

        // Создание строки задачи
        function createTaskRow(task) {
            const tr = document.createElement('tr');
            tr.dataset.taskId = task.id;
            
            // Проверка просрочки
            const now = new Date();
            const deadline = task.deadline ? new Date(task.deadline) : null;
            const isOverdue = deadline && deadline < now && task.status !== 'done';
            const isSoon = deadline && (deadline - now) / (1000 * 60 * 60 * 24) <= 3 && task.status !== 'done';

            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="checkbox task-checkbox" data-task-id="${task.id}">
                </td>
                <td>${task.id}</td>
                <td>
                    <div>
                        <div style="font-weight: 600;">${escapeHtml(task.title)}</div>
                        <div style="font-size: 13px; color: var(--ios-text-tertiary);">${escapeHtml(task.description)}</div>
                    </div>
                </td>
                <td>
                    <span class="status-badge status-${task.status}">${getStatusLabel(task.status)}</span>
                </td>
                <td>
                    <span class="priority-badge priority-${task.priority}">${getPriorityLabel(task.priority)}</span>
                </td>
                <td>
                    ${renderAssignees(task.assignees)}
                </td>
                <td>
                    ${renderDeadline(task.deadline, isOverdue, isSoon)}
                </td>
                <td>${escapeHtml(task.creator.name)}</td>
                <td>${escapeHtml(task.department)}</td>
                <td>
                    <div class="actions">
                        <button class="action-btn" onclick="editTask(${task.id})" title="Редактировать">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button class="action-btn" onclick="duplicateTask(${task.id})" title="Дублировать">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                        </button>
                    </div>
                </td>
            `;

            return tr;
        }

        // Рендеринг исполнителей
        function renderAssignees(assignees) {
            if (!assignees || assignees.length === 0) {
                return '<span style="color: var(--ios-text-tertiary);">—</span>';
            }

            let html = '<div class="assignees">';
            const maxShow = 3;
            
            assignees.slice(0, maxShow).forEach(assignee => {
                html += `<div class="assignee-avatar" title="${escapeHtml(assignee.name)}">${assignee.initials}</div>`;
            });

            if (assignees.length > maxShow) {
                html += `<div class="assignee-count">+${assignees.length - maxShow}</div>`;
            }

            html += '</div>';
            return html;
        }

        // Рендеринг дедлайна
        function renderDeadline(deadline, isOverdue, isSoon) {
            if (!deadline) {
                return '<span style="color: var(--ios-text-tertiary);">—</span>';
            }

            const date = new Date(deadline);
            const formatted = date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
            });

            let className = 'deadline';
            if (isOverdue) className += ' deadline-overdue';
            else if (isSoon) className += ' deadline-soon';

            return `
                <div class="${className}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    ${formatted}
                </div>
            `;
        }

        // Инициализация обработчиков событий
        function initializeEventListeners() {
            // Выбрать все
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.task-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });

            // Поиск
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 300);
            });

            // Фильтры
            ['filterStatus', 'filterPriority', 'filterAssignee', 'filterDepartment'].forEach(id => {
                document.getElementById(id).addEventListener('change', applyFilters);
            });

            // Быстрые фильтры
            document.querySelectorAll('.quick-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.quick-filter').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    applyFilters();
                });
            });
        }

        // Применение фильтров
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const selectedStatuses = Array.from(document.getElementById('filterStatus').selectedOptions).map(o => o.value);
            const selectedPriorities = Array.from(document.getElementById('filterPriority').selectedOptions).map(o => o.value);
            const selectedAssignee = document.getElementById('filterAssignee').value;
            const selectedDepartment = document.getElementById('filterDepartment').value;
            const activeQuickFilter = document.querySelector('.quick-filter.active')?.dataset.filter;

            filteredTasks = tasks.filter(task => {
                // Поиск
                if (searchTerm) {
                    const searchIn = [
                        task.title,
                        task.description,
                        task.creator.name,
                        task.department,
                        ...task.assignees.map(a => a.name)
                    ].join(' ').toLowerCase();

                    if (!searchIn.includes(searchTerm)) return false;
                }

                // Статус
                if (selectedStatuses.length && !selectedStatuses.includes(task.status)) return false;

                // Приоритет
                if (selectedPriorities.length && !selectedPriorities.includes(task.priority)) return false;

                // Исполнитель
                if (selectedAssignee && !task.assignees.some(a => a.id == selectedAssignee)) return false;

                // Отдел
                if (selectedDepartment && task.department !== selectedDepartment) return false;

                // Быстрые фильтры
                if (activeQuickFilter) {
                    switch (activeQuickFilter) {
                        case 'my-tasks':
                            return task.assignees.some(a => a.id === currentUserId) || task.creator.id === currentUserId;
                        case 'assigned-to-me':
                            return task.assignees.some(a => a.id === currentUserId);
                        case 'created-by-me':
                            return task.creator.id === currentUserId;
                        case 'overdue':
                            if (!task.deadline || task.status === 'done') return false;
                            return new Date(task.deadline) < new Date();
                    }
                }

                return true;
            });

            currentPage = 1;
            renderTasks();
        }

        // Сброс фильтров
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').selectedIndex = -1;
            document.getElementById('filterPriority').selectedIndex = -1;
            document.getElementById('filterAssignee').value = '';
            document.getElementById('filterDepartment').value = '';
            document.querySelectorAll('.quick-filter').forEach(b => b.classList.remove('active'));
            
            filteredTasks = [...tasks];
            currentPage = 1;
            renderTasks();
        }

        // Пагинация
        function updatePagination() {
            const totalPages = Math.ceil(filteredTasks.length / tasksPerPage);
            document.getElementById('currentPage').textContent = currentPage;
            document.getElementById('totalPages').textContent = totalPages || 1;
        }

        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                renderTasks();
            }
        }

        function nextPage() {
            const totalPages = Math.ceil(filteredTasks.length / tasksPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderTasks();
            }
        }

        // Действия
        function editTask(taskId) {
            window.location.href = `/tasks/edit/${taskId}`;
        }

        function duplicateTask(taskId) {
            if (confirm('Создать копию этой задачи?')) {
                // Здесь будет логика дублирования
                console.log('Дублирование задачи', taskId);
            }
        }

        function exportTasks(format) {
            console.log('Экспорт в формате', format);
            // Здесь будет логика экспорта
        }

        function printTasks() {
            window.print();
        }

        // Вспомогательные функции
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getStatusLabel(status) {
            const labels = {
                'backlog': 'Очередь задач',
                'todo': 'К выполнению',
                'in_progress': 'В работе',
                'waiting_approval': 'Ожидает проверки',
                'done': 'Выполнено'
            };
            return labels[status] || status;
        }

        function getPriorityLabel(priority) {
            const labels = {
                'low': 'Низкий',
                'medium': 'Средний',
                'high': 'Высокий',
                'urgent': 'Срочный'
            };
            return labels[priority] || priority;
        }
    </script>
</body>
</html>