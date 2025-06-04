<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\Task;
use App\Services\NotificationService;

class UserController {
    private $db;
    private $user;
    private $department;
    private $task;
    
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
        $this->department = new Department($db);
        $this->task = new Task($db);
    }
    
    public function profile() {
        $userId = $_SESSION['user_id'];
        
        // Получаем полную информацию о пользователе
        $user = $this->user->findById($userId);
        
        if (!$user) {
            header('Location: /logout');
            exit;
        }
        
        // Получаем статистику пользователя
        $stats = $this->user->getTaskStatistics($userId);
        
        // Получаем последние задачи
        $recentTasks = $this->task->getUserRecentTasks($userId, 10);
        
        // Получаем активные задачи
        $activeTasks = $this->task->getActiveTasksForUser($userId);
        
        require_once __DIR__ . '/../../views/profile/index.php';
    }
    
    public function settings() {
        $userId = $_SESSION['user_id'];
        
        // Получаем информацию о пользователе
        $user = $this->user->findById($userId);
        
        if (!$user) {
            header('Location: /logout');
            exit;
        }
        
        // Получаем список отделов
        $departments = $this->department->getAll();
        
        require_once __DIR__ . '/../../views/profile/settings.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile/settings');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $section = $_POST['section'] ?? '';
        
        switch ($section) {
            case 'general':
                $this->updateGeneral($userId);
                break;
                
            case 'notifications':
                $this->updateNotifications($userId);
                break;
                
            case 'security':
                $this->updateSecurity($userId);
                break;
                
            default:
                header('Location: /profile/settings');
        }
    }
    
    private function updateGeneral($userId) {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => $_POST['phone'] ?? null,
            'department_id' => $_POST['department_id'] ?? null,
            'bio' => $_POST['bio'] ?? null
        ];
        
        // Валидация
        if (empty($data['name'])) {
            $error = 'Имя не может быть пустым';
            $user = $this->user->findById($userId);
            $departments = $this->department->getAll();
            require_once __DIR__ . '/../../views/profile/settings.php';
            return;
        }
        
        // Обработка загрузки аватара
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Здесь должна быть логика загрузки и сохранения файла
            // Пример:
            // $avatarPath = $this->uploadAvatar($_FILES['avatar']);
            // $data['avatar'] = $avatarPath;
        }
        
        try {
            $this->user->update($userId, $data);
            $_SESSION['user_name'] = $data['name'];
            $_SESSION['success'] = 'Профиль успешно обновлен';
            $_SESSION['section'] = 'general';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Ошибка при обновлении профиля';
            error_log('Profile update error: ' . $e->getMessage());
        }
        
        header('Location: /profile/settings#general');
        exit;
    }
    
    private function updateNotifications($userId) {
        $data = [
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'telegram_notifications' => isset($_POST['telegram_notifications']) ? 1 : 0,
            'telegram_chat_id' => $_POST['telegram_chat_id'] ?? null
        ];
        
        // Очищаем telegram_chat_id если пустой
        if (empty($data['telegram_chat_id'])) {
            $data['telegram_chat_id'] = null;
        }
        
        try {
            $this->user->updateSettings($userId, $data);
            $_SESSION['success'] = 'Настройки уведомлений сохранены';
            $_SESSION['section'] = 'notifications';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Ошибка при сохранении настроек';
            error_log('Notification settings error: ' . $e->getMessage());
        }
        
        header('Location: /profile/settings#notifications');
        exit;
    }
    
    private function updateSecurity($userId) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['new_password_confirm'] ?? '';
        
        // Если пароли не заполнены, просто возвращаемся
        if (empty($currentPassword) && empty($newPassword)) {
            header('Location: /profile/settings#security');
            exit;
        }
        
        // Валидация
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors[] = 'Введите текущий пароль';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'Введите новый пароль';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'Пароль должен содержать минимум 8 символов';
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'Пароль должен содержать хотя бы одну заглавную букву';
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'Пароль должен содержать хотя бы одну цифру';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Пароли не совпадают';
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
            $user = $this->user->findById($userId);
            $departments = $this->department->getAll();
            require_once __DIR__ . '/../../views/profile/settings.php';
            return;
        }
        
        // Проверяем текущий пароль
        $user = $this->user->findById($userId);
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Неверный текущий пароль';
            $departments = $this->department->getAll();
            require_once __DIR__ . '/../../views/profile/settings.php';
            return;
        }
        
        // Обновляем пароль
        try {
            $this->user->updatePassword($userId, $newPassword);
            $_SESSION['success'] = 'Пароль успешно изменен';
            $_SESSION['section'] = 'security';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Ошибка при изменении пароля';
            error_log('Password update error: ' . $e->getMessage());
        }
        
        header('Location: /profile/settings#security');
        exit;
    }
    
    public function testNotification() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $user = $this->user->findById($userId);
        
        // Создаем тестовое уведомление
        $notificationModel = new \App\Models\Notification($this->db);
        $notificationId = $notificationModel->create([
            'user_id' => $userId,
            'type' => 'test',
            'title' => 'Тестовое уведомление',
            'message' => 'Это тестовое уведомление для проверки настроек',
            'task_id' => null
        ]);
        
        // Отправляем через сервисы
        $emailService = new \App\Services\EmailService($GLOBALS['mailConfig']);
        $telegramService = new \App\Services\TelegramService($GLOBALS['telegramConfig']['bot_token']);
        
        $success = true;
        $errors = [];
        
        // Email
        if ($user['email_notifications']) {
            $emailSent = $emailService->send(
                $user['email'],
                'Тестовое уведомление',
                'Это тестовое уведомление из системы управления задачами. Если вы его получили, значит email уведомления работают корректно!'
            );
            
            if (!$emailSent) {
                $errors[] = 'Ошибка отправки email';
                $success = false;
            }
        }
        
        // Telegram
        if ($user['telegram_notifications'] && $user['telegram_chat_id']) {
            $result = $telegramService->sendMessage(
                $user['telegram_chat_id'],
                "🔔 <b>Тестовое уведомление</b>\n\nЭто тестовое уведомление из системы управления задачами. Telegram уведомления работают корректно!"
            );
            
            if (!$result || !$result['ok']) {
                $errors[] = 'Ошибка отправки в Telegram';
                $success = false;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'error' => implode(', ', $errors)
        ]);
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile/settings');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $password = $_POST['password'] ?? '';
        
        // Проверяем пароль
        $user = $this->user->findById($userId);
        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Неверный пароль';
            header('Location: /profile/settings#advanced');
            exit;
        }
        
        // Удаляем пользователя
        try {
            $this->user->delete($userId);
            session_destroy();
            header('Location: /login');
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Ошибка при удалении аккаунта';
            header('Location: /profile/settings#advanced');
        }
    }
}