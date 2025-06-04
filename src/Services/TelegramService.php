<?php
namespace App\Services;

class TelegramService {
    private $botToken;
    private $apiUrl;
    
    public function __construct($botToken) {
        $this->botToken = $botToken;
        $this->apiUrl = "https://api.telegram.org/bot{$botToken}/";
    }
    
    public function sendMessage($chatId, $text) {
        $url = $this->apiUrl . "sendMessage";
        
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
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    public function setWebhook($webhookUrl) {
        $url = $this->apiUrl . "setWebhook";
        
        $data = [
            'url' => $webhookUrl
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
}