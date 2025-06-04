<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/telegram.php';

use App\Models\User;

// Получаем данные от Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$db = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
$userModel = new User($db);

// Обработка команд
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    
    if ($text === '/start') {
        // Отправляем пользователю его Chat ID
        $response = "Ваш Chat ID: <code>{$chatId}</code>\n\n";
        $response .= "Скопируйте его и вставьте в настройках профиля для получения уведомлений.";
        
        sendTelegramMessage($chatId, $response);
    }
    
    if (strpos($text, '/link') === 0) {
        // Связывание аккаунта
        $parts = explode(' ', $text);
        if (count($parts) === 2) {
            $email = $parts[1];
            
            $user = $userModel->findByEmail($email);
            if ($user) {
                $userModel->updateSettings($user['id'], [
                    'telegram_chat_id' => $chatId,
                    'telegram_notifications' => true
                ]);
                
                sendTelegramMessage($chatId, "✅ Аккаунт успешно связан!");
            } else {
                sendTelegramMessage($chatId, "❌ Пользователь с таким email не найден.");
            }
        } else {
            sendTelegramMessage($chatId, "Использование: /link ваш@email.com");
        }
    }
}

function sendTelegramMessage($chatId, $text) {
    global $telegramConfig;
    
    $url = "https://api.telegram.org/bot{$telegramConfig['bot_token']}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_exec($ch);
    curl_close($ch);
}