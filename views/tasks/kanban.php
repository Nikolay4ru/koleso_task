<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Канбан доска</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #eef1f7;
        }
        .kanban-board {
            overflow-x: auto;
            white-space: nowrap;
        }
        .kanban-column {
            min-height: 600px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px 14px;
            margin-right: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            display: inline-block;
            width: 340px;
            vertical-align: top;
        }
        .kanban-column:last-child {
            margin-right: 0;
        }
        .kanban-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .task-card {
            background: white;
            border-radius: 10px;
            padding: 15px 12px 12px 16px;
            margin-bottom: 12px;
            cursor: move;
            box-shadow: 0 2px 7px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            border: 1px solid #ececec;
        }
        .task-card:hover {
            transform: translateY(-3px) scale(1.012);
            box-shadow: 0 6px 18px rgba(0,0,0,0.13);
            z-index: 1;
        }
        .task-card.dragging {
            opacity: 0.55;
            box-shadow: 0 0 0 2px #007bff33;
        }
        .priority-urgent { border-left: 5px solid #dc3545; }
        .priority-high { border-left: 5px solid #fd7e14; }
        .priority-medium { border-left: 5px solid #ffc107; }
        .priority-low { border-left: 5px solid #28a745; }
        .assignee-badge {
            background: #d7e3fb;
            color: #234685;
            padding: 2px 10px;
            border-radius: 14px;
            font-size: 13px;
            margin-right: 6px;
        }
        .deadline-badge {
            background: #f7eefd;
            color: #7c52b9;
            border-radius: 10px;
            font-size: 12px;
            padding: 2px 8px;
            margin-left: 4px;
        }
        .task-actions {
            position: absolute;
            right: 9px;
            top: 10px;
            z-index: 2;
        }
        .task-actions .btn {
            padding: 0 6px;
            font-size: 15px;
            color: #6c757d;
            background: none;
            border: none;
        }
        .task-actions .btn:hover {
            color: #0d6efd;
        }
        .add-task-btn {
            background: #0d6efd;
            color: #fff;
            border-radius: 20px;
            padding: 3px 18px 3px 12px;
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .add-task-btn i {
            margin-right: 5px;
        }
        .kanban-search {
            max-width: 300px;
        }
        @media (max-width: 1200px) {
            .kanban-column { width: 92vw; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-8 col-12 mb-2">
                <h2 class="mb-0">Канбан доска</h2>
            </div>
            <div class="col-md-4 col-12 text-md-end">
                <input type="text" class="form-control kanban-search d-inline-block" style="width: 70%;" placeholder="Поиск задач..." id="kanbanSearch">
                <a href="/tasks/create" class="btn add-task-btn ms-2"><i class="bi bi-plus-circle"></i>Создать задачу</a>
            </div>
        </div>

        <div class="kanban-board pb-3">
            <?php
            $columns = [
                'backlog' => 'Очередь задач',
                'todo' => 'К выполнению',
                'in_progress' => 'В работе',
                'review' => 'На проверке',
                'done' => 'Выполнено'
            ];
            foreach ($columns as $status => $title): ?>
                <div class="kanban-column" data-status="<?= $status ?>">
                    <div class="kanban-header">
                        <h5 class="mb-0"><?= $title ?></h5>
                        <span class="badge bg-secondary" id="count-<?= $status ?>">
                            <?= isset($tasks[$status]) ? count($tasks[$status]) : 0 ?>
                        </span>
                    </div>
                    <div class="kanban-tasks-list">
                        <?php foreach ($tasks[$status] as $task): ?>
                            <div class="task-card priority-<?= $task['priority'] ?>"
                                 draggable="true"
                                 data-task-id="<?= $task['id'] ?>"
                                 data-current-status="<?= $status ?>"
                                 data-title="<?= htmlspecialchars($task['title']) ?>"
                                 data-desc="<?= htmlspecialchars($task['description']) ?>"
                                 data-assignees="<?= htmlspecialchars($task['assignee_names']) ?>"
                            >
                                <div class="task-actions">
                                    <button class="btn btn-sm btn-light edit-task-btn" title="Редактировать" data-task-id="<?= $task['id'] ?>"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-sm btn-light delete-task-btn" title="Удалить" data-task-id="<?= $task['id'] ?>"><i class="bi bi-trash"></i></button>
                                </div>
                                <h6 class="mb-1"><?= htmlspecialchars($task['title']) ?></h6>
                                <p class="small text-muted mb-2"><?= htmlspecialchars($task['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($task['assignee_names']): ?>
                                            <?php foreach (explode(',', $task['assignee_names']) as $assignee): ?>
                                                <span class="assignee-badge"><?= htmlspecialchars($assignee) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($task['deadline']): ?>
                                        <span class="deadline-badge">
                                            <i class="bi bi-calendar3"></i>
                                            <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Модальное окно для подтверждения удаления -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteTaskModalLabel">Удалить задачу</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
          </div>
          <div class="modal-body">
            Вы уверены, что хотите удалить эту задачу?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteTaskBtn">Удалить</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно для редактирования задачи -->
    <!-- ... Вставьте этот modal в ваш kanban-board.html вместо старого editTaskModal ... -->
<!-- Модальное окно для редактирования задачи -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:10px;">
      <form id="editTaskForm">
        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; border-radius:10px 10px 0 0 !important;">
          <h5 class="modal-title" id="editTaskModalLabel"><i class="bi bi-pencil-square me-2"></i>Редактирование задачи</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editTaskId" name="task_id">
          <div class="row mb-4">
            <div class="col-md-8 mb-3">
              <label for="editTaskTitle" class="form-label required-field">Название задачи</label>
              <input type="text" class="form-control" id="editTaskTitle" name="title" required placeholder="Введите название задачи">
              <div class="form-hint">Краткое и понятное название задачи</div>
            </div>
            <div class="col-md-4 mb-3">
              <label for="editTaskStatus" class="form-label">Статус</label>
              <select class="form-select" id="editTaskStatus" name="status">
                <option value="backlog">📋 Бэклог</option>
                <option value="todo">📝 К выполнению</option>
                <option value="in_progress">🔄 В работе</option>
              </select>
            </div>
            <div class="col-12 mb-3">
              <label for="editTaskDesc" class="form-label">Описание</label>
              <textarea class="form-control" id="editTaskDesc" name="description" rows="4" placeholder="Подробное описание задачи..."></textarea>
              <div class="form-hint">Опишите, что нужно сделать, какие есть требования и ограничения</div>
            </div>
          </div>
          <div class="row mb-4">
            <div class="col-md-8 mb-3">
              <label class="form-label required-field">Приоритет</label>
              <div class="priority-select d-flex gap-2 flex-wrap">
                <div class="priority-option priority-low">
                  <input type="radio" id="edit-priority-low" name="priority" value="low">
                  <label class="priority-label" for="edit-priority-low">
                    <i class="bi bi-arrow-down-circle me-1"></i>Низкий
                  </label>
                </div>
                <div class="priority-option priority-medium">
                  <input type="radio" id="edit-priority-medium" name="priority" value="medium">
                  <label class="priority-label" for="edit-priority-medium">
                    <i class="bi bi-dash-circle me-1"></i>Средний
                  </label>
                </div>
                <div class="priority-option priority-high">
                  <input type="radio" id="edit-priority-high" name="priority" value="high">
                  <label class="priority-label" for="edit-priority-high">
                    <i class="bi bi-arrow-up-circle me-1"></i>Высокий
                  </label>
                </div>
                <div class="priority-option priority-urgent">
                  <input type="radio" id="edit-priority-urgent" name="priority" value="urgent">
                  <label class="priority-label" for="edit-priority-urgent">
                    <i class="bi bi-exclamation-circle me-1"></i>Срочный
                  </label>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label for="editTaskDeadline" class="form-label">Дедлайн</label>
              <input type="text" class="form-control" id="editTaskDeadline" name="deadline" placeholder="Выберите дату">
              <div class="form-hint">Когда задача должна быть выполнена</div>
            </div>
          </div>
          <div class="row mb-4">
            <div class="col-md-6 mb-3">
              <label for="editTaskAssignees" class="form-label"><i class="bi bi-people-fill me-1"></i>Исполнители</label>
              <select class="form-select" id="editTaskAssignees" name="assignees[]" multiple></select>
              <div class="form-hint">Выберите одного или нескольких исполнителей</div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="editTaskWatchers" class="form-label"><i class="bi bi-eye-fill me-1"></i>Наблюдатели</label>
              <select class="form-select" id="editTaskWatchers" name="watchers[]" multiple></select>
              <div class="form-hint">Они будут получать уведомления об изменениях</div>
            </div>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>Отмена
          </button>
          <button type="submit" class="btn btn-save">
            <i class="bi bi-check-circle me-2"></i>Сохранить изменения
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- подключите стили и скрипты (однократно на странице) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.user-select-item { display: flex; align-items: center; padding: 0.5rem; }
.user-avatar { width: 30px; height: 30px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 0.75rem; font-size: 0.875rem; }
.priority-select .priority-label { font-weight: 600; }
.priority-low .priority-label { color: #28a745; }
.priority-low input[type="radio"]:checked + .priority-label { background: #28a745; border-color: #28a745; color: white; }
.priority-medium .priority-label { color: #17a2b8; }
.priority-medium input[type="radio"]:checked + .priority-label { background: #17a2b8; border-color: #17a2b8; color: white;}
.priority-high .priority-label { color: #ffc107; }
.priority-high input[type="radio"]:checked + .priority-label { background: #ffc107; border-color: #ffc107; color: white;}
.priority-urgent .priority-label { color: #dc3545; }
.priority-urgent input[type="radio"]:checked + .priority-label { background: #dc3545; border-color: #dc3545; color: white;}
.btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 0.75rem 2rem; font-weight: 600; border-radius: 8px; transition: all 0.3s; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); color: white; }
.btn-cancel { background: #6c757d; border: none; color: white; padding: 0.75rem 2rem; font-weight: 600; border-radius: 8px; transition: all 0.3s; }
.btn-cancel:hover { background: #5a6268; color: white; }
.form-hint { font-size: 0.875rem; color: #6c757d; margin-top: 0.25rem; }
</style>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
<script>
/*
 * usersData - массив пользователей для select2
 * Пример:
 * var usersData = [
 *   { id: 1, text: 'Иван Иванов (Отдел A)' },
 *   { id: 2, text: 'Петр Петров (Отдел B)' }
 * ];
 * Загрузите usersData на страницу или через ajax!
 */
function formatUser(user) {
    if (!user.id) return user.text;
    const name = user.text.split('(')[0].trim();
    const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
    return $(`
        <div class="user-select-item">
            <div class="user-avatar">${initials}</div>
            <div>
                <div>${user.text}</div>
            </div>
        </div>
    `);
}
function formatUserSelection(user) {
    return user.text.split('(')[0].trim();
}
function updateEditModalWithTask(task, usersData) {
    $('#editTaskId').val(task.id);
    $('#editTaskTitle').val(task.title);
    $('#editTaskDesc').val(task.description);
    $('#editTaskStatus').val(task.status);
    // Приоритет
    $(`#editTaskForm input[name="priority"][value="${task.priority}"]`).prop('checked', true);
    // Дедлайн
    $('#editTaskDeadline').val(task.deadline);
    // Исполнители
    $('#editTaskAssignees').empty();
    $('#editTaskWatchers').empty();
    usersData.forEach(u => {
        $('#editTaskAssignees').append(new Option(u.text, u.id, false, task.assignees && task.assignees.includes(u.id)));
        $('#editTaskWatchers').append(new Option(u.text, u.id, false, task.watchers && task.watchers.includes(u.id)));
    });
    $('#editTaskAssignees, #editTaskWatchers').trigger('change');
}
$(function() {
    // Инициализация Select2 для исполнителей и наблюдателей
    $('#editTaskAssignees, #editTaskWatchers').select2({
        theme: 'bootstrap-5',
        placeholder: 'Выберите пользователей',
        allowClear: true,
        language: {
            noResults: function() { return 'Пользователи не найдены'; },
            searching: function() { return 'Поиск...'; },
            removeAllItems: function() { return 'Удалить все'; }
        },
        templateResult: formatUser,
        templateSelection: formatUserSelection
    });
    // Flatpickr для дедлайна
    flatpickr("#editTaskDeadline", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        locale: "ru",
        time_24hr: true
    });
    // Валидация
    $('#editTaskForm').on('submit', function(e) {
        if (!$('#editTaskTitle').val().trim()) {
            alert('Пожалуйста, введите название задачи');
            $('#editTaskTitle').focus();
            e.preventDefault();
            return false;
        }
    });
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag and Drop функциональность
        let draggedElement = null;
        const statusUpdateTimeouts = {};

        function debounceStatusUpdate(taskId, oldStatus, newStatus) {
            if (statusUpdateTimeouts[taskId]) {
                clearTimeout(statusUpdateTimeouts[taskId]);
            }
            statusUpdateTimeouts[taskId] = setTimeout(() => {
                fetch('/tasks/update-status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `task_id=${taskId}&old_status=${oldStatus}&new_status=${newStatus}`
                }).then(() => updateColumnCounts());
                delete statusUpdateTimeouts[taskId];
            }, 500);
        }

        function updateColumnCounts() {
            document.querySelectorAll('.kanban-column').forEach(col => {
                const status = col.dataset.status;
                const cnt = col.querySelectorAll('.task-card').length;
                const badge = document.getElementById('count-' + status);
                if (badge) badge.textContent = cnt;
            });
        }

        function filterTasks(search) {
            const value = search.trim().toLowerCase();
            document.querySelectorAll('.task-card').forEach(card => {
                let t = card.dataset.title.toLowerCase();
                let d = card.dataset.desc.toLowerCase();
                let a = (card.dataset.assignees || '').toLowerCase();
                card.style.display = (t.includes(value) || d.includes(value) || a.includes(value)) ? '' : 'none';
            });
            updateColumnCounts();
        }

        document.getElementById('kanbanSearch').addEventListener('input', function() {
            filterTasks(this.value);
        });

        // DnD events
        document.querySelectorAll('.task-card').forEach(card => {
            card.addEventListener('dragstart', function(e) {
                draggedElement = this;
                setTimeout(() => this.classList.add('dragging'), 0);
                e.dataTransfer.effectAllowed = 'move';
            });
            card.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
            });
        });

        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            column.addEventListener('drop', function(e) {
                e.preventDefault();
                if (draggedElement) {
                    const oldStatus = draggedElement.dataset.currentStatus;
                    const newStatus = this.dataset.status;
                    const taskId = draggedElement.dataset.taskId;
                    if (oldStatus !== newStatus) {
                        this.querySelector('.kanban-tasks-list').appendChild(draggedElement);
                        draggedElement.dataset.currentStatus = newStatus;
                        debounceStatusUpdate(taskId, oldStatus, newStatus);
                    }
                    updateColumnCounts();
                }
            });
        });

        // Delete Task
        let deleteTaskId = null;
        document.querySelectorAll('.delete-task-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteTaskId = btn.dataset.taskId;
                new bootstrap.Modal(document.getElementById('deleteTaskModal')).show();
            });
        });
        document.getElementById('confirmDeleteTaskBtn').onclick = function() {
            if (!deleteTaskId) return;
            fetch('/tasks/delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `task_id=${deleteTaskId}`
            }).then(() => location.reload());
        };

        // Edit Task
        document.querySelectorAll('.edit-task-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = btn.closest('.task-card');
                document.getElementById('editTaskId').value = card.dataset.taskId;
                document.getElementById('editTaskTitle').value = card.dataset.title;
                document.getElementById('editTaskDesc').value = card.dataset.desc;
                document.getElementById('editTaskAssignees').value = card.dataset.assignees;
                document.getElementById('editTaskDeadline').value = card.querySelector('.deadline-badge') ? 
                    card.querySelector('.deadline-badge').textContent.trim().split('.').reverse().join('-').replace(/[^0-9\-]/g,'') : '';
                document.getElementById('editTaskPriority').value = Array.from(card.classList).find(cl => cl.startsWith('priority-')).replace('priority-','');
                new bootstrap.Modal(document.getElementById('editTaskModal')).show();
            });
        });

        document.getElementById('editTaskForm').onsubmit = function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new URLSearchParams(new FormData(form)).toString();
            fetch('/tasks/edit', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            }).then(() => location.reload());
        };
    </script>
</body>
</html>