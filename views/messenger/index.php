<?php
// views/messenger/index.php
$pageTitle = 'Мессенджер';
$currentUser = $_SESSION['user_name'] ?? 'Пользователь';
$currentUserId = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Система управления задачами</title>
    
    <!-- Стили -->
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
            --info: #4299e1;
            --dark: #2d3748;
            --light: #f7fafc;
            --gray: #718096;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: var(--dark);
        }

        .messenger-wrapper {
            display: flex;
            height: 100vh;
            background: white;
        }

        /* Sidebar с чатами */
        .chats-sidebar {
            width: 380px;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            background: white;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .sidebar-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .sidebar-title h2 {
            font-size: 24px;
            font-weight: 600;
        }

        .new-chat-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 20px;
        }

        .new-chat-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            background: rgba(255, 255, 255, 0.3);
        }

        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }

        /* Tabs */
        .chat-tabs {
            display: flex;
            padding: 0 20px;
            background: white;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-btn:hover {
            color: var(--primary-dark);
        }

        /* Chat List */
        .chats-list {
            flex: 1;
            overflow-y: auto;
            background: white;
        }

        .chat-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
        }

        .chat-item:hover {
            background: #f8f9fa;
        }

        .chat-item.active {
            background: linear-gradient(to right, rgba(102, 126, 234, 0.1), transparent);
            border-left: 3px solid var(--primary);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
            position: relative;
            flex-shrink: 0;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .online-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid white;
            background: var(--secondary);
        }

        .online-indicator.away {
            background: var(--warning);
        }

        .online-indicator.busy {
            background: var(--danger);
        }

        .online-indicator.offline {
            background: var(--gray);
        }

        .chat-info {
            flex: 1;
            min-width: 0;
        }

        .chat-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .last-message {
            font-size: 13px;
            color: var(--gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
        }

        .typing-indicator {
            color: var(--primary);
            font-style: italic;
        }

        .chat-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }

        .message-time {
            font-size: 12px;
            color: var(--gray);
        }

        .unread-count {
            background: var(--primary);
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .no-chat-selected {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray);
        }

        .no-chat-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .no-chat-text {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .no-chat-hint {
            font-size: 14px;
            opacity: 0.7;
        }

        /* Chat Header */
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
        }

        .chat-header-info {
            display: flex;
            align-items: center;
        }

        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }

        .chat-header-details {
            display: flex;
            flex-direction: column;
        }

        .chat-header-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 2px;
        }

        .chat-header-status {
            font-size: 13px;
            color: var(--gray);
        }

        .chat-header-status.online {
            color: var(--secondary);
        }

        .chat-header-actions {
            display: flex;
            gap: 10px;
        }

        .header-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #f0f0f0;
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 18px;
        }

        .header-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        /* Messages Container */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        .date-divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .date-divider span {
            background: #f8f9fa;
            padding: 0 15px;
            color: var(--gray);
            font-size: 12px;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .date-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border);
        }

        .message {
            display: flex;
            margin-bottom: 15px;
            animation: messageSlide 0.3s ease;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin: 0 10px;
            flex-shrink: 0;
        }

        .message-content {
            max-width: 70%;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .message-bubble {
            padding: 10px 15px;
            border-radius: 18px;
            background: white;
            box-shadow: var(--shadow);
            position: relative;
            word-wrap: break-word;
        }

        .message.own .message-bubble {
            background: var(--primary);
            color: white;
        }

        .message-text {
            font-size: 14px;
            line-height: 1.5;
        }

        .message-text img {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 5px;
        }

        .message-file {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            margin-top: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .message-file:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .message.own .message-file {
            background: rgba(255, 255, 255, 0.2);
        }

        .message.own .message-file:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .file-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            font-size: 13px;
        }

        .file-size {
            font-size: 11px;
            opacity: 0.7;
        }

        .message-info {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: var(--gray);
            padding: 0 5px;
        }

        .message.own .message-info {
            flex-direction: row-reverse;
            color: rgba(255, 255, 255, 0.7);
        }

        .message-time {
            font-size: 11px;
        }

        .message-status {
            display: flex;
            align-items: center;
        }

        .check-icon {
            width: 14px;
            height: 14px;
        }

        .message-reactions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
            flex-wrap: wrap;
        }

        .reaction-badge {
            display: flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .reaction-badge:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow);
        }

        .reaction-badge.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .reaction-count {
            font-size: 12px;
            font-weight: 500;
        }

        .add-reaction-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px dashed var(--border);
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s;
        }

        .add-reaction-btn:hover {
            border-style: solid;
            background: var(--light);
        }

        /* Reply Message */
        .reply-to {
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.05);
            border-left: 3px solid var(--primary);
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .reply-to-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 2px;
        }

        .reply-to-text {
            color: var(--gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Input Area */
        .input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid var(--border);
        }

        .reply-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: var(--light);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .reply-preview-content {
            flex: 1;
        }

        .reply-preview-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 2px;
        }

        .reply-preview-text {
            font-size: 13px;
            color: var(--gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .reply-close-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            background: var(--gray);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s;
        }

        .reply-close-btn:hover {
            background: var(--danger);
            transform: scale(1.1);
        }

        .input-container {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .input-wrapper {
            flex: 1;
            background: var(--light);
            border-radius: 25px;
            padding: 10px 15px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .input-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .input-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: none;
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 18px;
        }

        .input-btn:hover {
            background: white;
            color: var(--primary);
            transform: scale(1.1);
        }

        .message-input {
            flex: 1;
            border: none;
            background: none;
            outline: none;
            font-size: 14px;
            line-height: 1.5;
            max-height: 120px;
            min-height: 20px;
            resize: none;
            font-family: inherit;
        }

        .send-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 20px;
        }

        .send-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .send-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        /* Emoji Picker */
        .emoji-picker {
            position: absolute;
            bottom: 80px;
            left: 20px;
            width: 350px;
            height: 400px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            display: none;
            flex-direction: column;
            z-index: 1000;
        }

        .emoji-picker.show {
            display: flex;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .emoji-header {
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }

        .emoji-search {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 14px;
            outline: none;
        }

        .emoji-categories {
            display: flex;
            padding: 10px;
            gap: 5px;
            border-bottom: 1px solid var(--border);
        }

        .emoji-category {
            flex: 1;
            padding: 8px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 8px;
            font-size: 18px;
            transition: all 0.3s;
        }

        .emoji-category:hover {
            background: var(--light);
        }

        .emoji-category.active {
            background: var(--primary);
        }

        .emoji-grid {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 5px;
        }

        .emoji-item {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 20px;
        }

        .emoji-item:hover {
            background: var(--light);
            transform: scale(1.2);
        }

        /* Context Menu */
        .context-menu {
            position: fixed;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            padding: 5px;
            display: none;
            z-index: 1000;
            min-width: 180px;
        }

        .context-menu.show {
            display: block;
        }

        .context-menu-item {
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .context-menu-item:hover {
            background: var(--light);
        }

        .context-menu-item.danger {
            color: var(--danger);
        }

        .context-menu-separator {
            height: 1px;
            background: var(--border);
            margin: 5px 10px;
        }

        /* Call Modal */
        .call-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .call-modal.show {
            display: flex;
        }

        .call-container {
            background: #1a1a2e;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            color: white;
            min-width: 400px;
        }

        .call-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            70% {
                box-shadow: 0 0 0 30px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }

        .call-info h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .call-status {
            color: #aaa;
            margin-bottom: 30px;
        }

        .call-timer {
            font-size: 18px;
            color: var(--secondary);
            margin-bottom: 30px;
        }

        .call-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .call-control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: white;
            font-size: 24px;
        }

        .call-control-btn.mute {
            background: #666;
        }

        .call-control-btn.mute.active {
            background: var(--danger);
        }

        .call-control-btn.video-toggle {
            background: #666;
        }

        .call-control-btn.video-toggle.active {
            background: var(--danger);
        }

        .call-control-btn.end {
            background: var(--danger);
        }

        .call-control-btn:hover {
            transform: scale(1.1);
        }

        /* Video Call */
        .video-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .remote-video {
            width: 100%;
            border-radius: 15px;
            background: #000;
        }

        .local-video {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 150px;
            height: 100px;
            border-radius: 10px;
            background: #333;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* User List Modal */
        .users-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1500;
        }

        .users-modal.show {
            display: flex;
        }

        .users-container {
            background: white;
            border-radius: 15px;
            width: 400px;
            max-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .users-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .users-header h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .users-search {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 14px;
            outline: none;
        }

        .users-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-item:hover {
            background: var(--light);
        }

        .user-item-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 12px;
        }

        .user-item-info {
            flex: 1;
        }

        .user-item-name {
            font-weight: 500;
            margin-bottom: 2px;
        }

        .user-item-status {
            font-size: 12px;
            color: var(--gray);
        }

        /* Typing Indicator */
        .typing-dots {
            display: inline-flex;
            gap: 3px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            animation: typing-bounce 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing-bounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chats-sidebar {
                width: 100%;
                position: absolute;
                z-index: 100;
                transition: transform 0.3s;
            }

            .chats-sidebar.hidden {
                transform: translateX(-100%);
            }

            .chat-area {
                width: 100%;
            }

            .message-content {
                max-width: 85%;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 3000;
        }

        .toast {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 10px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 250px;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast.success {
            border-left: 4px solid var(--secondary);
        }

        .toast.error {
            border-left: 4px solid var(--danger);
        }

        .toast.info {
            border-left: 4px solid var(--info);
        }

        .toast-icon {
            font-size: 20px;
        }

        .toast-message {
            flex: 1;
            font-size: 14px;
        }

        .toast-close {
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        .toast-close:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="messenger-wrapper">
        <!-- Sidebar с чатами -->
        <div class="chats-sidebar" id="chatsSidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <h2>Чаты</h2>
                    <button class="new-chat-btn" onclick="showUsersModal()" title="Новый чат">
                        ➕
                    </button>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Поиск..." id="searchInput" onkeyup="searchChats(this.value)">
                    <span class="search-icon">🔍</span>
                </div>
            </div>
            
            <div class="chat-tabs">
                <button class="tab-btn active" onclick="switchTab('all')">Все</button>
                <button class="tab-btn" onclick="switchTab('unread')">Непрочитанные</button>
                <button class="tab-btn" onclick="switchTab('groups')">Группы</button>
            </div>
            
            <div class="chats-list" id="chatsList">
                <?php if (!empty($chats)): ?>
                    <?php foreach ($chats as $chat): ?>
                        <div class="chat-item <?= isset($_GET['chat']) && $_GET['chat'] == $chat['id'] ? 'active' : '' ?>" 
                             onclick="openChat(<?= $chat['id'] ?>)" 
                             data-chat-id="<?= $chat['id'] ?>">
                            <div class="user-avatar">
                                <?php if (isset($chat['avatar']) && $chat['avatar']): ?>
                                    <img src="<?= htmlspecialchars($chat['avatar']) ?>" alt="">
                                <?php else: ?>
                                    <?= strtoupper(substr($chat['name'] ?? 'U', 0, 1)) ?>
                                <?php endif; ?>
                                <?php if (isset($chat['opponent_status'])): ?>
                                    <span class="online-indicator <?= $chat['opponent_status'] ?>"></span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-info">
                                <div class="chat-name"><?= htmlspecialchars($chat['name'] ?? 'Без имени') ?></div>
                                <div class="last-message">
                                    <?php if (isset($chat['is_typing']) && $chat['is_typing']): ?>
                                        <span class="typing-indicator">печатает...</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($chat['last_message'] ?? 'Нет сообщений') ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="chat-meta">
                                <div class="message-time"><?= isset($chat['last_message_time']) ? date('H:i', strtotime($chat['last_message_time'])) : '' ?></div>
                                <?php if (isset($chat['unread_count']) && $chat['unread_count'] > 0): ?>
                                    <div class="unread-count"><?= $chat['unread_count'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #999;">
                        Нет доступных чатов
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="no-chat-selected" id="noChatSelected">
                <div class="no-chat-icon">💬</div>
                <div class="no-chat-text">Выберите чат</div>
                <div class="no-chat-hint">или начните новый разговор</div>
            </div>
            
            <div id="chatContent" style="display: none; height: 100%; display: flex; flex-direction: column;">
                <!-- Chat Header -->
                <div class="chat-header" id="chatHeader">
                    <div class="chat-header-info">
                        <div class="chat-header-avatar" id="chatAvatar">U</div>
                        <div class="chat-header-details">
                            <div class="chat-header-name" id="chatName">Загрузка...</div>
                            <div class="chat-header-status" id="chatStatus">офлайн</div>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button class="header-btn" onclick="initiateCall('audio')" title="Аудио звонок">
                            📞
                        </button>
                        <button class="header-btn" onclick="initiateCall('video')" title="Видео звонок">
                            📹
                        </button>
                        <button class="header-btn" onclick="showChatInfo()" title="Информация">
                            ℹ️
                        </button>
                    </div>
                </div>
                
                <!-- Messages Container -->
                <div class="messages-container" id="messagesContainer">
                    <!-- Сообщения будут загружены сюда -->
                </div>
                
                <!-- Input Area -->
                <div class="input-area">
                    <div class="reply-preview" id="replyPreview" style="display: none;">
                        <div class="reply-preview-content">
                            <div class="reply-preview-name" id="replyName">Имя</div>
                            <div class="reply-preview-text" id="replyText">Текст сообщения</div>
                        </div>
                        <button class="reply-close-btn" onclick="cancelReply()">✕</button>
                    </div>
                    
                    <div class="input-container">
                        <div class="input-wrapper">
                            <div class="input-actions">
                                <button class="input-btn" onclick="toggleEmojiPicker()" title="Эмодзи">
                                    😊
                                </button>
                                <button class="input-btn" onclick="attachFile()" title="Прикрепить файл">
                                    📎
                                </button>
                                <button class="input-btn" onclick="sendVoiceMessage()" title="Голосовое сообщение">
                                    🎤
                                </button>
                            </div>
                            <textarea class="message-input" 
                                      id="messageInput" 
                                      placeholder="Напишите сообщение..." 
                                      rows="1"
                                      onkeypress="handleKeyPress(event)"
                                      oninput="handleTyping()"></textarea>
                        </div>
                        <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                            ➤
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Emoji Picker -->
    <div class="emoji-picker" id="emojiPicker">
        <div class="emoji-header">
            <input type="text" class="emoji-search" placeholder="Поиск эмодзи..." onkeyup="searchEmoji(this.value)">
        </div>
        <div class="emoji-categories">
            <button class="emoji-category active" onclick="loadEmojiCategory('smileys')" title="Смайлы">😊</button>
            <button class="emoji-category" onclick="loadEmojiCategory('animals')" title="Животные">🐾</button>
            <button class="emoji-category" onclick="loadEmojiCategory('food')" title="Еда">🍕</button>
            <button class="emoji-category" onclick="loadEmojiCategory('activities')" title="Активности">⚽</button>
            <button class="emoji-category" onclick="loadEmojiCategory('travel')" title="Путешествия">✈️</button>
            <button class="emoji-category" onclick="loadEmojiCategory('objects')" title="Объекты">💡</button>
            <button class="emoji-category" onclick="loadEmojiCategory('symbols')" title="Символы">❤️</button>
        </div>
        <div class="emoji-grid" id="emojiGrid">
            <!-- Эмодзи будут загружены сюда -->
        </div>
    </div>
    
    <!-- Context Menu -->
    <div class="context-menu" id="contextMenu">
        <div class="context-menu-item" onclick="replyToMessage()">
            ↩️ Ответить
        </div>
        <div class="context-menu-item" onclick="editMessage()">
            ✏️ Редактировать
        </div>
        <div class="context-menu-item" onclick="copyMessage()">
            📋 Копировать
        </div>
        <div class="context-menu-item" onclick="forwardMessage()">
            ↗️ Переслать
        </div>
        <div class="context-menu-separator"></div>
        <div class="context-menu-item" onclick="addReaction()">
            😊 Реакция
        </div>
        <div class="context-menu-separator"></div>
        <div class="context-menu-item danger" onclick="deleteMessage()">
            🗑️ Удалить
        </div>
    </div>
    
    <!-- Call Modal -->
    <div class="call-modal" id="callModal">
        <div class="call-container">
            <div id="videoContainer" class="video-container" style="display: none;">
                <video id="remoteVideo" class="remote-video" autoplay></video>
                <video id="localVideo" class="local-video" autoplay muted></video>
            </div>
            <div id="audioCallInfo">
                <div class="call-avatar" id="callAvatar">U</div>
                <div class="call-info">
                    <h3 id="callName">Пользователь</h3>
                    <div class="call-status" id="callStatus">Звоним...</div>
                    <div class="call-timer" id="callTimer" style="display: none;">00:00</div>
                </div>
            </div>
            <div class="call-controls">
                <button class="call-control-btn mute" id="muteBtn" onclick="toggleMute()" title="Микрофон">
                    🎤
                </button>
                <button class="call-control-btn video-toggle" id="videoBtn" onclick="toggleVideo()" title="Камера" style="display: none;">
                    📹
                </button>
                <button class="call-control-btn end" onclick="endCall()" title="Завершить">
                    ☎️
                </button>
            </div>
        </div>
    </div>
    
    <!-- Users Modal -->
    <div class="users-modal" id="usersModal">
        <div class="users-container">
            <div class="users-header">
                <h3>Выберите пользователя</h3>
                <input type="text" class="users-search" placeholder="Поиск пользователей..." onkeyup="searchUsers(this.value)">
            </div>
            <div class="users-list" id="usersList">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php if ($user['id'] != $currentUserId): ?>
                            <div class="user-item" onclick="startChatWithUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                                <div class="user-item-avatar">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <div class="user-item-info">
                                    <div class="user-item-name"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="user-item-status"><?= htmlspecialchars($user['department_name'] ?? 'Без отдела') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Hidden file input -->
    <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect(event)">
    
    <!-- JavaScript -->
    <script>
        // Глобальные переменные
        let currentChatId = null;
        let currentUserId = <?= $currentUserId ?>;
        let selectedMessageId = null;
        let replyToMessageId = null;
        let isTyping = false;
        let typingTimeout = null;
        let pollInterval = null;
        let lastMessageId = 0;
        
        // Emoji data
        const emojis = {
            smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐'],
            animals: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜', '🦟', '🦗', '🕷️', '🕸️', '🦂', '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈'],
            food: ['🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶️', '🌽', '🥕', '🧄', '🧅', '🥔', '🍠', '🥐', '🥯', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳', '🧈', '🥞', '🧇', '🥓', '🥩', '🍗', '🍖', '🌭', '🍔', '🍟', '🍕', '🥪', '🥙', '🧆', '🌮', '🌯', '🥗', '🥘', '🥫', '🍝', '🍜', '🍲', '🍛'],
            activities: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤼', '🤸', '⛹️', '🤺', '🤾', '🏌️', '🏇', '🧘', '🏄', '🏊', '🤽', '🚣', '🧗', '🚵', '🚴', '🏆', '🥇', '🥈', '🥉', '🏅', '🎖️', '🏵️', '🎗️', '🎫', '🎟️'],
            travel: ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🚚', '🚛', '🚜', '🦯', '🦽', '🦼', '🛴', '🚲', '🛵', '🏍️', '🛺', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟', '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈️', '🛫', '🛬', '🛩️', '💺', '🛰️', '🚀', '🛸', '🚁', '🛶', '⛵', '🚤', '🛥️', '🛳️', '⛴️', '🚢', '⚓', '⛽', '🚧', '🚦'],
            objects: ['⌚', '📱', '📲', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '🕹️', '🗜️', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️', '🎛️', '🧭', '⏱️', '⏲️', '⏰', '🕰️', '⌛', '⏳', '📡', '🔋', '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴', '💶', '💷', '💰', '💳', '💎', '⚖️', '🧰', '🔧', '🔨'],
            symbols: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲']
        };
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Загружаем эмодзи
            loadEmojiCategory('smileys');
            
            // Проверяем, есть ли выбранный чат в URL
            const urlParams = new URLSearchParams(window.location.search);
            const chatId = urlParams.get('chat');
            if (chatId) {
                openChat(chatId);
            }
            
            // Начинаем опрос новых сообщений
            startPolling();
            
            // Обработка клавиш
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeAllModals();
                }
            });
            
            // Клик вне модальных окон
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('users-modal')) {
                    closeUsersModal();
                }
                if (!e.target.closest('.emoji-picker') && !e.target.closest('.input-btn')) {
                    closeEmojiPicker();
                }
                if (!e.target.closest('.context-menu')) {
                    closeContextMenu();
                }
            });
        });
        
        // Функции работы с чатами
        function openChat(chatId) {
            currentChatId = chatId;
            
            // Обновляем UI
            document.getElementById('noChatSelected').style.display = 'none';
            document.getElementById('chatContent').style.display = 'flex';
            
            // Помечаем чат как активный
            document.querySelectorAll('.chat-item').forEach(item => {
                item.classList.toggle('active', item.dataset.chatId == chatId);
            });
            
            // Загружаем сообщения
            loadMessages(chatId);
            
            // Обновляем URL
            window.history.pushState({}, '', '/messenger?chat=' + chatId);
        }
        
        function loadMessages(chatId) {
            fetch('/messenger/chat/open', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'chat_id=' + chatId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages);
                    updateChatHeader(data.participants);
                    lastMessageId = data.messages.length > 0 ? 
                        Math.max(...data.messages.map(m => m.id)) : 0;
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                showToast('Ошибка загрузки сообщений', 'error');
            });
        }
        
        function renderMessages(messages) {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = '';
            
            let lastDate = null;
            
            messages.forEach(msg => {
                // Добавляем разделитель дат
                const msgDate = new Date(msg.created_at).toLocaleDateString('ru-RU');
                if (msgDate !== lastDate) {
                    const divider = document.createElement('div');
                    divider.className = 'date-divider';
                    divider.innerHTML = `<span>${msgDate}</span>`;
                    container.appendChild(divider);
                    lastDate = msgDate;
                }
                
                // Создаем элемент сообщения
                const messageEl = createMessageElement(msg);
                container.appendChild(messageEl);
            });
            
            // Прокручиваем вниз
            container.scrollTop = container.scrollHeight;
        }
        
        function createMessageElement(msg) {
            const isOwn = msg.user_id == currentUserId;
            const messageEl = document.createElement('div');
            messageEl.className = `message ${isOwn ? 'own' : ''}`;
            messageEl.dataset.messageId = msg.id;
            
            let content = '';
            
            // Аватар
            if (!isOwn) {
                content += `
                    <div class="message-avatar">
                        ${msg.user_avatar ? 
                            `<img src="${msg.user_avatar}" alt="">` : 
                            msg.user_name.substring(0, 1).toUpperCase()}
                    </div>
                `;
            }
            
            content += '<div class="message-content">';
            
            // Ответ на сообщение
            if (msg.parent_message_id) {
                content += `
                    <div class="reply-to">
                        <div class="reply-to-name">${msg.parent_user_name || 'Пользователь'}</div>
                        <div class="reply-to-text">${msg.parent_content || 'Сообщение удалено'}</div>
                    </div>
                `;
            }
            
            content += `<div class="message-bubble" oncontextmenu="showContextMenu(event, ${msg.id}, ${msg.user_id})">`;
            
            // Контент сообщения
            if (msg.type === 'text') {
                content += `<div class="message-text">${escapeHtml(msg.content)}</div>`;
            } else if (msg.type === 'image') {
                content += `
                    <div class="message-text">
                        <img src="${msg.file_path}" alt="${msg.file_name}" style="max-width: 300px; cursor: pointer;" onclick="viewImage('${msg.file_path}')">
                    </div>
                `;
            } else if (msg.type === 'file') {
                content += `
                    <div class="message-file" onclick="downloadFile('${msg.file_path}', '${msg.file_name}')">
                        <div class="file-icon">📄</div>
                        <div class="file-details">
                            <div class="file-name">${msg.file_name}</div>
                            <div class="file-size">${formatFileSize(msg.file_size)}</div>
                        </div>
                    </div>
                `;
            }
            
            content += '</div>';
            
            // Реакции
            if (msg.reactions) {
                content += renderReactions(msg.reactions, msg.id);
            }
            
            // Информация о сообщении
            content += `
                <div class="message-info">
                    <span class="message-time">${formatTime(msg.created_at)}</span>
                    ${isOwn ? `
                        <span class="message-status">
                            ${msg.is_read ? '✓✓' : '✓'}
                        </span>
                    ` : ''}
                </div>
            `;
            
            content += '</div>'; // message-content
            
            messageEl.innerHTML = content;
            return messageEl;
        }
        
        function renderReactions(reactionsString, messageId) {
            if (!reactionsString) return '';
            
            let html = '<div class="message-reactions">';
            const reactions = reactionsString.split(',');
            
            reactions.forEach(reaction => {
                const [emoji, count] = reaction.split(':');
                html += `
                    <span class="reaction-badge" onclick="toggleReaction(${messageId}, '${emoji}')">
                        ${emoji} <span class="reaction-count">${count}</span>
                    </span>
                `;
            });
            
            html += `
                <button class="add-reaction-btn" onclick="showEmojiForReaction(${messageId})">
                    +
                </button>
            `;
            html += '</div>';
            
            return html;
        }
        
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const content = input.value.trim();
            
            if (!content || !currentChatId) return;
            
            const formData = new FormData();
            formData.append('chat_id', currentChatId);
            formData.append('content', content);
            
            if (replyToMessageId) {
                formData.append('parent_message_id', replyToMessageId);
            }
            
            fetch('/messenger/message/send', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Добавляем сообщение в UI
                    const container = document.getElementById('messagesContainer');
                    const messageEl = createMessageElement(data.message);
                    container.appendChild(messageEl);
                    container.scrollTop = container.scrollHeight;
                    
                    // Очищаем поле ввода
                    input.value = '';
                    input.style.height = 'auto';
                    
                    // Сбрасываем ответ
                    cancelReply();
                    
                    // Обновляем последнее сообщение
                    lastMessageId = data.message.id;
                } else {
                    showToast('Ошибка отправки сообщения', 'error');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showToast('Ошибка отправки сообщения', 'error');
            });
        }
        
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        function handleTyping() {
            const input = document.getElementById('messageInput');
            
            // Auto-resize
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
            
            // Typing indicator (можно отправить на сервер)
            if (!isTyping) {
                isTyping = true;
                // sendTypingStatus(true);
            }
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                // sendTypingStatus(false);
            }, 1000);
        }
        
        // Функции для эмодзи
        function toggleEmojiPicker() {
            const picker = document.getElementById('emojiPicker');
            picker.classList.toggle('show');
        }
        
        function closeEmojiPicker() {
            document.getElementById('emojiPicker').classList.remove('show');
        }
        
        function loadEmojiCategory(category) {
            const grid = document.getElementById('emojiGrid');
            grid.innerHTML = '';
            
            // Обновляем активную категорию
            document.querySelectorAll('.emoji-category').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Загружаем эмодзи
            const categoryEmojis = emojis[category] || [];
            categoryEmojis.forEach(emoji => {
                const item = document.createElement('div');
                item.className = 'emoji-item';
                item.textContent = emoji;
                item.onclick = () => insertEmoji(emoji);
                grid.appendChild(item);
            });
        }
        
        function insertEmoji(emoji) {
            const input = document.getElementById('messageInput');
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const text = input.value;
            
            input.value = text.substring(0, start) + emoji + text.substring(end);
            input.selectionStart = input.selectionEnd = start + emoji.length;
            input.focus();
            
            closeEmojiPicker();
            handleTyping();
        }
        
        function searchEmoji(query) {
            // Реализация поиска эмодзи
            const grid = document.getElementById('emojiGrid');
            grid.innerHTML = '';
            
            if (!query) {
                loadEmojiCategory('smileys');
                return;
            }
            
            // Поиск по всем категориям
            Object.values(emojis).flat().forEach(emoji => {
                // Простой поиск (можно улучшить)
                if (emoji.includes(query)) {
                    const item = document.createElement('div');
                    item.className = 'emoji-item';
                    item.textContent = emoji;
                    item.onclick = () => insertEmoji(emoji);
                    grid.appendChild(item);
                }
            });
        }
        
        // Контекстное меню
        function showContextMenu(event, messageId, userId) {
            event.preventDefault();
            selectedMessageId = messageId;
            
            const menu = document.getElementById('contextMenu');
            menu.style.left = event.pageX + 'px';
            menu.style.top = event.pageY + 'px';
            menu.classList.add('show');
            
            // Показываем/скрываем пункты в зависимости от автора
            const editItem = menu.querySelector('[onclick="editMessage()"]');
            const deleteItem = menu.querySelector('[onclick="deleteMessage()"]');
            
            if (userId == currentUserId) {
                editItem.style.display = 'flex';
                deleteItem.style.display = 'flex';
            } else {
                editItem.style.display = 'none';
                deleteItem.style.display = 'none';
            }
        }
        
        function closeContextMenu() {
            document.getElementById('contextMenu').classList.remove('show');
        }
        
        function replyToMessage() {
            const message = document.querySelector(`[data-message-id="${selectedMessageId}"]`);
            const messageText = message.querySelector('.message-text').textContent;
            const isOwn = message.classList.contains('own');
            
            replyToMessageId = selectedMessageId;
            
            // Показываем превью ответа
            document.getElementById('replyPreview').style.display = 'block';
            document.getElementById('replyName').textContent = isOwn ? 'Вы' : 'Пользователь';
            document.getElementById('replyText').textContent = messageText.substring(0, 100);
            
            // Фокус на поле ввода
            document.getElementById('messageInput').focus();
            
            closeContextMenu();
        }
        
        function cancelReply() {
            replyToMessageId = null;
            document.getElementById('replyPreview').style.display = 'none';
        }
        
        function editMessage() {
            const message = document.querySelector(`[data-message-id="${selectedMessageId}"]`);
            const messageText = message.querySelector('.message-text').textContent;
            
            const newText = prompt('Редактировать сообщение:', messageText);
            if (newText && newText !== messageText) {
                fetch('/messenger/message/edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message_id=${selectedMessageId}&content=${encodeURIComponent(newText)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        message.querySelector('.message-text').textContent = newText;
                        showToast('Сообщение изменено', 'success');
                    }
                });
            }
            
            closeContextMenu();
        }
        
        function copyMessage() {
            const message = document.querySelector(`[data-message-id="${selectedMessageId}"]`);
            const messageText = message.querySelector('.message-text').textContent;
            
            navigator.clipboard.writeText(messageText).then(() => {
                showToast('Сообщение скопировано', 'success');
            });
            
            closeContextMenu();
        }
        
        function forwardMessage() {
            // Реализация пересылки
            showToast('Функция в разработке', 'info');
            closeContextMenu();
        }
        
        function deleteMessage() {
            if (confirm('Удалить сообщение?')) {
                fetch('/messenger/message/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message_id=${selectedMessageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = document.querySelector(`[data-message-id="${selectedMessageId}"]`);
                        message.remove();
                        showToast('Сообщение удалено', 'success');
                    }
                });
            }
            
            closeContextMenu();
        }
        
        function addReaction() {
            showEmojiForReaction(selectedMessageId);
            closeContextMenu();
        }
        
        function showEmojiForReaction(messageId) {
            selectedMessageId = messageId;
            toggleEmojiPicker();
            
            // Временно меняем обработчик для эмодзи
            document.querySelectorAll('.emoji-item').forEach(item => {
                item.onclick = () => {
                    toggleReaction(messageId, item.textContent);
                    closeEmojiPicker();
                };
            });
        }
        
        function toggleReaction(messageId, emoji) {
            fetch('/messenger/reaction/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message_id=${messageId}&emoji=${encodeURIComponent(emoji)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем реакции (можно перезагрузить сообщение)
                    loadMessages(currentChatId);
                }
            });
        }
        
        // Звонки
        let localStream = null;
        let remoteStream = null;
        let peerConnection = null;
        let callTimer = null;
        let callStartTime = null;
        
        async function initiateCall(type) {
            if (!currentChatId) {
                showToast('Выберите чат для звонка', 'error');
                return;
            }
            
            const modal = document.getElementById('callModal');
            const videoContainer = document.getElementById('videoContainer');
            const audioInfo = document.getElementById('audioCallInfo');
            const videoBtn = document.getElementById('videoBtn');
            
            // Настройка UI
            if (type === 'video') {
                videoContainer.style.display = 'block';
                audioInfo.style.display = 'none';
                videoBtn.style.display = 'flex';
            } else {
                videoContainer.style.display = 'none';
                audioInfo.style.display = 'block';
                videoBtn.style.display = 'none';
            }
            
            modal.classList.add('show');
            
            try {
                // Получаем медиа-поток
                const constraints = type === 'video' 
                    ? { video: true, audio: true }
                    : { audio: true };
                    
                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                
                if (type === 'video') {
                    document.getElementById('localVideo').srcObject = localStream;
                }
                
                // Отправляем запрос на сервер
                fetch('/messenger/call/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `chat_id=${currentChatId}&type=${type}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Начинаем WebRTC соединение
                        setupWebRTC(data.room_id);
                        
                        // Запускаем таймер
                        startCallTimer();
                    }
                });
                
            } catch (error) {
                console.error('Error accessing media devices:', error);
                showToast('Не удалось получить доступ к камере/микрофону', 'error');
                modal.classList.remove('show');
            }
        }
        
        function setupWebRTC(roomId) {
            // Здесь должна быть реализация WebRTC
            // Используется для реального peer-to-peer соединения
            console.log('Setting up WebRTC for room:', roomId);
        }
        
        function startCallTimer() {
            callStartTime = Date.now();
            document.getElementById('callTimer').style.display = 'block';
            document.getElementById('callStatus').textContent = 'Подключено';
            
            callTimer = setInterval(() => {
                const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                document.getElementById('callTimer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }
        
        function toggleMute() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    const btn = document.getElementById('muteBtn');
                    btn.classList.toggle('active');
                    btn.textContent = audioTrack.enabled ? '🎤' : '🔇';
                }
            }
        }
        
        function toggleVideo() {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    const btn = document.getElementById('videoBtn');
                    btn.classList.toggle('active');
                    btn.textContent = videoTrack.enabled ? '📹' : '📵';
                }
            }
        }
        
        function endCall() {
            // Останавливаем медиа-потоки
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            
            // Очищаем таймер
            if (callTimer) {
                clearInterval(callTimer);
                callTimer = null;
            }
            
            // Закрываем соединение
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }
            
            // Скрываем модальное окно
            document.getElementById('callModal').classList.remove('show');
            document.getElementById('callTimer').style.display = 'none';
        }
        
        // Работа с файлами
        function attachFile() {
            document.getElementById('fileInput').click();
        }
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (file.size > 10 * 1024 * 1024) {
                showToast('Файл слишком большой. Максимум 10 МБ', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('chat_id', currentChatId);
            formData.append('file', file);
            formData.append('type', getFileType(file));
            
            // Показываем загрузку
            showToast('Загрузка файла...', 'info');
            
            fetch('/messenger/message/send', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Добавляем сообщение с файлом
                    const container = document.getElementById('messagesContainer');
                    const messageEl = createMessageElement(data.message);
                    container.appendChild(messageEl);
                    container.scrollTop = container.scrollHeight;
                    
                    showToast('Файл отправлен', 'success');
                } else {
                    showToast('Ошибка отправки файла', 'error');
                }
            })
            .catch(error => {
                console.error('Error uploading file:', error);
                showToast('Ошибка загрузки файла', 'error');
            });
            
            // Очищаем input
            event.target.value = '';
        }
        
        function getFileType(file) {
            const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const videoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            const audioTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg'];
            
            if (imageTypes.includes(file.type)) return 'image';
            if (videoTypes.includes(file.type)) return 'video';
            if (audioTypes.includes(file.type)) return 'audio';
            return 'file';
        }
        
        function viewImage(src) {
            window.open(src, '_blank');
        }
        
        function downloadFile(path, name) {
            const a = document.createElement('a');
            a.href = path;
            a.download = name;
            a.click();
        }
        
        function sendVoiceMessage() {
            showToast('Функция в разработке', 'info');
        }
        
        // Управление модальными окнами
        function showUsersModal() {
            document.getElementById('usersModal').classList.add('show');
        }
        
        function closeUsersModal() {
            document.getElementById('usersModal').classList.remove('show');
        }
        
        function startChatWithUser(userId, userName) {
            fetch('/messenger/chat/open', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeUsersModal();
                    openChat(data.chat_id);
                    location.reload(); // Перезагружаем для обновления списка чатов
                }
            });
        }
        
        function searchUsers(query) {
            const items = document.querySelectorAll('.user-item');
            items.forEach(item => {
                const name = item.querySelector('.user-item-name').textContent.toLowerCase();
                item.style.display = name.includes(query.toLowerCase()) ? 'flex' : 'none';
            });
        }
        
        function searchChats(query) {
            const items = document.querySelectorAll('.chat-item');
            items.forEach(item => {
                const name = item.querySelector('.chat-name').textContent.toLowerCase();
                const message = item.querySelector('.last-message').textContent.toLowerCase();
                item.style.display = (name.includes(query.toLowerCase()) || 
                                     message.includes(query.toLowerCase())) ? 'flex' : 'none';
            });
        }
        
        function switchTab(tab) {
            // Обновляем активную вкладку
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Фильтруем чаты
            const items = document.querySelectorAll('.chat-item');
            items.forEach(item => {
                if (tab === 'all') {
                    item.style.display = 'flex';
                } else if (tab === 'unread') {
                    const unreadCount = item.querySelector('.unread-count');
                    item.style.display = unreadCount ? 'flex' : 'none';
                } else if (tab === 'groups') {
                    // Здесь нужна логика для определения групповых чатов
                    item.style.display = 'none';
                }
            });
        }
        
        function showChatInfo() {
            showToast('Информация о чате', 'info');
        }
        
        function updateChatHeader(participants) {
            if (!participants || participants.length === 0) return;
            
            // Находим собеседника (не текущего пользователя)
            const opponent = participants.find(p => p.id != currentUserId);
            if (opponent) {
                document.getElementById('chatName').textContent = opponent.name;
                document.getElementById('chatAvatar').textContent = opponent.name.substring(0, 1).toUpperCase();
                
                const statusEl = document.getElementById('chatStatus');
                if (opponent.online_status === 'online') {
                    statusEl.textContent = 'в сети';
                    statusEl.className = 'chat-header-status online';
                } else if (opponent.last_seen) {
                    statusEl.textContent = 'был(а) ' + formatLastSeen(opponent.last_seen);
                    statusEl.className = 'chat-header-status';
                } else {
                    statusEl.textContent = 'офлайн';
                    statusEl.className = 'chat-header-status';
                }
            }
        }
        
        // Опрос новых сообщений
        function startPolling() {
            pollInterval = setInterval(() => {
                if (currentChatId) {
                    checkNewMessages();
                }
            }, 3000); // Каждые 3 секунды
        }
        
        function checkNewMessages() {
            fetch(`/messenger/messages/new?chat_id=${currentChatId}&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        const container = document.getElementById('messagesContainer');
                        data.messages.forEach(msg => {
                            const messageEl = createMessageElement(msg);
                            container.appendChild(messageEl);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                        container.scrollTop = container.scrollHeight;
                        
                        // Звуковое уведомление
                        playNotificationSound();
                    }
                })
                .catch(error => {
                    console.error('Error checking new messages:', error);
                });
        }
        
        function playNotificationSound() {
            // Создаем простой звук уведомления
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmFgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
            audio.play().catch(e => console.log('Could not play notification sound'));
        }
        
        // Утилиты
        function closeAllModals() {
            closeUsersModal();
            closeEmojiPicker();
            closeContextMenu();
            document.getElementById('callModal').classList.remove('show');
        }
        
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: '✅',
                error: '❌',
                info: 'ℹ️',
                warning: '⚠️'
            };
            
            toast.innerHTML = `
                <span class="toast-icon">${icons[type]}</span>
                <span class="toast-message">${message}</span>
                <span class="toast-close" onclick="this.parentElement.remove()">✕</span>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        function formatTime(datetime) {
            const date = new Date(datetime);
            return date.toLocaleTimeString('ru-RU', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
        
        function formatLastSeen(datetime) {
            const date = new Date(datetime);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'только что';
            if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
            
            return date.toLocaleDateString('ru-RU');
        }
        
        function formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>