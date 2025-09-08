<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/telegram.php';

use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\DashboardController;
use App\Controllers\TaskController;
use App\Controllers\UserController;
use App\Controllers\DepartmentController;
use App\Controllers\NotificationController;
use App\Controllers\VideoConferenceController;
use App\Controllers\SignalingController;
use App\Controllers\FileController;
use App\Controllers\MessengerController; // Новый контроллер мессенджера
use App\Services\EmailService;
use App\Services\TelegramService;
use App\Services\NotificationService;

// Подключение к БД
$db = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Инициализация сервисов
$emailService = new EmailService($mailConfig);
$telegramService = new TelegramService($telegramConfig['bot_token']);
$notificationService = new NotificationService($db, $emailService, $telegramService);

// Роутинг
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Публичные маршруты
$publicRoutes = ['/login', '/register', '/telegram-webhook', '/forgot-password'];

// Проверка авторизации
if (!in_array($uri, $publicRoutes) && !isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Маршруты
switch ($uri) {
    case '/':
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
        } else {
            header('Location: /login');
        }
        break;
        
    case '/dashboard':
        $controller = new DashboardController($db);
        $controller->index();
        break;
        
    case '/dashboard/chart-data':
        $controller = new DashboardController($db);
        $controller->getChartData();
        break;
        
    // ===== АВТОРИЗАЦИЯ =====
    case '/login':
        $controller = new AuthController($db);
        $controller->login();
        break;
        
    case '/register':
        $controller = new AuthController($db);
        $controller->register();
        break;
        
    case '/logout':
        $controller = new AuthController($db);
        $controller->logout();
        break;
        
    case '/forgot-password':
        $controller = new AuthController($db);
        $controller->forgotPassword();
        break;
        
    // ===== ЗАДАЧИ =====
    case '/tasks/list':
        $controller = new TaskController($db, $notificationService);
        $controller->list();
        break;
        
    case '/tasks/grid':
        $controller = new TaskController($db, $notificationService);
        $controller->grid();
        break;
        
    case '/tasks/kanban':
        $controller = new TaskController($db, $notificationService);
        $controller->kanban();
        break;
        
    case '/tasks/create':
        $controller = new TaskController($db, $notificationService);
        $controller->create();
        break;
        
    case '/tasks/update-status':
        $controller = new TaskController($db, $notificationService);
        $controller->updateStatus();
        break;
        
    // ===== ПРОФИЛЬ =====
    case '/profile':
        $controller = new UserController($db);
        $controller->profile();
        break;
        
    case '/profile/settings':
        $controller = new UserController($db);
        $controller->settings();
        break;
        
    case '/profile/update':
        $controller = new UserController($db);
        $controller->update();
        break;
        
    case '/profile/test-notification':
        $controller = new UserController($db);
        $controller->testNotification();
        break;
        
    case '/profile/delete':
        $controller = new UserController($db);
        $controller->delete();
        break;
        
    // ===== АДМИН ПАНЕЛЬ =====
    case '/admin/dashboard':
        $controller = new AdminController($db, $emailService);
        $controller->dashboard();
        break;
        
    case '/admin/users':
        $controller = new AdminController($db, $emailService);
        $controller->users();
        break;
        
    case '/admin/tasks':
        $controller = new AdminController($db, $emailService);
        $controller->tasks();
        break;
        
    case '/admin/invitations':
        $controller = new AdminController($db, $emailService);
        $controller->invitations();
        break;
        
    case '/admin/invitations/send':
        $controller = new AdminController($db, $emailService);
        $controller->sendInvitation();
        break;
        
    case '/admin/departments':
        $controller = new AdminController($db, $emailService);
        $controller->departments();
        break;
        
    case '/admin/departments/create':
        $controller = new AdminController($db, $emailService);
        $controller->createDepartment();
        break;
        
    case '/admin/reports':
        $controller = new AdminController($db, $emailService);
        $controller->reports();
        break;
        
    // ===== УВЕДОМЛЕНИЯ =====
    case '/notifications':
        $controller = new NotificationController($db);
        $controller->index();
        break;
        
    case '/notifications/recent':
        $controller = new NotificationController($db);
        $controller->getRecent();
        break;
        
    case '/notifications/mark-all-read':
        $controller = new NotificationController($db);
        $controller->markAllAsRead();
        break;
        
    // ===== ФАЙЛЫ =====
    case '/file/upload':
        $controller = new FileController($db);
        $controller->upload();
        break;
        
    // ===== ВИДЕОКОНФЕРЕНЦИИ =====
    case '/conference':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->index();
        break;
        
    case '/conference/create':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->create();
        break;
        
    case '/conference/quick-create':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->quickCreate();
        break;
        
    case '/conference/scheduled':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->scheduled();
        break;
        
    case '/conference/history':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->history();
        break;
        
    case '/conference/send-message':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->sendMessage();
        break;
        
    case '/conference/start-recording':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->startRecording();
        break;
        
    case '/conference/stop-recording':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->stopRecording();
        break;
        
    case '/conference/end':
        $controller = new VideoConferenceController($db, $notificationService);
        $controller->end();
        break;
        
    // ===== SIGNALING (WebRTC) =====
    case '/signaling':
        $controller = new SignalingController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->send();
        } else {
            $controller->poll();
        }
        break;
        
    case '/signaling/poll':
        $controller = new SignalingController($db);
        $controller->poll();
        break;
        
    case '/signaling/send':
        $controller = new SignalingController($db);
        $controller->send();
        break;
        
    // ===== МЕССЕНДЖЕР (НОВЫЕ МАРШРУТЫ) =====
    case '/messenger':
        $controller = new MessengerController($db);
        $controller->index();
        break;
        
    case '/messenger/chat/open':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $chatId = $_POST['chat_id'] ?? null;
            $controller->openChat($chatId);
        } else {
            header('Location: /messenger');
        }
        break;
        
    case '/messenger/chat/create':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createChat();
        } else {
            header('Location: /messenger');
        }
        break;
        
    case '/messenger/message/send':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->sendMessage();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/message/edit':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->editMessage();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/message/delete':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->deleteMessage();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/reaction/add':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->addReaction();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/reaction/remove':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->removeReaction();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/messages/new':
        $controller = new MessengerController($db);
        $controller->getNewMessages();
        break;
        
    case '/messenger/messages/search':
        $controller = new MessengerController($db);
        $controller->searchMessages();
        break;
        
    case '/messenger/call/initiate':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->initiateCall();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/call/join':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->joinCall();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/call/leave':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->leaveCall();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/call/end':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->endCall();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/status/update':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateStatus();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/typing/start':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->startTyping();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/messenger/typing/stop':
        $controller = new MessengerController($db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->stopTyping();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        // ===== ОБРАБОТКА ДИНАМИЧЕСКИХ МАРШРУТОВ =====
        
        // Мессенджер - открытие чата по ID
        if (preg_match('/^\/messenger\/chat\/(\d+)$/', $uri, $matches)) {
            $controller = new MessengerController($db);
            $controller->openChat($matches[1]);
            
        // Мессенджер - история чата
        } elseif (preg_match('/^\/messenger\/chat\/(\d+)\/history$/', $uri, $matches)) {
            $controller = new MessengerController($db);
            $controller->getChatHistory($matches[1]);
            
        // Мессенджер - участники чата
        } elseif (preg_match('/^\/messenger\/chat\/(\d+)\/participants$/', $uri, $matches)) {
            $controller = new MessengerController($db);
            $controller->getChatParticipants($matches[1]);
            
        // Мессенджер - загрузка файла в чат
        } elseif (preg_match('/^\/messenger\/chat\/(\d+)\/upload$/', $uri, $matches)) {
            $controller = new MessengerController($db);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->uploadFile($matches[1]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Method not allowed']);
            }
            
        // Уведомления
        } elseif (preg_match('/^\/notifications\/mark-read\/(\d+)$/', $uri, $matches)) {
            $controller = new NotificationController($db);
            $controller->markAsRead($matches[1]);
            
        } elseif (preg_match('/^\/notifications\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new NotificationController($db);
            $controller->delete($matches[1]);
            
        // Задачи
        } elseif (preg_match('/^\/tasks\/view\/(\d+)$/', $uri, $matches)) {
            $controller = new TaskController($db, $notificationService);
            $controller->view($matches[1]);
            
        } elseif (preg_match('/^\/tasks\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new TaskController($db, $notificationService);
            $controller->edit($matches[1]);
            
        } elseif (preg_match('/^\/tasks\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new TaskController($db, $notificationService);
            $controller->delete($matches[1]);
            
        } elseif (preg_match('/^\/tasks\/duplicate\/(\d+)$/', $uri, $matches)) {
            $controller = new TaskController($db, $notificationService);
            $controller->duplicate($matches[1]);
            
        } elseif (preg_match('/^\/tasks\/(\d+)\/comment$/', $uri, $matches)) {
            $controller = new TaskController($db, $notificationService);
            $controller->addComment($matches[1]);
            
        // Админ - пользователи
        } elseif (preg_match('/^\/admin\/users\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->editUser($matches[1]);
            
        } elseif (preg_match('/^\/admin\/users\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->deleteUser($matches[1]);
            
        // Админ - приглашения
        } elseif (preg_match('/^\/admin\/invitations\/resend\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->resendInvitation($matches[1]);
            
        } elseif (preg_match('/^\/admin\/invitations\/cancel\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->cancelInvitation($matches[1]);
            
        // Админ - отделы
        } elseif (preg_match('/^\/admin\/departments\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->editDepartment($matches[1]);
            
        } elseif (preg_match('/^\/admin\/departments\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->deleteDepartment($matches[1]);
            
        // Конференции
        } elseif (preg_match('/^\/conference\/room\/(.+)$/', $uri, $matches)) {
            $controller = new VideoConferenceController($db, $notificationService);
            $controller->room($matches[1]);
            
        // Файлы
        } elseif (preg_match('/^\/file\/download\/(\d+)$/', $uri, $matches)) {
            $controller = new FileController($db);
            $controller->download($matches[1]);
            
        } elseif (preg_match('/^\/file\/preview\/(\d+)$/', $uri, $matches)) {
            $controller = new FileController($db);
            $controller->preview($matches[1]);


        } elseif ($uri === '/shares/create') {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->create();

    } elseif ($uri === '/shares/upload') {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->uploadPage();

// Быстрое создание ссылки (загрузка + создание за один запрос)
} elseif ($uri === '/shares/quick-create') {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->quickShare();

// Обновленный маршрут для загрузки файлов (с поддержкой типа 'share')
} elseif ($uri === '/files/upload') {
    $controller = new \App\Controllers\FileController($db);
    $controller->upload();


// Мои ссылки
} elseif ($uri === '/shares/my') {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->myShares();

// Просмотр файла по короткой ссылке
} elseif (preg_match('/^\/s\/([a-zA-Z0-9]+)$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->view($matches[1]);

// Предпросмотр файла по короткой ссылке
} elseif (preg_match('/^\/s\/([a-zA-Z0-9]+)\/preview$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->preview($matches[1]);

// Скачивание файла по короткой ссылке
} elseif (preg_match('/^\/s\/([a-zA-Z0-9]+)\/download$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->download($matches[1]);

// Статистика ссылки
} elseif (preg_match('/^\/shares\/([a-zA-Z0-9]+)\/stats$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->stats($matches[1]);

// Деактивация ссылки
} elseif (preg_match('/^\/shares\/([a-zA-Z0-9]+)\/deactivate$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->deactivate($matches[1]);

// Удаление ссылки
} elseif (preg_match('/^\/shares\/([a-zA-Z0-9]+)\/delete$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->delete($matches[1]);

// Генерация предпросмотра для файла
} elseif (preg_match('/^\/files\/(\d+)\/generate-preview$/', $uri, $matches)) {
    $controller = new \App\Controllers\FileShareController($db);
    $controller->generatePreview($matches[1]);
            
        // Загруженные файлы (статические)
        } elseif (preg_match('/^\/uploads\/(.+)$/', $uri, $matches)) {
            $filePath = __DIR__ . '/../uploads/' . $matches[1];
            if (file_exists($filePath) && is_file($filePath)) {
                // Определяем MIME-тип
                $mimeType = mime_content_type($filePath);
                
                // Отправляем заголовки
                header('Content-Type: ' . $mimeType);
                header('Content-Length: ' . filesize($filePath));
                header('Cache-Control: public, max-age=86400');
                
                // Отправляем файл
                readfile($filePath);
                exit;
            } else {
                http_response_code(404);
                include __DIR__ . '/../views/errors/404.php';
            }
            
        } else {
            // 404 страница
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
        }
}