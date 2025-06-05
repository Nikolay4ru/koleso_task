<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Department;

class AuthController {
    private $db;
    private $user;
    
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            $user = $this->user->findByEmail($email);
            
            if ($user && password_verify($password, $user['password'])) {
                // Успешная авторизация
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header('Location: /tasks/kanban');
                exit;
            } else {
                $error = 'Неверный email или пароль';
            }
        }
        
        require_once __DIR__ . '/../../views/auth/login.php';
    }
    
    public function register() {
        // Проверяем токен приглашения
        $invitation = null;
        if (isset($_GET['token'])) {
            $invitationModel = new \App\Models\Invitation($this->db);
            $invitation = $invitationModel->findByToken($_GET['token']);
            
            if (!$invitation) {
                $error = 'Недействительная или истекшая ссылка приглашения';
            }
        }
        
        // Получаем список отделов для формы
        $departmentModel = new Department($this->db);
        $departments = $departmentModel->getAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Валидация данных
            $errors = [];
            
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $departmentId = $_POST['department_id'] ?? '';
            
            // Проверка имени
            if (empty($name)) {
                $errors[] = 'Имя обязательно для заполнения';
            } elseif (strlen($name) < 2) {
                $errors[] = 'Имя должно содержать минимум 2 символа';
            }
            
            // Проверка email
            if (empty($email)) {
                $errors[] = 'Email обязателен для заполнения';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Неверный формат email';
            } elseif ($this->user->exists($email)) {
                $errors[] = 'Пользователь с таким email уже существует';
            }
            
            // Проверка пароля
            if (empty($password)) {
                $errors[] = 'Пароль обязателен для заполнения';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Пароль должен содержать минимум 8 символов';
            } elseif ($password !== $passwordConfirm) {
                $errors[] = 'Пароли не совпадают';
            }
            
            // Если нет ошибок, создаем пользователя
            if (empty($errors)) {
                $data = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'department_id' => $departmentId // Может быть пустой строкой
                ];
                
                try {
                    $userId = $this->user->create($data);
                    
                    // Автоматическая авторизация после регистрации
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    header('Location: /tasks/kanban');
                    exit;
                } catch (\Exception $e) {
                    $error = 'Ошибка при создании пользователя. Попробуйте еще раз.';
                    error_log('Registration error: ' . $e->getMessage());
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
        
        require_once __DIR__ . '/../../views/auth/register.php';
    }
    
    public function logout() {
        // Очищаем сессию
        $_SESSION = [];
        
        // Удаляем cookie сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Уничтожаем сессию
        session_destroy();
        
        header('Location: /login');
        exit;
    }
    
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            
            if ($user = $this->user->findByEmail($email)) {
                // Здесь должна быть логика отправки email для сброса пароля
                $success = 'Инструкции по сбросу пароля отправлены на ваш email';
            } else {
                $error = 'Пользователь с таким email не найден';
            }
        }
        
        require_once __DIR__ . '/../../views/auth/forgot-password.php';
    }
}