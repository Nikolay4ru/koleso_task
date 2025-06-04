<?php
namespace App\Controllers;

use App\Models\Notification;

class NotificationController {
    private $db;
    private $notification;
    
    public function __construct($db) {
        $this->db = $db;
        $this->notification = new Notification($db);
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Получаем все уведомления пользователя
        $notifications = $this->notification->getUserNotifications($userId, 100);
        
        // Группируем по датам
        $groupedNotifications = [];
        foreach ($notifications as $notification) {
            $date = date('Y-m-d', strtotime($notification['created_at']));
            $groupedNotifications[$date][] = $notification;
        }
        
        require_once __DIR__ . '/../../views/notifications/index.php';
    }
    
    public function getRecent() {
        $userId = $_SESSION['user_id'];
        
        // Получаем последние 10 уведомлений
        $notifications = $this->notification->getUserNotifications($userId, 10);
        
        header('Content-Type: application/json');
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $this->notification->getUnreadCount($userId)
        ]);
    }
    
    public function markAsRead($notificationId) {
        $userId = $_SESSION['user_id'];
        
        $this->notification->markAsRead($notificationId, $userId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    public function markAllAsRead() {
        $userId = $_SESSION['user_id'];
        
        $this->notification->markAllAsRead($userId);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Location: /notifications');
        }
    }
    
    public function delete($notificationId) {
        $userId = $_SESSION['user_id'];
        
        $this->notification->delete($notificationId, $userId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}