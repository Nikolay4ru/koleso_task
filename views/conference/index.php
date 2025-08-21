<?php
// views/conference/index.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Видеоконференции - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #48bb78;
            --danger: #f56565;
            --warning: #ff9f0a;
            --info: #5ac8fa;
            --dark: #1a202c;
            --gray: #718096;
            --light: #f7fafc;
            --border: #e2e8f0;
            --radius: 12px;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .conference-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.98);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .conference-tabs {
            background: white;
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .nav-tabs {
            border: none;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--gray);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary);
            color: white;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            background: var(--light);
        }

        .conference-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .conference-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .conference-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .conference-card.active {
            border-left: 4px solid var(--secondary);
        }

        .conference-card.scheduled {
            border-left: 4px solid var(--warning);
        }

        .conference-card.ended {
            border-left: 4px solid var(--gray);
            opacity: 0.9;
        }

        .conference-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(72, 187, 120, 0.1);
            color: var(--secondary);
        }

        .status-scheduled {
            background: rgba(255, 159, 10, 0.1);
            color: var(--warning);
        }

        .status-ended {
            background: rgba(113, 128, 150, 0.1);
            color: var(--gray);
        }

        .conference-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .conference-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .meta-item i {
            width: 20px;
        }

        .conference-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .btn-action {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            color: var(--dark);
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn-action:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-action.primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-action.primary:hover {
            background: var(--primary-dark);
        }

        .empty-state {
            background: white;
            border-radius: var(--radius);
            padding: 3rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .participants-avatars {
            display: flex;
            margin-top: 0.5rem;
        }

        .participant-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: -8px;
            border: 2px solid white;
        }

        .participant-avatar.more {
            background: var(--gray);
        }

        .quick-start {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .quick-start-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .quick-start-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-option {
            padding: 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .quick-option:hover {
            border-color: var(--primary);
            background: var(--light);
            transform: translateY(-2px);
        }

        .quick-option i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .quick-option-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .quick-option-desc {
            font-size: 0.875rem;
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .conference-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-primary-gradient {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <div class="conference-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="page-title">
                        <i class="bi bi-camera-video"></i> Видеоконференции
                    </h1>
                    <p class="page-subtitle">Проводите встречи и обсуждения онлайн</p>
                </div>
                <div class="col-md-6">
                    <div class="action-buttons justify-content-md-end">
                        <button class="btn-primary-gradient" onclick="showCreateModal()">
                            <i class="bi bi-plus-circle"></i> Новая конференция
                        </button>
                        <button class="btn-primary-gradient" onclick="showJoinModal()">
                            <i class="bi bi-box-arrow-in-right"></i> Присоединиться
                        </button>
                        <a href="/conference/scheduled" class="btn btn-outline-light">
                            <i class="bi bi-calendar-event"></i> Запланированные
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Start -->
        <div class="quick-start">
            <h3 class="quick-start-title">Быстрый старт</h3>
            <div class="quick-start-options">
                <div class="quick-option" onclick="createInstantMeeting()">
                    <i class="bi bi-lightning-charge"></i>
                    <div class="quick-option-title">Мгновенная встреча</div>
                    <div class="quick-option-desc">Начать сейчас</div>
                </div>
                <div class="quick-option" onclick="showScheduleModal()">
                    <i class="bi bi-calendar-plus"></i>
                    <div class="quick-option-title">Запланировать</div>
                    <div class="quick-option-desc">На позже</div>
                </div>
                <div class="quick-option" onclick="showJoinModal()">
                    <i class="bi bi-link-45deg"></i>
                    <div class="quick-option-title">По коду</div>
                    <div class="quick-option-desc">Присоединиться</div>
                </div>
                <div class="quick-option" onclick="window.location.href='/conference/history'">
                    <i class="bi bi-clock-history"></i>
                    <div class="quick-option-title">История</div>
                    <div class="quick-option-desc">Прошедшие</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="conference-tabs">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#active-tab">
                        <i class="bi bi-broadcast"></i> Активные
                        <?php if (!empty($activeConferences)): ?>
                            <span class="badge bg-success ms-1"><?= count($activeConferences) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#scheduled-tab">
                        <i class="bi bi-calendar-week"></i> Запланированные
                        <?php if (!empty($scheduledConferences)): ?>
                            <span class="badge bg-warning ms-1"><?= count($scheduledConferences) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#recent-tab">
                        <i class="bi bi-clock"></i> Недавние
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Active Conferences -->
            <div class="tab-pane fade show active" id="active-tab">
                <?php if (!empty($activeConferences)): ?>
                    <div class="conference-grid">
                        <?php foreach ($activeConferences as $conference): ?>
                            <div class="conference-card active">
                                <span class="conference-status status-active">Идет сейчас</span>
                                <h3 class="conference-title"><?= htmlspecialchars($conference['title']) ?></h3>
                                
                                <div class="conference-meta">
                                    <div class="meta-item">
                                        <i class="bi bi-person"></i>
                                        <?= htmlspecialchars($conference['creator_name']) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-people"></i>
                                        <?= $conference['participant_count'] ?> участников
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-clock"></i>
                                        Начата <?= date('H:i', strtotime($conference['started_at'])) ?>
                                    </div>
                                    <?php if ($conference['task_id']): ?>
                                        <div class="meta-item">
                                            <i class="bi bi-list-task"></i>
                                            Задача #<?= $conference['task_id'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="conference-actions">
                                    <a href="/conference/join/<?= $conference['room_id'] ?>" class="btn-action primary">
                                        <i class="bi bi-camera-video"></i> Присоединиться
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-camera-video-off"></i>
                        </div>
                        <h3 class="empty-title">Нет активных конференций</h3>
                        <p class="empty-text">Начните новую встречу или присоединитесь к существующей</p>
                        <button class="btn-primary-gradient" onclick="createInstantMeeting()">
                            <i class="bi bi-plus-circle"></i> Начать конференцию
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Scheduled Conferences -->
            <div class="tab-pane fade" id="scheduled-tab">
                <?php if (!empty($scheduledConferences)): ?>
                    <div class="conference-grid">
                        <?php foreach ($scheduledConferences as $conference): ?>
                            <div class="conference-card scheduled">
                                <span class="conference-status status-scheduled">Запланировано</span>
                                <h3 class="conference-title"><?= htmlspecialchars($conference['title']) ?></h3>
                                
                                <div class="conference-meta">
                                    <div class="meta-item">
                                        <i class="bi bi-calendar"></i>
                                        <?= date('d.m.Y', strtotime($conference['scheduled_at'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-clock"></i>
                                        <?= date('H:i', strtotime($conference['scheduled_at'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-person"></i>
                                        <?= htmlspecialchars($conference['creator_name']) ?>
                                    </div>
                                </div>

                                <div class="conference-actions">
                                    <button class="btn-action" onclick="copyRoomId('<?= $conference['room_id'] ?>')">
                                        <i class="bi bi-clipboard"></i> Код
                                    </button>
                                    <?php if (strtotime($conference['scheduled_at']) <= time()): ?>
                                        <a href="/conference/join/<?= $conference['room_id'] ?>" class="btn-action primary">
                                            <i class="bi bi-play-circle"></i> Начать
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-action" onclick="addToCalendar(<?= htmlspecialchars(json_encode($conference)) ?>)">
                                            <i class="bi bi-calendar-plus"></i> В календарь
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <h3 class="empty-title">Нет запланированных конференций</h3>
                        <p class="empty-text">Запланируйте встречу заранее</p>
                        <button class="btn-primary-gradient" onclick="showScheduleModal()">
                            <i class="bi bi-calendar-plus"></i> Запланировать
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Conferences -->
            <div class="tab-pane fade" id="recent-tab">
                <?php if (!empty($recentConferences)): ?>
                    <div class="conference-grid">
                        <?php foreach ($recentConferences as $conference): ?>
                            <div class="conference-card ended">
                                <span class="conference-status status-ended">Завершена</span>
                                <h3 class="conference-title"><?= htmlspecialchars($conference['title']) ?></h3>
                                
                                <div class="conference-meta">
                                    <div class="meta-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <?= date('d.m.Y H:i', strtotime($conference['ended_at'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-hourglass"></i>
                                        <?php
                                        $duration = strtotime($conference['ended_at']) - strtotime($conference['started_at']);
                                        echo gmdate('H:i:s', $duration);
                                        ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-chat-dots"></i>
                                        <?= $conference['message_count'] ?> сообщений
                                    </div>
                                    <?php if ($conference['recording_count'] > 0): ?>
                                        <div class="meta-item text-success">
                                            <i class="bi bi-record-circle"></i>
                                            Есть запись
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="conference-actions">
                                    <?php if ($conference['recording_count'] > 0): ?>
                                        <a href="/conference/recording/<?= $conference['id'] ?>" class="btn-action">
                                            <i class="bi bi-download"></i> Запись
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn-action" onclick="showDetails(<?= $conference['id'] ?>)">
                                        <i class="bi bi-info-circle"></i> Детали
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title">История пуста</h3>
                        <p class="empty-text">Здесь будут отображаться завершенные конференции</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Create Conference Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Новая конференция</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm">
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Дата и время (опционально)</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пригласить участников</label>
                            <select class="form-select" name="invited_users[]" multiple>
                                <!-- Заполняется через JS -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="createConference()">Создать</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Join Conference Modal -->
    <div class="modal fade" id="joinModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Присоединиться к конференции</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Код комнаты</label>
                        <input type="text" class="form-control" id="roomCode" placeholder="123-456-789">
                        <small class="text-muted">Введите 9-значный код комнаты</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="joinConference()">Присоединиться</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showCreateModal() {
            new bootstrap.Modal(document.getElementById('createModal')).show();
        }

        function showJoinModal() {
            new bootstrap.Modal(document.getElementById('joinModal')).show();
        }

        function showScheduleModal() {
            const modal = new bootstrap.Modal(document.getElementById('createModal'));
            modal.show();
            // Установить минимальное время на час вперед
            const now = new Date();
            now.setHours(now.getHours() + 1);
            document.querySelector('input[name="scheduled_at"]').value = now.toISOString().slice(0, 16);
        }

        function createInstantMeeting() {
            fetch('/conference/create', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    title: 'Быстрая встреча',
                    scheduled_at: null
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.join_url;
                }
            });
        }

        function createConference() {
            const form = document.getElementById('createForm');
            const formData = new FormData(form);
            
            fetch('/conference/create', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (formData.get('scheduled_at')) {
                        alert('Конференция запланирована! Код: ' + data.room_id);
                        location.reload();
                    } else {
                        window.location.href = data.join_url;
                    }
                }
            });
        }

        function joinConference() {
            const roomCode = document.getElementById('roomCode').value.trim();
            if (roomCode) {
                window.location.href = '/conference/join/' + roomCode;
            }
        }

        function copyRoomId(roomId) {
            navigator.clipboard.writeText(roomId);
            alert('Код комнаты скопирован: ' + roomId);
        }

        function addToCalendar(conference) {
            // Здесь можно добавить интеграцию с календарем
            alert('Функция добавления в календарь будет доступна позже');
        }

        function showDetails(conferenceId) {
            // Показать детали конференции
            window.location.href = '/conference/details/' + conferenceId;
        }
    </script>
</body>
</html>