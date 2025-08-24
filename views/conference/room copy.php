<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($conference['title'] ?? 'Видеоконференция') ?></title>
    
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            
            --bg-primary: #0f0f14;
            --bg-secondary: #1a1a24;
            --bg-tertiary: #252533;
            --bg-card: rgba(255, 255, 255, 0.05);
            
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-tertiary: rgba(255, 255, 255, 0.5);
            
            --border: rgba(255, 255, 255, 0.1);
            --border-light: rgba(255, 255, 255, 0.05);
            
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.7);
            
            --radius: 16px;
            --radius-lg: 24px;
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            
            --header-height: 70px;
            --controls-height: 80px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #0f0f14 0%, #1a1a24 100%);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .conference-app {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Header */
        .header-bar {
            height: var(--header-height);
            background: linear-gradient(to bottom, rgba(15, 15, 20, 0.95), transparent);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 100;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 12px;
            transition: var(--transition-fast);
            flex-shrink: 0;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }

        .conference-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
            overflow: hidden;
        }

        .conference-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conference-status {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .live-indicator {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            flex-shrink: 0;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
        }

        .header-button {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-fast);
        }

        .header-button:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }

        .header-button.active {
            background: var(--primary);
            color: white;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
            overflow: hidden;
        }

        /* Videos Section */
        .videos-section {
            flex: 1;
            padding: 8px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            overflow: hidden;
        }

        .videos-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 0;
        }

        /* Main speaker view */
        .main-speaker {
            flex: 1;
            display: none;
            align-items: center;
            justify-content: center;
            min-height: 0;
        }

        .main-speaker .video-tile {
            width: 100%;
            height: 100%;
            max-width: 1200px;
            aspect-ratio: 16/9;
        }

        /* Thumbnails strip */
        .thumbnails-strip {
            height: 120px;
            display: none;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 4px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: var(--radius);
            scroll-behavior: smooth;
            flex-shrink: 0;
        }

        .thumbnails-strip::-webkit-scrollbar {
            height: 4px;
        }

        .thumbnails-strip::-webkit-scrollbar-track {
            background: transparent;
        }

        .thumbnails-strip::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
        }

        .thumbnails-strip .video-tile {
            flex: 0 0 160px;
            height: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .thumbnails-strip .video-tile:hover {
            transform: scale(1.05);
            z-index: 10;
        }

        /* Grid view */
        .videos-container {
            width: 100%;
            height: 100%;
            display: grid;
            gap: 8px;
            transition: var(--transition);
            align-content: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Responsive grid */
        .videos-container[data-count="1"] {
            grid-template-columns: 1fr;
            max-width: 800px;
        }

        .videos-container[data-count="2"] {
            grid-template-columns: repeat(2, 1fr);
            max-width: 1200px;
        }

        .videos-container[data-count="3"] {
            grid-template-columns: repeat(3, 1fr);
        }

        .videos-container[data-count="4"] {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
        }

        .videos-container[data-count="5"],
        .videos-container[data-count="6"] {
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
        }

        .videos-container[data-count="7"],
        .videos-container[data-count="8"] {
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 1fr);
        }

        .videos-container[data-count="9"] {
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
        }

        .videos-container[data-count="10"],
        .videos-container[data-count="11"],
        .videos-container[data-count="12"] {
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(3, 1fr);
        }

        /* Video Tiles */
        .video-tile {
            position: relative;
            background: var(--bg-secondary);
            border-radius: var(--radius);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid transparent;
            transition: var(--transition);
            min-height: 120px;
            aspect-ratio: 16/9;
        }

        .video-tile:hover {
            transform: scale(1.02);
        }

        .video-tile.speaking {
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            animation: speakingPulse 2s ease-in-out infinite;
        }

        @keyframes speakingPulse {
            0%, 100% { 
                border-color: var(--success);
                box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            }
            50% { 
                border-color: rgba(16, 185, 129, 0.8);
                box-shadow: 0 0 30px rgba(16, 185, 129, 0.6);
            }
        }

        .video-tile video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: black;
            border-radius: var(--radius);
        }

        .video-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            position: absolute;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            box-shadow: var(--shadow);
        }

        .video-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 16px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            pointer-events: none;
        }

        .video-name {
            font-size: 14px;
            font-weight: 500;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%;
        }

        .video-indicators {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .indicator {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .indicator ion-icon {
            font-size: 16px;
            color: white;
        }

        .indicator.muted {
            background: rgba(239, 68, 68, 0.8);
        }

        /* Chat Panel */
        .chat-panel {
            width: 0;
            background: var(--bg-secondary);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            overflow: hidden;
            flex-shrink: 0;
        }

        .chat-panel.open {
            width: 380px;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .chat-title {
            font-size: 18px;
            font-weight: 600;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 0;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .message {
            max-width: 85%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.own {
            align-self: flex-end;
        }

        .message-bubble {
            padding: 12px 16px;
            border-radius: 16px;
            background: var(--bg-tertiary);
        }

        .message.own .message-bubble {
            background: var(--primary);
        }

        .message-author {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .message.own .message-author {
            color: rgba(255, 255, 255, 0.8);
        }

        .message-text {
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .message-time {
            font-size: 11px;
            color: var(--text-tertiary);
            margin-top: 4px;
        }

        .chat-input-container {
            padding: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-shrink: 0;
        }

        .chat-input-wrapper {
            flex: 1;
            background: var(--bg-tertiary);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            resize: none;
            max-height: 100px;
            line-height: 1.5;
        }

        .send-button {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--primary);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-fast);
        }

        .send-button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Controls Bar */
        .controls-bar {
            height: var(--controls-height);
            padding: 12px;
            background: linear-gradient(to top, rgba(15, 15, 20, 0.95), transparent);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }

        .controls-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .control-button {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-fast);
            position: relative;
        }

        .control-button:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .control-button.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .control-button.muted {
            background: var(--danger);
            color: white;
        }

        .control-button.end-call {
            background: var(--danger);
            width: 64px;
            height: 64px;
        }

        .control-button.end-call:hover {
            background: #dc2626;
        }

        .control-button ion-icon {
            font-size: 24px;
        }

        /* Host Controls */
        .host-controls {
            display: flex;
            gap: 8px;
        }

        .host-button {
            padding: 8px 12px;
            background: var(--warning);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .host-button:hover {
            background: #d97706;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 90px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: var(--bg-tertiary);
            backdrop-filter: blur(20px);
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow);
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
            z-index: 300;
            border: 1px solid var(--border);
            max-width: calc(100vw - 40px);
            text-align: center;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            background: var(--success);
            border-color: transparent;
        }

        .toast.error {
            background: var(--danger);
            border-color: transparent;
        }

        .toast.info {
            background: var(--info);
            border-color: transparent;
        }

        /* Loading */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .loading-spinner::after {
            content: '';
            display: block;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            :root {
                --header-height: 60px;
                --controls-height: 70px;
            }

            .header-bar {
                padding: 0 16px;
            }

            .conference-title {
                font-size: 16px;
            }

            .conference-status {
                font-size: 12px;
            }

            .header-button {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            .videos-section {
                padding: 4px;
            }

            .videos-container {
                gap: 4px;
            }

            .videos-container[data-count="1"] {
                grid-template-columns: 1fr;
            }

            .videos-container[data-count="2"] {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(2, 1fr);
            }

            .videos-container[data-count="3"],
            .videos-container[data-count="4"] {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(2, 1fr);
            }

            .video-tile {
                min-height: 100px;
                aspect-ratio: 4/3;
            }

            .video-avatar {
                width: 60px;
                height: 60px;
                font-size: 20px;
            }

            .chat-panel {
                position: fixed;
                bottom: var(--controls-height);
                left: 0;
                right: 0;
                width: 100% !important;
                height: 0;
                max-height: 50vh;
                border-left: none;
                border-top: 1px solid var(--border);
                border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            }

            .chat-panel.open {
                height: 50vh;
            }

            .control-button {
                width: 48px;
                height: 48px;
            }

            .control-button.end-call {
                width: 56px;
                height: 56px;
            }
        }
    </style>
</head>
<body>
    <div class="conference-app">
        <!-- Header -->
        <div class="header-bar">
            <div class="header-left">
                <button class="back-button" onclick="leaveConference()">
                    <ion-icon name="chevron-back-outline"></ion-icon>
                    <span>Выйти</span>
                </button>
                <div class="conference-info">
                    <div class="conference-title"><?= htmlspecialchars($conference['title'] ?? 'Видеоконференция') ?></div>
                    <div class="conference-status">
                        <span class="live-indicator"></span>
                        <span id="participantCount">Подключение...</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="header-button" id="chatToggleBtn" onclick="toggleChat()">
                    <ion-icon name="chatbubble-outline"></ion-icon>
                </button>
                <?php if (($conference['creator_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                <div class="host-controls">
                    <button class="host-button" onclick="endConferenceForAll()">
                        Завершить для всех
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Videos Section -->
            <div class="videos-section" id="videosSection">
                <div class="videos-wrapper" id="videosWrapper">
                    <!-- Grid view by default -->
                    <div class="videos-container" id="videosGrid" data-count="1">
                        <div class="loading-spinner" id="loadingSpinner"></div>
                    </div>
                    
                    <!-- Speaker view (hidden by default) -->
                    <div class="main-speaker" id="mainSpeaker">
                        <!-- Main speaker video will be moved here -->
                    </div>
                    <div class="thumbnails-strip" id="thumbnailsStrip">
                        <!-- Other participants will be shown here -->
                    </div>
                </div>
            </div>

            <!-- Chat Panel -->
            <div class="chat-panel" id="chatPanel">
                <div class="chat-header">
                    <div class="chat-title">Чат конференции</div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="message">
                        <div class="message-bubble">
                            <div class="message-text">Добро пожаловать в конференцию</div>
                        </div>
                    </div>
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message">
                                <div class="message-bubble">
                                    <div class="message-author"><?= htmlspecialchars($msg['user_name']) ?></div>
                                    <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
                                    <div class="message-time"><?= date('H:i', strtotime($msg['sent_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="chat-input-container">
                    <div class="chat-input-wrapper">
                        <textarea class="chat-input" id="messageInput" placeholder="Написать сообщение..." rows="1"
                                  onkeypress="handleChatKeyPress(event)" oninput="autoResize(this)"></textarea>
                    </div>
                    <button class="send-button" onclick="sendMessage()">
                        <ion-icon name="send"></ion-icon>
                    </button>
                </div>
            </div>
        </div>

        <!-- Controls Bar -->
        <div class="controls-bar">
            <div class="controls-container">
                <button class="control-button active" id="micBtn" onclick="toggleMicrophone()">
                    <ion-icon name="mic"></ion-icon>
                </button>
                <button class="control-button active" id="cameraBtn" onclick="toggleCamera()">
                    <ion-icon name="videocam"></ion-icon>
                </button>
                <button class="control-button" id="screenBtn" onclick="toggleScreenShare()">
                    <ion-icon name="desktop-outline"></ion-icon>
                </button>
                <button class="control-button end-call" onclick="leaveConference()">
                    <ion-icon name="call"></ion-icon>
                </button>
            </div>
        </div>

        <!-- Toast -->
        <div class="toast" id="toast"></div>
    </div>

    <script>
        // ===== CONFIGURATION =====
        const CONFIG = {
            conferenceId: <?= (int)($conference['id'] ?? 1) ?>,
            roomCode: '<?= htmlspecialchars($conference['room_id'] ?? '123-456-789', ENT_QUOTES) ?>',
            userId: <?= (int)($_SESSION['user_id'] ?? 0) ?>,
            userName: '<?= htmlspecialchars($currentUser['name'] ?? 'Guest', ENT_QUOTES) ?>',
            isHost: <?= ($conference['creator_id'] ?? 0) == ($_SESSION['user_id'] ?? 0) ? 'true' : 'false' ?>,
            signalingUrl: '/signaling',
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        // ===== STATE MANAGEMENT =====
        const state = {
            localStream: null,
            peerConnections: new Map(),
            participants: new Map(),
            pendingCandidates: new Map(),
            isScreenSharing: false,
            lastSignalId: 0,
            pollingInterval: null,
            heartbeatInterval: null,
            reconnectAttempts: new Map(),
            currentSpeaker: null,
            viewMode: 'grid',
            audioProcessors: new Map(),
            speakingTimeouts: new Map(),
            conferenceEnded: false,
            initialized: false,
            connectionStates: new Map()
        };

        // ===== VIDEO MANAGEMENT =====
        class ConferenceVideoManager {
    constructor() {
        this.videoStreams = new Map(); // Хранение потоков
        this.videoQueue = new Map();
        this.processing = false;
    }

    async setVideoStream(userId, stream, userName = '') {
        console.log(`Setting video stream for ${userName || userId}`);
        
        // Сохраняем поток
        this.videoStreams.set(userId, stream);
        
        if (this.processing) {
            this.videoQueue.set(userId, { stream, userName });
            return;
        }

        this.processing = true;
        try {
            await this._processVideoStream(userId, stream, userName);
            
            // Process queued videos
            for (const [queuedUserId, queuedData] of this.videoQueue) {
                await this._processVideoStream(queuedUserId, queuedData.stream, queuedData.userName);
                this.videoQueue.delete(queuedUserId);
            }
        } finally {
            this.processing = false;
        }
    }

    async _processVideoStream(userId, stream, userName) {
        return new Promise((resolve) => {
            const video = this._getOrCreateVideo(userId, userName);
            if (!video) {
                resolve();
                return;
            }

            // Важно: НЕ останавливаем существующий поток, если это тот же поток
            if (video.srcObject !== stream) {
                video.srcObject = stream;
            }
            
            video.autoplay = true;
            video.playsInline = true;
            video.muted = userId === 'local';

            let resolved = false;
            const resolveOnce = () => {
                if (!resolved) {
                    resolved = true;
                    resolve();
                }
            };

            const handlePlay = () => {
                console.log(`Video playing for ${userName || userId}`);
                
                // Проверяем, есть ли видео в потоке
                const videoTracks = stream.getVideoTracks();
                if (videoTracks.length > 0 && videoTracks[0].enabled) {
                    this._hideAvatar(userId);
                } else {
                    this._showAvatar(userId);
                }
                
                resolveOnce();
            };

            const handleError = (e) => {
                console.error(`Video error for ${userName || userId}:`, e);
                this._showAvatar(userId);
                resolveOnce();
            };

            video.addEventListener('playing', handlePlay, { once: true });
            video.addEventListener('error', handleError, { once: true });

            // Попытка воспроизведения
            video.play().catch(err => {
                console.warn(`Autoplay failed for ${userName}:`, err);
                // Для удаленных видео это нормально, они начнут играть после взаимодействия пользователя
                handleError(err);
            });

            setTimeout(resolveOnce, 3000);
        });
    }

    _getOrCreateVideo(userId, userName) {
        let tile = document.querySelector(`[data-user-id="${userId}"]`);
        
        if (!tile) {
            tile = this._createVideoTile(userId, userName);
            const container = document.getElementById('videosGrid');
            if (container) {
                container.appendChild(tile);
            }
        }

        return tile.querySelector('video');
    }

    _createVideoTile(userId, userName) {
        const tile = document.createElement('div');
        tile.className = 'video-tile';
        tile.dataset.userId = userId;
        
        const initials = (userName || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        
        if (userId === 'local') {
            tile.id = 'localVideoTile';
            tile.innerHTML = `
                <video autoplay muted playsinline></video>
                <div class="video-avatar">${initials}</div>
                <div class="video-overlay">
                    <div class="video-name">Вы</div>
                    <div class="video-indicators">
                        <div class="indicator" id="localMicIndicator">
                            <ion-icon name="mic"></ion-icon>
                        </div>
                        <div class="indicator" id="localCamIndicator">
                            <ion-icon name="videocam"></ion-icon>
                        </div>
                    </div>
                </div>
            `;
        } else {
            tile.innerHTML = `
                <video autoplay playsinline></video>
                <div class="video-avatar">${initials}</div>
                <div class="video-overlay">
                    <div class="video-name">${escapeHtml(userName)}</div>
                    <div class="video-indicators">
                        <div class="indicator">
                            <ion-icon name="mic"></ion-icon>
                        </div>
                    </div>
                </div>
            `;
        }
        
        return tile;
    }

    restoreVideoStreams() {
        // Восстанавливаем все видеопотоки после изменения DOM
        this.videoStreams.forEach((stream, userId) => {
            const video = document.querySelector(`[data-user-id="${userId}"] video`);
            if (video && video.srcObject !== stream) {
                video.srcObject = stream;
                video.play().catch(() => {});
            }
        });
    }

    _hideAvatar(userId) {
        const tile = document.querySelector(`[data-user-id="${userId}"]`);
        if (tile) {
            const avatar = tile.querySelector('.video-avatar');
            if (avatar) avatar.style.display = 'none';
        }
    }

    _showAvatar(userId) {
        const tile = document.querySelector(`[data-user-id="${userId}"]`);
        if (tile) {
            const avatar = tile.querySelector('.video-avatar');
            if (avatar) avatar.style.display = 'flex';
        }
    }

    removeVideo(userId) {
        const tile = document.querySelector(`[data-user-id="${userId}"]`);
        if (tile) {
            const video = tile.querySelector('video');
            if (video && video.srcObject) {
                // Не останавливаем треки здесь - они управляются внешне
                video.srcObject = null;
            }
            tile.remove();
        }
        this.videoStreams.delete(userId);
        this.videoQueue.delete(userId);
    }
}

        const videoManager = new ConferenceVideoManager();

        // ===== WEBRTC CONNECTION MANAGEMENT =====
        function createPeerConnection(userId, userName, isInitiator = false) {
    console.log(`Creating peer connection for ${userName} (${userId}), initiator: ${isInitiator}`);
    
    // Закрываем старое соединение если есть
    if (state.peerConnections.has(userId)) {
        const oldPc = state.peerConnections.get(userId);
        oldPc.close();
        state.peerConnections.delete(userId);
    }
    
    const pc = new RTCPeerConnection({
        iceServers: CONFIG.iceServers,
        iceCandidatePoolSize: 10
    });
    
    // Добавляем локальные треки
    if (state.localStream) {
        state.localStream.getTracks().forEach(track => {
            pc.addTrack(track, state.localStream);
            console.log(`Added local ${track.kind} track to connection with ${userName}`);
        });
    }
    
    // Обработка входящих треков
    pc.ontrack = (event) => {
        console.log(`Received ${event.track.kind} track from ${userName}`);
        if (event.streams && event.streams.length > 0) {
            // Немедленно обрабатываем поток
            handleRemoteStream(userId, userName, event.streams[0]);
        }
    };
    
    // Обработка ICE кандидатов
    pc.onicecandidate = (event) => {
        if (event.candidate) {
            sendSignal('ice-candidate', userId, {
                candidate: event.candidate.toJSON()
            });
        }
    };
    
    // Мониторинг состояния соединения
    pc.onconnectionstatechange = () => {
        state.connectionStates.set(userId, pc.connectionState);
        console.log(`Connection state with ${userName}: ${pc.connectionState}`);
        
        if (pc.connectionState === 'connected') {
            state.reconnectAttempts.set(userId, 0);
            showToast(`${userName} подключился`, 'success');
        } else if (pc.connectionState === 'failed') {
            attemptReconnect(userId, userName);
        } else if (pc.connectionState === 'disconnected') {
            setTimeout(() => {
                if (state.connectionStates.get(userId) === 'disconnected') {
                    removeParticipant(userId);
                }
            }, 5000);
        }
    };
    
    // Обработка отложенных ICE кандидатов
    if (state.pendingCandidates.has(userId)) {
        const candidates = state.pendingCandidates.get(userId);
        candidates.forEach(candidate => {
            pc.addIceCandidate(new RTCIceCandidate(candidate))
                .catch(e => console.error('Error adding pending ICE candidate:', e));
        });
        state.pendingCandidates.delete(userId);
    }
    
    state.peerConnections.set(userId, pc);
    
    // Если мы инициатор, создаем offer
    if (isInitiator) {
        setTimeout(() => createAndSendOffer(userId, pc), 100);
    }
    
    return pc;
}

        async function createAndSendOffer(userId, pc) {
            try {
                const offer = await pc.createOffer({
                    offerToReceiveVideo: true,
                    offerToReceiveAudio: true
                });
                
                await pc.setLocalDescription(offer);
                
                await sendSignal('offer', userId, {
                    sdp: offer.sdp,
                    type: offer.type
                });
                
            } catch (error) {
                console.error('Error creating offer:', error);
            }
        }

        async function handleOffer(fromUserId, fromUserName, offer) {
            console.log(`Received offer from ${fromUserName}`);
            
            let pc = state.peerConnections.get(fromUserId);
            if (!pc) {
                pc = createPeerConnection(fromUserId, fromUserName, false);
            }
            
            try {
                await pc.setRemoteDescription(new RTCSessionDescription(offer));
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                
                await sendSignal('answer', fromUserId, {
                    sdp: answer.sdp,
                    type: answer.type
                });
                
            } catch (error) {
                console.error('Error handling offer:', error);
            }
        }

        async function handleAnswer(fromUserId, answer) {
            const pc = state.peerConnections.get(fromUserId);
            
            if (pc) {
                try {
                    await pc.setRemoteDescription(new RTCSessionDescription(answer));
                } catch (error) {
                    console.error('Error handling answer:', error);
                }
            }
        }

        async function handleIceCandidate(fromUserId, candidate) {
            const pc = state.peerConnections.get(fromUserId);
            
            if (pc && pc.remoteDescription) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (error) {
                    console.error('Error adding ICE candidate:', error);
                }
            } else {
                if (!state.pendingCandidates.has(fromUserId)) {
                    state.pendingCandidates.set(fromUserId, []);
                }
                state.pendingCandidates.get(fromUserId).push(candidate);
            }
        }

        // ===== STREAM HANDLING =====
        async function handleRemoteStream(userId, userName, stream) {
    console.log(`Handling remote stream for ${userName}, tracks:`, {
        audio: stream.getAudioTracks().length,
        video: stream.getVideoTracks().length
    });
    
    // Сохраняем информацию об участнике
    state.participants.set(userId, { userName, stream });
    
    // Устанавливаем видеопоток
    await videoManager.setVideoStream(userId, stream, userName);
    
    // Настраиваем детекцию звука
    if (stream.getAudioTracks().length > 0) {
        setupAudioDetection(stream, userId);
    }
    
    // Обновляем UI
    updateLayout();
    updateParticipantCount();
}

        function removeParticipant(userId) {
            console.log(`Removing participant ${userId}`);
            
            const pc = state.peerConnections.get(userId);
            if (pc) {
                pc.close();
                state.peerConnections.delete(userId);
            }
            
            const audioProcessor = state.audioProcessors.get(userId);
            if (audioProcessor) {
                try {
                    audioProcessor.microphone.disconnect();
                    audioProcessor.audioContext.close();
                } catch (e) {}
                state.audioProcessors.delete(userId);
            }
            
            clearTimeout(state.speakingTimeouts.get(userId));
            state.speakingTimeouts.delete(userId);
            state.connectionStates.delete(userId);
            
            state.participants.delete(userId);
            videoManager.removeVideo(userId);
            
            if (state.currentSpeaker === userId) {
                switchToGridView();
            }
            
            updateLayout();
            updateParticipantCount();
        }

        function attemptReconnect(userId, userName) {
            const attempts = state.reconnectAttempts.get(userId) || 0;
            
            if (attempts < 3) {
                state.reconnectAttempts.set(userId, attempts + 1);
                console.log(`Reconnect attempt ${attempts + 1} for ${userName}`);
                
                setTimeout(() => {
                    createPeerConnection(userId, userName, true);
                }, 2000 * (attempts + 1));
            } else {
                removeParticipant(userId);
                showToast(`${userName} отключился`, 'error');
            }
        }

        // ===== AUDIO DETECTION =====
        function setupAudioDetection(stream, userId) {
            if (!stream.getAudioTracks().length) return;
            
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const analyser = audioContext.createAnalyser();
                const microphone = audioContext.createMediaStreamSource(stream);
                
                analyser.smoothingTimeConstant = 0.8;
                analyser.fftSize = 1024;
                
                microphone.connect(analyser);
                
                let speakingCounter = 0;
                let isSpeaking = false;
                
                const checkAudioLevel = () => {
                    if (state.conferenceEnded) return;
                    
                    const array = new Uint8Array(analyser.frequencyBinCount);
                    analyser.getByteFrequencyData(array);
                    const average = array.reduce((a, b) => a + b) / array.length;
                    
                    const threshold = 30;
                    const tile = document.querySelector(`[data-user-id="${userId}"]`);
                    
                    if (tile) {
                        if (average > threshold) {
                            if (!isSpeaking) {
                                tile.classList.add('speaking');
                                isSpeaking = true;
                                speakingCounter = 0;
                            }
                            speakingCounter++;
                            
                            if (speakingCounter > 20 && userId !== 'local') {
                                setMainSpeaker(userId);
                            }
                            
                            clearTimeout(state.speakingTimeouts.get(userId));
                            const timeout = setTimeout(() => {
                                if (tile) {
                                    tile.classList.remove('speaking');
                                    isSpeaking = false;
                                    speakingCounter = 0;
                                }
                            }, 1000);
                            
                            state.speakingTimeouts.set(userId, timeout);
                        }
                    }
                    
                    requestAnimationFrame(checkAudioLevel);
                };
                
                checkAudioLevel();
                state.audioProcessors.set(userId, { audioContext, analyser, microphone });
                
            } catch (error) {
                console.error('Audio detection setup failed:', error);
            }
        }

        // ===== SPEAKER VIEW MANAGEMENT =====
        function setMainSpeaker(userId) {
            if (state.currentSpeaker === userId) return;
            
            const totalParticipants = state.participants.size + 1;
            if (totalParticipants <= 2) return;
            
            state.currentSpeaker = userId;
            switchToSpeakerView(userId);
        }

        function switchToSpeakerView(speakerId) {
    const grid = document.getElementById('videosGrid');
    const mainSpeaker = document.getElementById('mainSpeaker');
    const thumbnails = document.getElementById('thumbnailsStrip');
    
    const speakerStream = videoManager.videoStreams.get(speakerId);
    if (!speakerStream) return;
    
    state.viewMode = 'speaker';
    grid.style.display = 'none';
    mainSpeaker.style.display = 'flex';
    thumbnails.style.display = 'flex';
    
    // Очищаем контейнеры
    mainSpeaker.innerHTML = '';
    thumbnails.innerHTML = '';
    
    // Создаем главное видео
    const participant = state.participants.get(speakerId);
    const mainTile = videoManager._createVideoTile(speakerId, participant ? participant.userName : 'Speaker');
    mainSpeaker.appendChild(mainTile);
    
    const mainVideo = mainTile.querySelector('video');
    mainVideo.srcObject = speakerStream;
    mainVideo.play().catch(() => {});
    
    // Добавляем миниатюры других участников
    videoManager.videoStreams.forEach((stream, userId) => {
        if (userId !== speakerId) {
            const userInfo = userId === 'local' ? { userName: 'Вы' } : state.participants.get(userId);
            const thumbTile = videoManager._createVideoTile(userId, userInfo ? userInfo.userName : 'User');
            thumbTile.onclick = () => setMainSpeaker(userId);
            thumbnails.appendChild(thumbTile);
            
            const thumbVideo = thumbTile.querySelector('video');
            thumbVideo.srcObject = stream;
            thumbVideo.play().catch(() => {});
        }
    });
}

       function switchToGridView() {
    const grid = document.getElementById('videosGrid');
    const mainSpeaker = document.getElementById('mainSpeaker');
    const thumbnails = document.getElementById('thumbnailsStrip');
    
    state.viewMode = 'grid';
    state.currentSpeaker = null;
    
    grid.style.display = 'grid';
    mainSpeaker.style.display = 'none';
    thumbnails.style.display = 'none';
    
    // Восстанавливаем видеопотоки в сетке
    videoManager.restoreVideoStreams();
    
    updateLayout();
}

        // ===== INITIALIZATION =====
        async function initialize() {
            if (state.initialized) return;
            state.initialized = true;
            
            console.log('Initializing conference...');
            showToast('Подключение к конференции...', 'info');
            
            try {
                state.localStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1280, max: 1920 },
                        height: { ideal: 720, max: 1080 },
                        facingMode: 'user'
                    },
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });
                
                console.log('Got local stream');
                await videoManager.setVideoStream('local', state.localStream, 'Local');
                setupAudioDetection(state.localStream, 'local');
                
            } catch (error) {
                console.error('Media access error:', error);
                handleMediaError(error);
            }
            
            document.getElementById('loadingSpinner').style.display = 'none';
            
            startSignaling();
            
            await sendSignal('join', null, {
                userId: CONFIG.userId,
                userName: CONFIG.userName
            });
            
            setTimeout(() => loadExistingParticipants(), 1000);
            updateLayout();
            
            showToast('Подключено к конференции', 'success');
        }

        function handleMediaError(error) {
            let message = 'Ошибка доступа к медиа устройствам';
            
            switch (error.name) {
                case 'NotAllowedError':
                    message = 'Доступ к камере/микрофону запрещен';
                    break;
                case 'NotFoundError':
                    message = 'Камера или микрофон не найдены';
                    break;
                case 'NotReadableError':
                    message = 'Камера или микрофон заняты';
                    break;
            }
            
            showToast(message, 'error');
        }

        // ===== SIGNALING =====
        async function sendSignal(type, targetUserId, data = {}) {
            if (state.conferenceEnded) return;
            
            const signal = {
                type,
                from_user_id: CONFIG.userId,
                from_user_name: CONFIG.userName,
                to_user_id: targetUserId,
                room_id: CONFIG.roomCode,
                data: JSON.stringify(data)
            };
            
            try {
                const response = await fetch(CONFIG.signalingUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(signal)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.lastId) {
                        state.lastSignalId = Math.max(state.lastSignalId, result.lastId);
                    }
                }
            } catch (error) {
                console.error('Error sending signal:', error);
            }
        }

        async function pollSignals() {
            if (state.conferenceEnded) return;
            
            try {
                const response = await fetch(`${CONFIG.signalingUrl}?room_id=${CONFIG.roomCode}&last_id=${state.lastSignalId}`);
                
                if (response.ok) {
                    const signals = await response.json();
                    
                    for (const signal of signals) {
                        if (signal.id > state.lastSignalId) {
                            state.lastSignalId = signal.id;
                        }
                        
                        if (signal.from_user_id != CONFIG.userId) {
                            if (!signal.to_user_id || signal.to_user_id == CONFIG.userId) {
                                await processSignal(signal);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error polling signals:', error);
            }
        }

        async function processSignal(signal) {
            if (state.conferenceEnded) return;
            
            const data = signal.data ? JSON.parse(signal.data) : {};
            
            switch (signal.type) {
                case 'join':
                    handleNewParticipant(signal.from_user_id, signal.from_user_name);
                    break;
                case 'offer':
                    await handleOffer(signal.from_user_id, signal.from_user_name, data);
                    break;
                case 'answer':
                    await handleAnswer(signal.from_user_id, data);
                    break;
                case 'ice-candidate':
                    await handleIceCandidate(signal.from_user_id, data.candidate);
                    break;
                case 'leave':
                    handleParticipantLeft(signal.from_user_id, signal.from_user_name);
                    break;
                case 'chat':
                    handleChatMessage(signal.from_user_id, signal.from_user_name, data.message);
                    break;
                case 'host-end-conference':
                    handleHostEndConference();
                    break;
            }
        }

        function handleNewParticipant(userId, userName) {
            if (!state.peerConnections.has(userId)) {
                console.log(`New participant: ${userName}`);
                showToast(`${userName} присоединился`, 'success');
                createPeerConnection(userId, userName, true);
            }
        }

        function handleParticipantLeft(userId, userName) {
            removeParticipant(userId);
            showToast(`${userName} покинул конференцию`, 'info');
        }

        function handleHostEndConference() {
            state.conferenceEnded = true;
            showToast('Конференция завершена хостом', 'info');
            setTimeout(() => window.location.href = '/conference', 3000);
        }

        async function loadExistingParticipants() {
            try {
                const response = await fetch(`/api/conference/participants/${CONFIG.conferenceId}`);
                if (response.ok) {
                    const participants = await response.json();
                    
                    for (const participant of participants) {
                        if (participant.user_id != CONFIG.userId && participant.is_active) {
                            handleNewParticipant(participant.user_id, participant.name || participant.user_name);
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading participants:', error);
            }
        }

        function startSignaling() {
            if (state.pollingInterval) return;
            
            state.pollingInterval = setInterval(pollSignals, 1000);
            state.heartbeatInterval = setInterval(() => {
                sendSignal('heartbeat');
            }, 10000);
        }

        function stopSignaling() {
            if (state.pollingInterval) {
                clearInterval(state.pollingInterval);
                state.pollingInterval = null;
            }
            if (state.heartbeatInterval) {
                clearInterval(state.heartbeatInterval);
                state.heartbeatInterval = null;
            }
        }

        // ===== CONTROLS =====
        function toggleMicrophone() {
            const btn = document.getElementById('micBtn');
            const indicator = document.getElementById('localMicIndicator');
            
            if (state.localStream) {
                const audioTrack = state.localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    
                    if (audioTrack.enabled) {
                        btn.classList.remove('muted');
                        indicator.classList.remove('muted');
                        btn.innerHTML = '<ion-icon name="mic"></ion-icon>';
                    } else {
                        btn.classList.add('muted');
                        indicator.classList.add('muted');
                        btn.innerHTML = '<ion-icon name="mic-off"></ion-icon>';
                    }
                }
            }
        }

        function toggleCamera() {
            const btn = document.getElementById('cameraBtn');
            const indicator = document.getElementById('localCamIndicator');
            
            if (state.localStream) {
                const videoTrack = state.localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    
                    if (videoTrack.enabled) {
                        btn.classList.remove('muted');
                        indicator.classList.remove('muted');
                        btn.innerHTML = '<ion-icon name="videocam"></ion-icon>';
                        videoManager._hideAvatar('local');
                    } else {
                        btn.classList.add('muted');
                        indicator.classList.add('muted');
                        btn.innerHTML = '<ion-icon name="videocam-off"></ion-icon>';
                        videoManager._showAvatar('local');
                    }
                }
            }
        }

        async function toggleScreenShare() {
            const btn = document.getElementById('screenBtn');
            
            if (!state.isScreenSharing) {
                try {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({
                        video: { mediaSource: 'screen' },
                        audio: true
                    });
                    
                    const videoTrack = screenStream.getVideoTracks()[0];
                    
                    state.peerConnections.forEach((pc) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) sender.replaceTrack(videoTrack);
                    });
                    
                    await videoManager.setVideoStream('local', screenStream, 'Screen Share');
                    
                    videoTrack.onended = () => stopScreenShare();
                    
                    state.isScreenSharing = true;
                    btn.classList.add('active');
                    showToast('Демонстрация экрана включена', 'success');
                    
                } catch (error) {
                    showToast('Ошибка при демонстрации экрана', 'error');
                }
            } else {
                stopScreenShare();
            }
        }

        function stopScreenShare() {
            const btn = document.getElementById('screenBtn');
            
            if (state.localStream) {
                const videoTrack = state.localStream.getVideoTracks()[0];
                
                state.peerConnections.forEach((pc) => {
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender && videoTrack) sender.replaceTrack(videoTrack);
                });
                
                videoManager.setVideoStream('local', state.localStream, 'Local');
            }
            
            state.isScreenSharing = false;
            btn.classList.remove('active');
            showToast('Демонстрация экрана выключена', 'info');
        }

        // ===== CHAT =====
        function toggleChat() {
            const panel = document.getElementById('chatPanel');
            const btn = document.getElementById('chatToggleBtn');
            
            panel.classList.toggle('open');
            
            if (panel.classList.contains('open')) {
                btn.classList.add('active');
                btn.innerHTML = '<ion-icon name="close-outline"></ion-icon>';
                setTimeout(() => {
                    const messages = document.getElementById('chatMessages');
                    messages.scrollTop = messages.scrollHeight;
                }, 100);
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<ion-icon name="chatbubble-outline"></ion-icon>';
            }
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            sendSignal('chat', null, { message });
            addChatMessage(CONFIG.userId, CONFIG.userName, message, true);
            
            input.value = '';
            input.style.height = 'auto';
        }

        function handleChatMessage(userId, userName, message) {
            addChatMessage(userId, userName, message, false);
            
            const panel = document.getElementById('chatPanel');
            if (!panel.classList.contains('open')) {
                showToast(`${userName}: ${message.substring(0, 30)}...`, 'info');
            }
        }

        function addChatMessage(userId, userName, message, isOwn) {
            const container = document.getElementById('chatMessages');
            const messageEl = document.createElement('div');
            messageEl.className = `message ${isOwn ? 'own' : ''}`;
            
            const time = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            
            messageEl.innerHTML = `
                <div class="message-bubble">
                    <div class="message-text">${escapeHtml(message)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            container.appendChild(messageEl);
            container.scrollTop = container.scrollHeight;
            
            fetch('/conference/send-message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `conference_id=${CONFIG.conferenceId}&message=${encodeURIComponent(message)}`
            }).catch(error => console.error('Error saving message:', error));
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
        }

        // ===== HOST CONTROLS =====
        async function endConferenceForAll() {
            if (!CONFIG.isHost) return;
            
            if (confirm('Вы уверены, что хотите завершить конференцию для всех участников?')) {
                try {
                    await sendSignal('host-end-conference', null, {});
                    await fetch(`/conference/end/${CONFIG.conferenceId}`, { method: 'POST' });
                    
                    state.conferenceEnded = true;
                    showToast('Конференция завершена', 'success');
                    
                    setTimeout(() => window.location.href = '/conference', 2000);
                    
                } catch (error) {
                    showToast('Ошибка при завершении конференции', 'error');
                }
            }
        }

        // ===== UI HELPERS =====
        function updateLayout() {
            const grid = document.getElementById('videosGrid');
            const tiles = grid.querySelectorAll('.video-tile');
            const tileCount = tiles.length;
            
            grid.dataset.count = Math.min(tileCount, 12);
            
            if (tileCount > 2 && state.currentSpeaker && state.viewMode === 'grid') {
                switchToSpeakerView(state.currentSpeaker);
            } else if (tileCount <= 2 && state.viewMode === 'speaker') {
                switchToGridView();
            }
        }

        function updateParticipantCount() {
            const count = state.participants.size + 1;
            const text = count === 1 ? '1 участник' : `${count} участников`;
            document.getElementById('participantCount').textContent = text;
        }

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            
            setTimeout(() => toast.classList.remove('show'), 4000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ===== LEAVE CONFERENCE =====
        async function leaveConference() {
            if (confirm('Вы уверены, что хотите покинуть конференцию?')) {
                try {
                    state.conferenceEnded = true;
                    
                    await sendSignal('leave');
                    
                    if (state.localStream) {
                        state.localStream.getTracks().forEach(track => track.stop());
                    }
                    
                    state.peerConnections.forEach(pc => pc.close());
                    
                    state.audioProcessors.forEach(processor => {
                        try {
                            processor.microphone.disconnect();
                            processor.audioContext.close();
                        } catch (e) {}
                    });
                    
                    state.speakingTimeouts.forEach(timeout => clearTimeout(timeout));
                    
                    stopSignaling();
                    
                    fetch(`/conference/leave/${CONFIG.conferenceId}`, { method: 'POST' });
                    
                    if (CONFIG.isHost) {
                        await sendSignal('host-end-conference', null, {});
                        await fetch(`/conference/end/${CONFIG.conferenceId}`, { method: 'POST' });
                    }
                    
                    window.location.href = '/conference';
                    
                } catch (error) {
                    window.location.href = '/conference';
                }
            }
        }

        // ===== EVENT LISTENERS =====
        document.addEventListener('DOMContentLoaded', initialize);

        window.addEventListener('beforeunload', () => {
            if (!state.conferenceEnded) {
                sendSignal('leave');
                if (state.localStream) {
                    state.localStream.getTracks().forEach(track => track.stop());
                }
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !state.conferenceEnded && !state.pollingInterval) {
                startSignaling();
            }
        });

        window.addEventListener('orientationchange', () => {
            setTimeout(updateLayout, 500);
        });

        window.addEventListener('resize', debounce(updateLayout, 250));

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        window.addEventListener('unhandledrejection', event => {
            console.error('Unhandled promise rejection:', event.reason);
            if (!state.conferenceEnded) {
                showToast('Произошла ошибка соединения', 'error');
            }
        });

        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                showToast('Обновление страницы отключено во время конференции', 'warning');
            }
        });

        // User interaction handler for autoplay
        function handleUserInteraction() {
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                if (video.paused && video.srcObject) {
                    video.play().catch(() => {});
                }
            });
            
            document.removeEventListener('click', handleUserInteraction);
            document.removeEventListener('touchstart', handleUserInteraction);
        }

        document.addEventListener('click', handleUserInteraction);
        document.addEventListener('touchstart', handleUserInteraction);



        document.addEventListener('click', () => {
    document.querySelectorAll('video').forEach(video => {
        if (video.paused && video.srcObject) {
            video.play().catch(() => {});
        }
    });
}, { once: true });


        // Debug function
        window.debugConference = () => {
            console.log('=== Conference Debug ===');
            console.log('Participants:', state.participants.size);
            console.log('Peer connections:', state.peerConnections.size);
            console.log('Connection states:', Array.from(state.connectionStates.entries()));
            console.log('Current speaker:', state.currentSpeaker);
            console.log('View mode:', state.viewMode);
        };

        console.log('Conference room script loaded successfully');
    </script>
</body>
</html>