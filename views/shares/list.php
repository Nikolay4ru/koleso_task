<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои ссылки - Общий доступ к файлам</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .share-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        .share-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .share-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .file-icon {
            font-size: 2.5rem;
            color: #6c757d;
        }
        .share-url {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            color: #495057;
        }
        .stats-row {
            display: flex;
            gap: 2rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
        }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-expired { background-color: #f8d7da; color: #721c24; }
        .status-limit-reached { background-color: #fff3cd; color: #856404; }
        
        .btn-group-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        
        <!-- Заголовок -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Мои ссылки</h1>
                <p class="text-muted mb-0">Управление ссылками для совместного доступа к файлам</p>
            </div>
            <div>
                <a href="/tasks" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Назад к задачам
                </a>
            </div>
        </div>

        <!-- Статистика -->
        <?php if (!empty($shares)): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-link-45deg text-primary fs-1"></i>
                        <h4 class="mt-2"><?= count($shares) ?></h4>
                        <small class="text-muted">Всего ссылок</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-eye text-info fs-1"></i>
                        <h4 class="mt-2"><? array_sum(array_column($shares, 'download_count')) ?></h4>
                        <small class="text-muted">Скачиваний</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-check-circle text-success fs-1"></i>
                        <h4 class="mt-2"><? count(array_filter($shares, fn($s) => $s['is_active'])) ?></h4>
                        <small class="text-muted">Активных</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-file-earmark text-warning fs-1"></i>
                        <h4 class="mt-2"><? count(array_unique(array_column($shares, 'original_name'))) ?></h4>
                        <small class="text-muted">Файлов</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Список ссылок -->
        <?php if (empty($shares)): ?>
            <div class="empty-state">
                <i class="bi bi-share"></i>
                <h4>У вас пока нет общих ссылок</h4>
                <p>Создайте ссылку для совместного доступа к файлам из задач</p>
                <a href="/tasks" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Перейти к задачам
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($shares as $share): ?>
                <div class="share-card" data-share-code="<?= $share['short_code'] ?>">
                    
                    <!-- Заголовок карточки -->
                    <div class="share-header">
                        <div class="file-info">
                            <i class="<?= $this->getFileIcon($share['mime_type']) ?> file-icon"></i>
                            <div>
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($share['title'] ?: $share['original_name']) ?>
                                </h5>
                                <small class="text-muted">
                                    <?= htmlspecialchars($share['original_name']) ?>
                                    • <?= $this->formatFileSize($share['size']) ?>
                                    • Создана <?= date('d.m.Y в H:i', strtotime($share['created_at'])) ?>
                                </small>

                                <small class="text-muted">
    <?= htmlspecialchars($share['original_name']) ?>
    • <?= $this->formatFileSize($share['size']) ?>
    • 
    <?php 
    $ext = strtoupper(pathinfo($share['original_name'], PATHINFO_EXTENSION));
    $descriptions = [
        'XLSB' => 'Excel Binary',
        'XLSX' => 'Excel',
        'XLS' => 'Excel Legacy', 
        'DOCX' => 'Word',
        'DOC' => 'Word Legacy',
        'PDF' => 'PDF',
        'PPTX' => 'PowerPoint',
        'PPT' => 'PowerPoint Legacy'
    ];
    echo $descriptions[$ext] ?? $ext;
    ?>
    • Создана <?= date('d.m.Y в H:i', strtotime($share['created_at'])) ?>
</small>
                            </div>
                        </div>
                        
                        <!-- Статус -->
                        <div>
                            <?php 
                            $status = 'active';
                            $statusText = 'Активна';
                            
                            if (!$share['is_active']) {
                                $status = 'expired';
                                $statusText = 'Отключена';
                            } elseif ($share['expires_at'] && strtotime($share['expires_at']) < time()) {
                                $status = 'expired';
                                $statusText = 'Истекла';
                            } elseif ($share['max_downloads'] && $share['download_count'] >= $share['max_downloads']) {
                                $status = 'limit-reached';
                                $statusText = 'Лимит исчерпан';
                            }
                            ?>
                            <span class="badge status-badge status-<?= $status ?>"><?= $statusText ?></span>
                        </div>
                    </div>

                    <!-- Описание -->
                    <?php if ($share['description']): ?>
                        <div class="mb-3">
                            <small class="text-muted"><?= nl2br(htmlspecialchars($share['description'])) ?></small>
                        </div>
                    <?php endif; ?>

                    <!-- Ссылка -->
                    <div class="mb-3">
                        <label class="form-label small text-muted">ССЫЛКА ДЛЯ СОВМЕСТНОГО ДОСТУПА</label>
                        <div class="input-group">
                            <input type="text" class="form-control share-url" 
                                   value="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/s/<?= $share['short_code'] ?>" 
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="copyToClipboard('<?= $share['short_code'] ?>')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Статистика и ограничения -->
                    <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-number"><?= $share['download_count'] ?></div>
                            <div class="stat-label">Скачиваний</div>
                        </div>
                        
                        <?php if ($share['max_downloads']): ?>
                            <div class="stat-item">
                                <div class="stat-number"><?= $share['max_downloads'] ?></div>
                                <div class="stat-label">Лимит</div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($share['expires_at']): ?>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?= date('d.m.Y', strtotime($share['expires_at'])) ?>
                                </div>
                                <div class="stat-label">Истекает</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="stat-item">
                            <div class="stat-number">
                                <i class="bi bi-<?= $share['password'] ? 'lock' : 'unlock' ?>"></i>
                            </div>
                            <div class="stat-label"><?= $share['password'] ? 'Защищена' : 'Открыта' ?></div>
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="btn-group-actions">
                        <a href="/s/<?= $share['short_code'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Просмотр
                        </a>
                        
                        <button type="button" class="btn btn-sm btn-outline-info" 
                                onclick="showShareQR('<?= $share['short_code'] ?>')">
                            <i class="bi bi-qr-code me-1"></i>QR код
                        </button>
                        
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                onclick="showShareStats('<?= $share['short_code'] ?>')">
                            <i class="bi bi-graph-up me-1"></i>Статистика
                        </button>
                        
                        <?php if ($share['is_active']): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                    onclick="deactivateShare('<?= $share['short_code'] ?>')">
                                <i class="bi bi-pause-circle me-1"></i>Отключить
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteShare('<?= $share['short_code'] ?>')">
                            <i class="bi bi-trash me-1"></i>Удалить
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR код для ссылки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrCodeContainer"></div>
                    <p class="mt-3 text-muted">Отсканируйте для быстрого доступа</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Modal -->
    <div class="modal fade" id="statsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Статистика ссылки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="statsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>