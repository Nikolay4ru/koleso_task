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
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
        }
}