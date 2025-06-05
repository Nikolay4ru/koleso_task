#!/usr/bin/env php
<?php
/**
 * Процессор очереди уведомлений
 * Запускать через cron каждую минуту:
 * * * * * * /usr/bin/php /var/www/task.koleso.app/scripts/process-notification-queue.php
 */

// Проверяем, что скрипт запущен из командной строки
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line');
}

// Подключаем автозагрузчик
require_once __DIR__ . '/../vendor/autoload.php';

// Подключаем конфигурацию
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/telegram.php';

use App\Models\NotificationQueue;
use App\Services\EmailService;
use App\Services\TelegramService;

// Файл блокировки для предотвращения запуска нескольких процессов
$lockFile = __DIR__ . '/../storage/queue.lock';

// Проверяем, не запущен ли уже процесс
if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    // Проверяем, существует ли процесс с таким PID
    if (posix_kill($pid, 0)) {
        echo "Queue processor is already running (PID: $pid)\n";
        exit;
    } else {
        // Процесс не существует, удаляем старый lock файл
        unlink($lockFile);
    }
}

// Создаем lock файл
file_put_contents($lockFile, getmypid());

// Обработчик сигналов для корректного завершения
function signalHandler($signal) {
    global $lockFile;
    unlink($lockFile);
    echo "\nQueue processor stopped\n";
    exit;
}

// Регистрируем обработчики сигналов
pcntl_signal(SIGTERM, 'signalHandler');
pcntl_signal(SIGINT, 'signalHandler');

try {
    // Подключаемся к БД
    $db = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Инициализируем сервисы
    $queue = new NotificationQueue($db);
    $emailService = new EmailService($mailConfig);
    $telegramService = new TelegramService($telegramConfig['bot_token']);
    
    echo "Queue processor started at " . date('Y-m-d H:i:s') . "\n";
    
    // Получаем статистику очереди
    $stats = $queue->getStatistics();
    foreach ($stats as $stat) {
        echo "Status: {$stat['status']} - Count: {$stat['count']}\n";
    }
    
    // Обрабатываем пакет уведомлений
    $notifications = $queue->getNextBatch(20);
    
    if (empty($notifications)) {
        echo "No pending notifications\n";
    } else {
        echo "Processing " . count($notifications) . " notifications...\n";
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($notifications as $notification) {
            // Проверяем сигналы
            pcntl_signal_dispatch();
            
            echo "Processing notification #{$notification['id']} ";
            echo "({$notification['channel']} to {$notification['recipient']})... ";
            
            // Отмечаем как обрабатываемое
            $queue->markAsProcessing($notification['id']);
            
            try {
                $sent = false;
                
                if ($notification['channel'] === 'email') {
                    $sent = $emailService->send(
                        $notification['recipient'],
                        $notification['subject'],
                        $notification['message']
                    );
                } elseif ($notification['channel'] === 'telegram') {
                    $result = $telegramService->sendMessage(
                        $notification['recipient'],
                        $notification['message']
                    );
                    $sent = $result && isset($result['ok']) && $result['ok'];
                }
                
                if ($sent) {
                    $queue->markAsSent($notification['id']);
                    echo "✓ Sent\n";
                    $successCount++;
                } else {
                    throw new Exception('Failed to send notification');
                }
                
            } catch (Exception $e) {
                $queue->markAsFailed($notification['id'], $e->getMessage());
                echo "✗ Failed: " . $e->getMessage() . "\n";
                $failureCount++;
            }
            
            // Небольшая задержка между отправками
            usleep(100000); // 0.1 секунда
        }
        
        echo "\nProcessing complete: $successCount sent, $failureCount failed\n";
    }
    
    // Очищаем старые обработанные уведомления (старше 7 дней)
    $cleaned = $queue->cleanOld(7);
    if ($cleaned > 0) {
        echo "Cleaned $cleaned old notifications\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Queue processor error: " . $e->getMessage());
} finally {
    // Удаляем lock файл
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

echo "Queue processor finished at " . date('Y-m-d H:i:s') . "\n";