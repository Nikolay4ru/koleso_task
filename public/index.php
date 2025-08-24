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
//print_r($uri); // Отладочный вывод для проверки текущего URI
// Маршруты
switch ($uri) {
    case '/':
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
        } elseif (preg_match('/^\/admin\/users\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->editUser($matches[1]);
        } elseif (preg_match('/^\/admin\/users\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->deleteUser($matches[1]);
        } elseif (preg_match('/^\/admin\/invitations\/resend\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->resendInvitation($matches[1]);
        } elseif (preg_match('/^\/admin\/invitations\/cancel\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->cancelInvitation($matches[1]);
        } elseif (preg_match('/^\/admin\/departments\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->editDepartment($matches[1]);
        } elseif (preg_match('/^\/admin\/departments\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->deleteDepartment($matches[1]);
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
        
    case '/profile/test-notification':
        $controller = new UserController($db);
        $controller->testNotification();
        break;
        
    case '/profile/delete':
        $controller = new UserController($db);
        $controller->delete();
        break;
        
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
        
    case '/file/upload':
        $controller = new FileController($db);
        $controller->upload();
        break;
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
        
    default:
        // Обработка динамических роутов
        if (preg_match('/^\/notifications\/mark-read\/(\d+)$/', $uri, $matches)) {
            $controller = new NotificationController($db);
            $controller->markAsRead($matches[1]);
        } elseif (preg_match('/^\/notifications\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new NotificationController($db);
            $controller->delete($matches[1]);
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
        } elseif (preg_match('/^\/file\/download\/(\d+)$/', $uri, $matches)) {
            $controller = new FileController($db);
            $controller->download($matches[1]);
        } elseif (preg_match('/^\/file\/preview\/(\d+)$/', $uri, $matches)) {
            $controller = new FileController($db);
            $controller->preview($matches[1]);
        } elseif (preg_match('/^\/file\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new FileController($db);
            $controller->delete($matches[1]);
        } elseif (preg_match('/^\/admin\/users\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->editUser($matches[1]);
        } elseif (preg_match('/^\/admin\/users\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->deleteUser($matches[1]);
        } elseif (preg_match('/^\/admin\/invitations\/resend\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->resendInvitation($matches[1]);
        } elseif (preg_match('/^\/admin\/invitations\/cancel\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->cancelInvitation($matches[1]);
        } elseif (preg_match('/^\/admin\/departments\/edit\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->editDepartment($matches[1]);
        } elseif (preg_match('/^\/admin\/departments\/delete\/(\d+)$/', $uri, $matches)) {
            $controller = new AdminController($db, $emailService);
            $controller->deleteDepartment($matches[1]);
        } 
          elseif (preg_match('/^\/conference\/join\/([0-9-]+)$/', $uri, $matches)) {
            $controller = new VideoConferenceController($db, $notificationService);
            $controller->join($matches[1]);
        }
        
        // API для получения участников конференции
elseif (preg_match('/^\/api\/conference\/participants\/(\d+)$/', $uri, $matches)) {
    header('Content-Type: application/json');
    
    $conferenceId = $matches[1];
    $controller = new VideoConferenceController($db, $notificationService);
    
    try {
        // Получаем участников через модель
        $participants = $controller->getConferenceModel()->getParticipants($conferenceId);
        
        if ($participants === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Conference not found']);
        } else {
            echo json_encode($participants);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
    exit;
}

// API для выхода из конференции
elseif (preg_match('/^\/conference\/leave\/(\d+)$/', $uri, $matches)) {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $conferenceId = $matches[1];
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $controller = new VideoConferenceController($db, $notificationService);
    
    try {
        $controller->conferenceModel->updateParticipantLeftTime($conferenceId, $userId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
    exit;
}
    elseif (preg_match('/^\/uploads\/(.+)$/', $uri, $matches)) {
            // Обработка статических файлов из папки uploads
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
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
        }
}