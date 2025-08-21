<?php
// src/Controllers/SignalingController.php

namespace App\Controllers;

use App\Models\VideoConference;

class SignalingController {
    private $db;
    private $conferenceModel;
    
    public function __construct($db) {
        $this->db = $db;
        $this->conferenceModel = new VideoConference($db);
    }
    
    /**
     * Polling endpoint для получения сигналов
     */
    public function poll() {
        header('Content-Type: application/json');
        
        $conferenceId = $_GET['conference_id'] ?? null;
        $userId = $_SESSION['user_id'];
        $lastSignalId = $_GET['last_signal_id'] ?? 0;
        
        if (!$conferenceId) {
            echo json_encode(['error' => 'Conference ID required']);
            return;
        }
        
        // Получаем новые сигналы
        $signals = $this->getSignalsAfter($conferenceId, $userId, $lastSignalId);
        
        echo json_encode([
            'signals' => $signals,
            'last_signal_id' => empty($signals) ? $lastSignalId : end($signals)['id']
        ]);
    }
    
    /**
     * Отправка сигнала
     */
    public function send() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $conferenceId = $data['conference_id'] ?? null;
        $targetUserId = $data['target_user_id'] ?? null;
        $signalType = $data['type'] ?? null;
        $signalData = $data['data'] ?? null;
        
        if (!$conferenceId || !$signalType) {
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        // Сохраняем сигнал в БД
        $signalId = $this->saveSignal(
            $conferenceId,
            $_SESSION['user_id'],
            $targetUserId,
            $signalType,
            $signalData
        );
        
        echo json_encode([
            'success' => true,
            'signal_id' => $signalId
        ]);
    }
    
    /**
     * Получение участников конференции
     */
    public function participants() {
        header('Content-Type: application/json');
        
        $conferenceId = $_GET['conference_id'] ?? null;
        
        if (!$conferenceId) {
            echo json_encode(['error' => 'Conference ID required']);
            return;
        }
        
        $participants = $this->conferenceModel->getParticipants($conferenceId);
        
        // Добавляем онлайн статус (последняя активность < 10 секунд)
        foreach ($participants as &$participant) {
            $lastActive = $this->getLastActivity($conferenceId, $participant['user_id']);
            $participant['is_online'] = $lastActive && (time() - strtotime($lastActive)) < 10;
        }
        
        echo json_encode(['participants' => $participants]);
    }
    
    /**
     * Heartbeat - поддержание статуса онлайн
     */
    public function heartbeat() {
        header('Content-Type: application/json');
        
        $conferenceId = $_POST['conference_id'] ?? null;
        
        if (!$conferenceId) {
            echo json_encode(['error' => 'Conference ID required']);
            return;
        }
        
        // Обновляем время последней активности
        $this->updateLastActivity($conferenceId, $_SESSION['user_id']);
        
        echo json_encode(['success' => true]);
    }
    
    // === Вспомогательные методы ===
    
    private function saveSignal($conferenceId, $fromUserId, $targetUserId, $type, $data) {
        $sql = "INSERT INTO conference_signals 
                (conference_id, from_user_id, target_user_id, signal_type, signal_data, created_at) 
                VALUES 
                (:conference_id, :from_user_id, :target_user_id, :signal_type, :signal_data, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':from_user_id' => $fromUserId,
            ':target_user_id' => $targetUserId,
            ':signal_type' => $type,
            ':signal_data' => json_encode($data)
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function getSignalsAfter($conferenceId, $userId, $afterId) {
        $sql = "SELECT cs.*, u.name as from_user_name 
                FROM conference_signals cs
                LEFT JOIN users u ON cs.from_user_id = u.id
                WHERE cs.conference_id = :conference_id 
                AND cs.id > :after_id
                AND (cs.target_user_id = :user_id OR cs.target_user_id IS NULL)
                AND cs.from_user_id != :user_id2
                ORDER BY cs.id ASC
                LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':after_id' => $afterId,
            ':user_id' => $userId,
            ':user_id2' => $userId
        ]);
        
        $signals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Декодируем JSON данные
        foreach ($signals as &$signal) {
            $signal['signal_data'] = json_decode($signal['signal_data'], true);
        }
        
        return $signals;
    }
    
    private function updateLastActivity($conferenceId, $userId) {
        $sql = "UPDATE conference_participants 
                SET last_activity = NOW() 
                WHERE conference_id = :conference_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':user_id' => $userId
        ]);
    }
    
    private function getLastActivity($conferenceId, $userId) {
        $sql = "SELECT last_activity 
                FROM conference_participants 
                WHERE conference_id = :conference_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':user_id' => $userId
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['last_activity'] : null;
    }
}

