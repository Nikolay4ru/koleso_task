<?php
// create_share_tables.php - запустить однократно для создания таблиц

require_once 'var/www/task.koleso.app/config/database.php';

try {
    echo "<h2>Создание таблиц для системы коротких ссылок</h2>\n";
    
    // 1. Создание таблицы file_shares
    echo "<h3>1. Создание таблицы file_shares</h3>\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `file_shares` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `file_id` int(11) NOT NULL,
        `short_code` varchar(10) NOT NULL UNIQUE,
        `created_by` int(11) NOT NULL,
        `title` varchar(255) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `expires_at` datetime DEFAULT NULL,
        `password` varchar(255) DEFAULT NULL,
        `download_count` int(11) DEFAULT 0,
        `max_downloads` int(11) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `allow_preview` tinyint(1) DEFAULT 1,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_short_code` (`short_code`),
        KEY `idx_file_id` (`file_id`),
        KEY `idx_created_by` (`created_by`),
        KEY `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($db->exec($sql) !== false) {
        echo "✅ Таблица file_shares создана успешно<br>\n";
    } else {
        echo "❌ Ошибка создания таблицы file_shares<br>\n";
    }
    
    // 2. Создание таблицы file_share_logs
    echo "<h3>2. Создание таблицы file_share_logs</h3>\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `file_share_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `share_id` int(11) NOT NULL,
        `action` enum('view', 'preview', 'download') NOT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_share_id` (`share_id`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($db->exec($sql) !== false) {
        echo "✅ Таблица file_share_logs создана успешно<br>\n";
    } else {
        echo "❌ Ошибка создания таблицы file_share_logs<br>\n";
    }
    
    // 3. Добавление новых полей в таблицу files (если их нет)
    echo "<h3>3. Обновление таблицы files</h3>\n";
    
    // Проверяем, существуют ли уже новые поля
    $stmt = $db->query("SHOW COLUMNS FROM files LIKE 'document_preview'");
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE `files` 
                ADD COLUMN `document_preview` longtext DEFAULT NULL COMMENT 'Текстовое содержимое для предпросмотра',
                ADD COLUMN `preview_generated` tinyint(1) DEFAULT 0 COMMENT 'Флаг генерации предпросмотра',
                ADD COLUMN `pages_count` int(11) DEFAULT NULL COMMENT 'Количество страниц в документе'";
        
        if ($db->exec($sql) !== false) {
            echo "✅ Таблица files обновлена успешно<br>\n";
        } else {
            echo "❌ Ошибка обновления таблицы files<br>\n";
        }
    } else {
        echo "✅ Таблица files уже содержит необходимые поля<br>\n";
    }
    
    // 4. Создание внешних ключей (если поддерживается)
    echo "<h3>4. Создание внешних ключей</h3>\n";
    
    try {
        // Проверяем, существуют ли уже внешние ключи
        $stmt = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                           WHERE TABLE_NAME = 'file_shares' AND CONSTRAINT_NAME LIKE 'fk_%'");
        
        if ($stmt->rowCount() == 0) {
            // Добавляем внешние ключи
            $sql = "ALTER TABLE `file_shares` 
                    ADD CONSTRAINT `fk_file_shares_file_id` 
                        FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
                    ADD CONSTRAINT `fk_file_shares_created_by` 
                        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE";
            
            $db->exec($sql);
            
            $sql = "ALTER TABLE `file_share_logs` 
                    ADD CONSTRAINT `fk_file_share_logs_share_id` 
                        FOREIGN KEY (`share_id`) REFERENCES `file_shares` (`id`) ON DELETE CASCADE";
            
            $db->exec($sql);
            
            echo "✅ Внешние ключи созданы успешно<br>\n";
        } else {
            echo "✅ Внешние ключи уже существуют<br>\n";
        }
    } catch (Exception $e) {
        echo "⚠️ Внешние ключи не созданы (возможно, нет прав или таблицы users не существует): " . $e->getMessage() . "<br>\n";
    }
    
    // 5. Создание папок для загруженных файлов
    echo "<h3>5. Создание структуры папок</h3>\n";
    
    $uploadsDir = __DIR__ . '/uploads';
    $subdirs = ['tasks', 'comments', 'thumbnails', 'share'];
    
    if (!is_dir($uploadsDir)) {
        if (mkdir($uploadsDir, 0755, true)) {
            echo "✅ Папка uploads создана<br>\n";
        } else {
            echo "❌ Не удалось создать папку uploads<br>\n";
        }
    } else {
        echo "✅ Папка uploads уже существует<br>\n";
    }
    
    foreach ($subdirs as $subdir) {
        $path = $uploadsDir . '/' . $subdir;
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                echo "✅ Папка $subdir создана<br>\n";
            } else {
                echo "❌ Не удалось создать папку $subdir<br>\n";
            }
        } else {
            echo "✅ Папка $subdir уже существует<br>\n";
        }
    }
    
    // 6. Создание .htaccess для безопасности uploads
    echo "<h3>6. Настройка безопасности</h3>\n";
    
    $htaccessPath = $uploadsDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "# Запрещаем выполнение PHP файлов в uploads
<Files \"*.php\">
    Order Allow,Deny
    Deny from all
</Files>

# Разрешаем доступ к файлам через наши скрипты
Options -Indexes
";
        
        if (file_put_contents($htaccessPath, $htaccessContent)) {
            echo "✅ Файл .htaccess создан для безопасности<br>\n";
        } else {
            echo "❌ Не удалось создать файл .htaccess<br>\n";
        }
    } else {
        echo "✅ Файл .htaccess уже существует<br>\n";
    }
    
    echo "<br><h3>🎉 Установка завершена!</h3>\n";
    echo "Теперь система готова для работы с короткими ссылками.<br>\n";
    echo "Вы можете удалить этот файл (create_share_tables.php) после установки.<br>\n";
    
    // 7. Тестовая проверка
    echo "<h3>7. Финальная проверка</h3>\n";
    
    $stmt = $db->query("SELECT COUNT(*) as tables_count FROM information_schema.tables 
                       WHERE table_schema = DATABASE() 
                       AND table_name IN ('file_shares', 'file_share_logs')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['tables_count'] == 2) {
        echo "✅ Все необходимые таблицы созданы и готовы к работе!<br>\n";
    } else {
        echo "❌ Не все таблицы были созданы. Проверьте ошибки выше.<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка при создании таблиц: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>\n";
}
?>