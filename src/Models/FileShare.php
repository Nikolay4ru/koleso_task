<?php
namespace App\Models;

use PDO;

class FileShare {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Создать короткую ссылку для файла
     */
    public function createShare($fileId, $createdBy, $options = []) {
        // Генерируем уникальный короткий код
        do {
            $shortCode = $this->generateShortCode();
            $exists = $this->findByCode($shortCode);
        } while ($exists);
        
        $sql = "INSERT INTO file_shares (file_id, short_code, created_by, title, description, expires_at, password, max_downloads, allow_preview) 
                VALUES (:file_id, :short_code, :created_by, :title, :description, :expires_at, :password, :max_downloads, :allow_preview)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':file_id' => $fileId,
            ':short_code' => $shortCode,
            ':created_by' => $createdBy,
            ':title' => $options['title'] ?? null,
            ':description' => $options['description'] ?? null,
            ':expires_at' => $options['expires_at'] ?? null,
            ':password' => $options['password'] ? password_hash($options['password'], PASSWORD_DEFAULT) : null,
            ':max_downloads' => $options['max_downloads'] ?? null,
            ':allow_preview' => $options['allow_preview'] ?? 1
        ]);
        
        return $shortCode;
    }
    
    /**
     * Найти ссылку по коду
     */
    public function findByCode($shortCode) {
        $sql = "SELECT fs.*, f.*, u.name as creator_name,
                       f.filename, f.original_name, f.mime_type, f.size, f.path, f.is_image, f.is_document,
                       f.thumbnail_path, f.document_preview, f.pages_count
                FROM file_shares fs
                JOIN files f ON fs.file_id = f.id
                JOIN users u ON fs.created_by = u.id
                WHERE fs.short_code = :short_code AND fs.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':short_code' => $shortCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить ссылки пользователя
     */
    public function getUserShares($userId, $limit = 50) {
        $sql = "SELECT fs.*, f.original_name, f.size, f.mime_type, f.is_document
                FROM file_shares fs
                JOIN files f ON fs.file_id = f.id
                WHERE fs.created_by = :user_id
                ORDER BY fs.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Проверить доступ к файлу
     */
    public function checkAccess($shortCode, $password = null) {
        $share = $this->findByCode($shortCode);
        
        if (!$share) {
            return ['success' => false, 'error' => 'Ссылка не найдена'];
        }
        
        // Проверка срока действия
        if ($share['expires_at'] && strtotime($share['expires_at']) < time()) {
            return ['success' => false, 'error' => 'Ссылка истекла'];
        }
        
        // Проверка лимита скачиваний
        if ($share['max_downloads'] && $share['download_count'] >= $share['max_downloads']) {
            return ['success' => false, 'error' => 'Превышен лимит скачиваний'];
        }
        
        // Проверка пароля
        if ($share['password'] && !password_verify($password ?: '', $share['password'])) {
            return ['success' => false, 'error' => 'Неверный пароль', 'requires_password' => true];
        }
        
        return ['success' => true, 'share' => $share];
    }
    
    /**
     * Логировать действие
     */
    public function logAccess($shareId, $action, $ipAddress = null, $userAgent = null) {
        try {
            // Сначала проверяем, существует ли запись в file_shares
            $checkSql = "SELECT id FROM file_shares WHERE id = :share_id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':share_id' => $shareId]);
            
            if (!$checkStmt->fetch()) {
                error_log("Share with ID $shareId not found for logging");
                return false;
            }
            
            $sql = "INSERT INTO file_share_logs (share_id, action, ip_address, user_agent) 
                    VALUES (:share_id, :action, :ip_address, :user_agent)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':share_id' => $shareId,
                ':action' => $action,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);
            
            // Увеличиваем счетчик скачиваний для действия download
            if ($result && $action === 'download') {
                $this->incrementDownloadCount($shareId);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Log access error: " . $e->getMessage());
            // Не прерываем выполнение, если логирование не удалось
            return false;
        }
    }
    
    /**
     * Увеличить счетчик скачиваний
     */
    private function incrementDownloadCount($shareId) {
        try {
            $sql = "UPDATE file_shares SET download_count = download_count + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $shareId]);
        } catch (\Exception $e) {
            error_log("Increment download count error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Деактивировать ссылку
     */
    public function deactivateShare($shortCode, $userId) {
        $sql = "UPDATE file_shares SET is_active = 0 WHERE short_code = :short_code AND created_by = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':short_code' => $shortCode,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Удалить ссылку
     */
    public function deleteShare($shortCode, $userId) {
        $sql = "DELETE FROM file_shares WHERE short_code = :short_code AND created_by = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':short_code' => $shortCode,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Генерировать короткий код
     */
    private function generateShortCode($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $shortCode = '';
        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $shortCode;
    }
    
    /**
     * Получить статистику ссылки
     */
    public function getShareStats($shortCode, $userId) {
        $sql = "SELECT 
                    fs.*,
                    f.original_name,
                    COUNT(fsl.id) as total_views,
                    COUNT(CASE WHEN fsl.action = 'download' THEN 1 END) as total_downloads,
                    COUNT(CASE WHEN fsl.action = 'preview' THEN 1 END) as total_previews,
                    MAX(fsl.created_at) as last_access
                FROM file_shares fs
                JOIN files f ON fs.file_id = f.id
                LEFT JOIN file_share_logs fsl ON fs.id = fsl.share_id
                WHERE fs.short_code = :short_code AND fs.created_by = :user_id
                GROUP BY fs.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':short_code' => $shortCode,
            ':user_id' => $userId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}