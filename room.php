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
            
            bottom: env(safe-area-inset-bottom);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Header */
        .header-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: linear-gradient(to bottom, rgba(15, 15, 20, 0.95), transparent);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
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
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }

        .conference-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .conference-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.3px;
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
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }

        .header-actions {
            display: flex;
            gap: 12px;
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
            padding: 70px 0 90px;
            transition: var(--transition);
            height: 100vh;
            overflow: hidden;
        }

        /* Videos Container */
        .videos-section {
            flex: 1;
            padding: 8px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }

        .videos-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Main speaker view */
        .main-speaker {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 0;
        }

        .main-speaker .video-tile {
            width: 100%;
            height: 100%;
            max-width: 1200px;
            max-height: 100%;
            aspect-ratio: 16/9;
        }

        /* Thumbnails strip */
        .thumbnails-strip {
            height: 120px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 4px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: var(--radius);
            scroll-behavior: smooth;
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

        /* Grid view (when no one is speaking) */
        .videos-container {
            width: 100%;
            height: 100%;
            display: grid;
            gap: 8px;
            transition: var(--transition);
            align-content: center;
            justify-content: center;
        }

        .videos-container.speaker-view {
            display: none;
        }

        /* Responsive grid based on participant count */
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
            grid-template-rows: 1fr;
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

        /* При большем количестве участников */
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
            /* Safari fix */
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }

        .video-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
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
        }

        .video-indicators {
            display: flex;
            gap: 8px;
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
            background: rgba(239, 68, 68, 0.3);
        }

        .indicator.muted ion-icon {
            color: var(--danger);
        }

        /* Chat Panel - Sidebar Style */
        .chat-panel {
            width: 0;
            background: var(--bg-secondary);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            overflow: hidden;
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
            position: fixed;
            bottom: env(safe-area-inset-bottom);
            left: 0;
            right: 0;
            padding: 5px;
            background: linear-gradient(to top, rgba(15, 15, 20, 0.95), transparent);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 100;
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
            background: var(--bg-tertiary);
            color: var(--text-tertiary);
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
            .main-content {
                flex-direction: column;
                padding: 60px 0 80px;
            }

            .videos-section {
                padding: 4px;
            }

            .videos-container {
                gap: 4px;
            }

            /* Мобильная сетка */
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

            .videos-container[data-count="5"],
            .videos-container[data-count="6"] {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(3, 1fr);
            }

            .videos-container[data-count="7"],
            .videos-container[data-count="8"],
            .videos-container[data-count="9"] {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(3, 1fr);
            }

            .video-tile {
                min-height: 100px;
                aspect-ratio: 4/3;
            }

            .chat-panel {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100% !important;
                height: 0;
                max-height: 60vh;
                border-left: none;
                border-top: 1px solid var(--border);
                border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            }

            .chat-panel.open {
                height: 60vh;
            }

            .controls-bar {
                padding: 12px;
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

        /* Tablet */
        @media (min-width: 769px) and (max-width: 1024px) {
            .chat-panel.open {
                width: 320px;
            }
        }

        /* Safari-specific fixes */
        @supports (-webkit-touch-callout: none) {
            video {
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
                will-change: transform;
            }
            
            .video-tile {
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
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
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Videos Section -->
            <div class="videos-section">
                <div class="videos-wrapper" id="videosWrapper">
                    <!-- Grid view by default -->
                    <div class="videos-container" id="videosGrid" data-count="1">
                        <div class="loading-spinner" id="loadingSpinner"></div>
                    </div>
                    
                    <!-- Speaker view (hidden by default) -->
                    <div class="main-speaker" id="mainSpeaker" style="display: none;">
                        <!-- Main speaker video will be moved here -->
                    </div>
                    <div class="thumbnails-strip" id="thumbnailsStrip" style="display: none;">
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
                    <div class="message system">
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
        // Копируем весь JavaScript код из вашего файла без изменений
        // Но добавляем Safari-specific фиксы

        // ===== CONFIGURATION FROM PHP =====
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
            viewMode: 'grid', // 'grid' or 'speaker'
            audioProcessors: new Map() // Store audio processors for cleanup
        };

        // ===== AUDIO LEVEL DETECTION WITH SPEAKER SWITCHING =====
        function setupAudioDetection(stream, userId) {
            if (!stream.getAudioTracks().length) return;
            
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const analyser = audioContext.createAnalyser();
            const microphone = audioContext.createMediaStreamSource(stream);
            const processor = audioContext.createScriptProcessor(2048, 1, 1);
            
            analyser.smoothingTimeConstant = 0.8;
            analyser.fftSize = 1024;
            
            microphone.connect(analyser);
            analyser.connect(processor);
            processor.connect(audioContext.destination);
            
            let speakingCounter = 0;
            
            processor.onaudioprocess = () => {
                const array = new Uint8Array(analyser.frequencyBinCount);
                analyser.getByteFrequencyData(array);
                const average = array.reduce((a, b) => a + b) / array.length;
                
                // Порог для определения речи
                const threshold = 25;
                const tile = document.querySelector(`[data-user-id="${userId}"]`);
                
                if (tile) {
                    if (average > threshold) {
                        tile.classList.add('speaking');
                        speakingCounter++;
                        
                        // Если говорит достаточно долго (более 1 секунды), делаем главным спикером
                        if (speakingCounter > 20) {
                            setMainSpeaker(userId);
                        }
                    } else {
                        tile.classList.remove('speaking');
                        speakingCounter = 0;
                    }
                }
            };
            
            // Сохраняем процессор для последующей очистки
            state.audioProcessors.set(userId, { audioContext, processor });
            
            return { audioContext, processor };
        }

        // ===== SPEAKER VIEW MANAGEMENT =====
        function setMainSpeaker(userId) {
            // Не переключаем, если уже этот спикер
            if (state.currentSpeaker === userId) return;
            
            // Не переключаем, если только 1-2 участника
            const totalParticipants = state.participants.size + 1;
            if (totalParticipants <= 2) return;
            
            state.currentSpeaker = userId;
            switchToSpeakerView(userId);
        }

        function switchToSpeakerView(speakerId) {
            const grid = document.getElementById('videosGrid');
            const mainSpeaker = document.getElementById('mainSpeaker');
            const thumbnails = document.getElementById('thumbnailsStrip');
            
            // Находим видео говорящего
            const speakerTile = document.querySelector(`[data-user-id="${speakerId}"]`);
            if (!speakerTile) return;
            
            // Переключаем на speaker view
            state.viewMode = 'speaker';
            grid.style.display = 'none';
            mainSpeaker.style.display = 'flex';
            thumbnails.style.display = 'flex';
            
            // Очищаем контейнеры
            mainSpeaker.innerHTML = '';
            thumbnails.innerHTML = '';
            
            // Клонируем спикера в главное окно
            const mainSpeakerTile = speakerTile.cloneNode(true);
            mainSpeakerTile.classList.add('main-speaker-tile');
            mainSpeaker.appendChild(mainSpeakerTile);
            
            // Переносим видеопоток
            const mainVideo = mainSpeakerTile.querySelector('video');
            const originalVideo = speakerTile.querySelector('video');
            if (mainVideo && originalVideo && originalVideo.srcObject) {
                mainVideo.srcObject = originalVideo.srcObject;
                mainVideo.play().catch(e => console.error('Video play error:', e));
            }
            
            // Добавляем всех остальных в thumbnails
            const allTiles = grid.querySelectorAll('.video-tile');
            allTiles.forEach(tile => {
                if (tile.dataset.userId !== speakerId) {
                    const thumbnail = tile.cloneNode(true);
                    thumbnail.onclick = () => setMainSpeaker(tile.dataset.userId);
                    thumbnails.appendChild(thumbnail);
                    
                    // Переносим видеопоток
                    const thumbVideo = thumbnail.querySelector('video');
                    const origVideo = tile.querySelector('video');
                    if (thumbVideo && origVideo && origVideo.srcObject) {
                        thumbVideo.srcObject = origVideo.srcObject;
                        thumbVideo.play().catch(e => console.error('Thumbnail video play error:', e));
                    }
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
            
            updateLayout();
        }

        // ===== UPDATE LAYOUT =====
        function updateLayout() {
            const grid = document.getElementById('videosGrid');
            const tiles = grid.querySelectorAll('.video-tile:not(.loading-spinner)');
            const tileCount = tiles.length;
            
            // Устанавливаем количество для адаптивной сетки
            grid.dataset.count = Math.min(tileCount, 12);
            
            // Если больше 2 участников и кто-то говорит - переключаем на speaker view
            if (tileCount > 2 && state.currentSpeaker && state.viewMode === 'grid') {
                switchToSpeakerView(state.currentSpeaker);
            }
            // Если 2 или меньше участников - возвращаемся в grid view
            else if (tileCount <= 2 && state.viewMode === 'speaker') {
                switchToGridView();
            }
            
            // Корректируем размер видео плиток в зависимости от количества
            if (window.innerWidth > 768) {
                tiles.forEach(tile => {
                    if (tileCount <= 4) {
                        tile.style.maxHeight = '400px';
                    } else if (tileCount <= 9) {
                        tile.style.maxHeight = '300px';
                    } else {
                        tile.style.maxHeight = '200px';
                    }
                });
            } else {
                tiles.forEach(tile => {
                    if (tileCount <= 2) {
                        tile.style.maxHeight = '300px';
                    } else if (tileCount <= 6) {
                        tile.style.maxHeight = '200px';
                    } else {
                        tile.style.maxHeight = '150px';
                    }
                });
            }
        }

        // ===== HANDLE REMOTE STREAM =====
        function handleRemoteStream(userId, userName, stream) {
            console.log(`Adding remote stream for ${userName}`);
            
            state.participants.set(userId, { userName, stream });
            
            // Всегда добавляем в grid view сначала
            const grid = document.getElementById('videosGrid');
            let tile = grid.querySelector(`[data-user-id="${userId}"]`);
            
            if (!tile) {
                tile = createVideoTile(userId, userName);
                grid.appendChild(tile);
            }
            
            const video = tile.querySelector('video');
            if (video) {
                video.srcObject = stream;
                
                // Safari fix
                if (isSafari()) {
                    video.setAttribute('autoplay', '');
                    video.setAttribute('playsinline', '');
                    
                    const playPromise = video.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(error => {
                            console.log('Safari remote video play issue, retrying...');
                            setTimeout(() => {
                                video.play().catch(e => console.error('Video play error:', e));
                            }, 100);
                        });
                    }
                }
                
                // Setup audio detection
                setupAudioDetection(stream, userId);
                
                const avatar = tile.querySelector('.video-avatar');
                if (avatar) {
                    video.onplaying = () => {
                        avatar.style.display = 'none';
                    };
                }
            }
            
            updateLayout();
            updateParticipantCount();
            
            // Если мы в speaker view, обновляем его
            if (state.viewMode === 'speaker' && state.currentSpeaker) {
                switchToSpeakerView(state.currentSpeaker);
            }
        }


        // ===== CREATE VIDEO TILE WITH SAFARI FIX =====
        function createVideoTile(userId, userName) {
            const tile = document.createElement('div');
            tile.className = 'video-tile';
            tile.dataset.userId = userId;
            
            const initials = userName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            
            tile.innerHTML = `
                <video autoplay playsinline></video>
                <div class="video-avatar">${initials}</div>
                <div class="video-overlay">
                    <div class="video-name">${userName}</div>
                    <div class="video-indicators">
                        <div class="indicator">
                            <ion-icon name="mic"></ion-icon>
                        </div>
                    </div>
                </div>
            `;
            
            return tile;
        }

        // ===== REMOVE PARTICIPANT =====
        function removeParticipant(userId) {
            console.log(`Removing participant ${userId}`);
            
            // Закрываем peer connection
            const pc = state.peerConnections.get(userId);
            if (pc) {
                pc.close();
                state.peerConnections.delete(userId);
            }
            
            // Очищаем audio processor
            const audioProcessor = state.audioProcessors.get(userId);
            if (audioProcessor) {
                audioProcessor.processor.disconnect();
                audioProcessor.audioContext.close();
                state.audioProcessors.delete(userId);
            }
            
            // Удаляем из участников
            state.participants.delete(userId);
            
            // Удаляем видео тайл из grid
            const tile = document.querySelector(`[data-user-id="${userId}"]`);
            if (tile) {
                tile.remove();
            }
            
            // Если удаляемый был главным спикером, переключаемся обратно в grid
            if (state.currentSpeaker === userId) {
                switchToGridView();
            }
            
            updateLayout();
            updateParticipantCount();
        }

        
        // ===== INITIALIZATION WITH IMPROVED ERROR HANDLING =====
        async function initialize() {
            console.log('Initializing conference...');
            showToast('Подключение к конференции...', 'info');
            
            try {
                // Запрашиваем доступ к медиа
                state.localStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        facingMode: 'user'
                    },
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });
                
                console.log('Got local stream');
                addLocalVideo();
                
                document.getElementById('loadingSpinner').style.display = 'none';
                
                startSignaling();
                
                await sendSignal('join', null, {
                    userId: CONFIG.userId,
                    userName: CONFIG.userName
                });
                
                setTimeout(async () => {
                    await loadExistingParticipants();
                }, 1500);
                
                showToast('Подключено', 'success');
                
            } catch (error) {
                console.error('Media access error:', error);
                
                if (error.name === 'NotAllowedError') {
                    showToast('Доступ к камере/микрофону запрещен', 'error');
                } else if (error.name === 'NotFoundError') {
                    showToast('Камера или микрофон не найдены', 'error');
                } else {
                    showToast('Ошибка доступа к медиа устройствам', 'error');
                }
                
                document.getElementById('loadingSpinner').style.display = 'none';
                
                startSignaling();
                await sendSignal('join', null, {
                    userId: CONFIG.userId,
                    userName: CONFIG.userName
                });
            }
        }



         // ===== LOCAL VIDEO WITH SAFARI FIX =====
        function addLocalVideo() {
            const tile = document.createElement('div');
            tile.className = 'video-tile';
            tile.id = 'localVideoTile';
            tile.dataset.userId = 'local';
            
            const initials = CONFIG.userName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            
            tile.innerHTML = `
                <video id="localVideo" autoplay muted playsinline></video>
                <div class="video-avatar" id="localAvatar">${initials}</div>
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
            
            document.getElementById('videosGrid').appendChild(tile);
            const video = document.getElementById('localVideo');
            video.srcObject = state.localStream;
            
            // Safari fix - ensure video plays
            if (isSafari()) {
                video.setAttribute('autoplay', '');
                video.setAttribute('muted', '');
                video.setAttribute('playsinline', '');
                
                // Force play on Safari
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.log('Safari autoplay issue, retrying...');
                        setTimeout(() => video.play(), 100);
                    });
                }
            }
            
            document.getElementById('localAvatar').style.display = 'none';
            updateLayout();
        }


        function isSafari() {
            return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        }


        // Копируем все остальные функции из оригинального кода
        // WebRTC функции
        function createPeerConnection(userId, userName, isInitiator = false) {
            console.log(`Creating peer connection for ${userName} (${userId}), initiator: ${isInitiator}`);
            
            if (state.peerConnections.has(userId)) {
                const oldPc = state.peerConnections.get(userId);
                oldPc.close();
                state.peerConnections.delete(userId);
            }
            
            const pc = new RTCPeerConnection({
                iceServers: CONFIG.iceServers,
                iceCandidatePoolSize: 10
            });
            
            if (state.localStream) {
                state.localStream.getTracks().forEach(track => {
                    pc.addTrack(track, state.localStream);
                    console.log(`Added ${track.kind} track to peer connection`);
                });
            }
            
            pc.ontrack = (event) => {
                console.log(`Received ${event.track.kind} track from ${userName}`);
                handleRemoteStream(userId, userName, event.streams[0]);
            };
            
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    console.log(`Sending ICE candidate to ${userName}`);
                    sendSignal('ice-candidate', userId, {
                        candidate: event.candidate.toJSON()
                    });
                }
            };
            
            pc.onconnectionstatechange = () => {
                console.log(`Connection state with ${userName}: ${pc.connectionState}`);
                
                if (pc.connectionState === 'connected') {
                    console.log(`Successfully connected to ${userName}`);
                    state.reconnectAttempts.set(userId, 0);
                    showToast(`${userName} подключился`, 'success');
                } else if (pc.connectionState === 'failed') {
                    console.log(`Connection failed with ${userName}, attempting reconnect...`);
                    attemptReconnect(userId, userName);
                } else if (pc.connectionState === 'disconnected') {
                    console.log(`Disconnected from ${userName}`);
                }
            };
            
            pc.onicegatheringstatechange = () => {
                console.log(`ICE gathering state with ${userName}: ${pc.iceGatheringState}`);
            };
            
            if (state.pendingCandidates.has(userId)) {
                const candidates = state.pendingCandidates.get(userId);
                console.log(`Applying ${candidates.length} pending ICE candidates for ${userName}`);
                candidates.forEach(candidate => {
                    pc.addIceCandidate(new RTCIceCandidate(candidate))
                        .catch(e => console.error('Error adding pending ICE candidate:', e));
                });
                state.pendingCandidates.delete(userId);
            }
            
            state.peerConnections.set(userId, pc);
            
            if (isInitiator) {
                createAndSendOffer(userId, pc);
            }
            
            return pc;
        }

        async function createAndSendOffer(userId, pc) {
            try {
                console.log(`Creating offer for user ${userId}`);
                const offer = await pc.createOffer({
                    offerToReceiveVideo: true,
                    offerToReceiveAudio: true
                });
                
                await pc.setLocalDescription(offer);
                
                await sendSignal('offer', userId, {
                    sdp: offer.sdp,
                    type: offer.type
                });
                
                console.log(`Offer sent to user ${userId}`);
            } catch (error) {
                console.error('Error creating offer:', error);
            }
        }

        async function handleOffer(fromUserId, fromUserName, offer) {
            console.log(`Received offer from ${fromUserName} (${fromUserId})`);
            
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
                
                console.log(`Answer sent to ${fromUserName}`);
            } catch (error) {
                console.error('Error handling offer:', error);
            }
        }

        async function handleAnswer(fromUserId, answer) {
            console.log(`Received answer from user ${fromUserId}`);
            const pc = state.peerConnections.get(fromUserId);
            
            if (pc) {
                try {
                    await pc.setRemoteDescription(new RTCSessionDescription(answer));
                    console.log(`Answer processed for user ${fromUserId}`);
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
                    console.log(`ICE candidate added for user ${fromUserId}`);
                } catch (error) {
                    console.error('Error adding ICE candidate:', error);
                }
            } else {
                if (!state.pendingCandidates.has(fromUserId)) {
                    state.pendingCandidates.set(fromUserId, []);
                }
                state.pendingCandidates.get(fromUserId).push(candidate);
                console.log(`Stored pending ICE candidate for user ${fromUserId}`);
            }
        }

        function removeParticipant(userId) {
            console.log(`Removing participant ${userId}`);
            
            const pc = state.peerConnections.get(userId);
            if (pc) {
                pc.close();
                state.peerConnections.delete(userId);
            }
            
            state.participants.delete(userId);
            
            const tile = document.querySelector(`[data-user-id="${userId}"]`);
            if (tile) {
                tile.remove();
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
                    const pc = createPeerConnection(userId, userName, true);
                }, 2000 * (attempts + 1));
            } else {
                console.log(`Max reconnect attempts reached for ${userName}`);
                removeParticipant(userId);
                showToast(`${userName} отключился`, 'error');
            }
        }

        // Signaling функции
        async function sendSignal(type, targetUserId, data = {}) {
            const signal = {
                type,
                from_user_id: CONFIG.userId,
                from_user_name: CONFIG.userName,
                to_user_id: targetUserId,
                room_id: CONFIG.roomCode,
                data: JSON.stringify(data),
                timestamp: Date.now()
            };
            
            try {
                const response = await fetch(CONFIG.signalingUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(signal)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                if (result.lastId) {
                    state.lastSignalId = Math.max(state.lastSignalId, result.lastId);
                }
                
                return result;
            } catch (error) {
                console.error('Error sending signal:', error);
                throw error;
            }
        }

        async function pollSignals() {
            try {
                const response = await fetch(`${CONFIG.signalingUrl}?room_id=${CONFIG.roomCode}&last_id=${state.lastSignalId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const signals = await response.json();
                
                for (const signal of signals) {
                    if (signal.id > state.lastSignalId) {
                        state.lastSignalId = signal.id;
                    }
                    
                    if (signal.from_user_id == CONFIG.userId) {
                        continue;
                    }
                    
                    if (!signal.to_user_id || signal.to_user_id == CONFIG.userId) {
                        await processSignal(signal);
                    }
                }
            } catch (error) {
                console.error('Error polling signals:', error);
            }
        }

        async function processSignal(signal) {
            console.log(`Processing signal: ${signal.type} from ${signal.from_user_name}`);
            
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
                    
                case 'heartbeat':
                    break;
            }
        }

        function handleNewParticipant(userId, userName) {
            if (!state.peerConnections.has(userId)) {
                console.log(`New participant joined: ${userName}`);
                showToast(`${userName} присоединился`, 'success');
                createPeerConnection(userId, userName, true);
            }
        }

        function handleParticipantLeft(userId, userName) {
            console.log(`Participant left: ${userName}`);
            removeParticipant(userId);
            showToast(`${userName} покинул конференцию`, 'info');
        }

        async function loadExistingParticipants() {
            try {
                const response = await fetch(`/api/conference/participants/${CONFIG.conferenceId}`);
                if (response.ok) {
                    const participants = await response.json();
                    
                    for (const participant of participants) {
                        if (participant.user_id != CONFIG.userId && participant.is_active) {
                            console.log(`Found existing participant: ${participant.name || participant.user_name}`);
                            handleNewParticipant(participant.user_id, participant.name || participant.user_name);
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading participants:', error);
            }
        }

        function startSignaling() {
            state.pollingInterval = setInterval(pollSignals, 1000);
            state.heartbeatInterval = setInterval(() => {
                sendSignal('heartbeat');
            }, 10000);
        }

        function stopSignaling() {
            if (state.pollingInterval) {
                clearInterval(state.pollingInterval);
            }
            if (state.heartbeatInterval) {
                clearInterval(state.heartbeatInterval);
            }
        }

        // Controls функции
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
            const video = document.getElementById('localVideo');
            const avatar = document.getElementById('localAvatar');
            const indicator = document.getElementById('localCamIndicator');
            
            if (state.localStream) {
                const videoTrack = state.localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    
                    if (videoTrack.enabled) {
                        btn.classList.remove('muted');
                        indicator.classList.remove('muted');
                        btn.innerHTML = '<ion-icon name="videocam"></ion-icon>';
                        avatar.style.display = 'none';
                    } else {
                        btn.classList.add('muted');
                        indicator.classList.add('muted');
                        btn.innerHTML = '<ion-icon name="videocam-off"></ion-icon>';
                        avatar.style.display = 'flex';
                    }
                }
            }
        }

        async function toggleScreenShare() {
            const btn = document.getElementById('screenBtn');
            
            if (!state.isScreenSharing) {
                try {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({
                        video: true,
                        audio: false
                    });
                    
                    const videoTrack = screenStream.getVideoTracks()[0];
                    
                    state.peerConnections.forEach((pc) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            sender.replaceTrack(videoTrack);
                        }
                    });
                    
                    document.getElementById('localVideo').srcObject = screenStream;
                    
                    videoTrack.onended = () => {
                        stopScreenShare();
                    };
                    
                    state.isScreenSharing = true;
                    btn.classList.add('active');
                    showToast('Демонстрация экрана включена', 'success');
                    
                } catch (error) {
                    console.error('Error sharing screen:', error);
                    showToast('Ошибка при демонстрации экрана', 'error');
                }
            } else {
                stopScreenShare();
            }
        }


        // ===== IMPROVED CHAT TOGGLE =====
        function toggleChat() {
            const panel = document.getElementById('chatPanel');
            const btn = document.getElementById('chatToggleBtn');
            
            panel.classList.toggle('open');
            
            if (panel.classList.contains('open')) {
                btn.classList.add('active');
                btn.innerHTML = '<ion-icon name="close-outline"></ion-icon>';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<ion-icon name="chatbubble-outline"></ion-icon>';
            }
        }

        function stopScreenShare() {
            const btn = document.getElementById('screenBtn');
            
            if (state.localStream) {
                const videoTrack = state.localStream.getVideoTracks()[0];
                
                state.peerConnections.forEach((pc) => {
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender && videoTrack) {
                        sender.replaceTrack(videoTrack);
                    }
                });
                
                document.getElementById('localVideo').srcObject = state.localStream;
            }
            
            state.isScreenSharing = false;
            btn.classList.remove('active');
            showToast('Демонстрация экрана выключена', 'info');
        }

        // Chat функции
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
                showToast(`${userName}: ${message.substring(0, 50)}...`, 'info');
            }
        }

        function addChatMessage(userId, userName, message, isOwn) {
            const container = document.getElementById('chatMessages');
            const messageEl = document.createElement('div');
            messageEl.className = `message ${isOwn ? 'own' : ''}`;
            
            const time = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            
            messageEl.innerHTML = `
                <div class="message-bubble">
                    ${!isOwn ? `<div class="message-author">${userName}</div>` : ''}
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
            }).catch(error => {
                console.error('Error saving message:', error);
            });
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

        // UI Helpers
        function updateLayout() {
            const grid = document.getElementById('videosGrid');
            const tileCount = grid.children.length - 1;
            grid.dataset.count = Math.min(tileCount, 9);
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
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function leaveConference() {
            if (confirm('Вы уверены, что хотите покинуть конференцию?')) {
                await sendSignal('leave');
                
                if (state.localStream) {
                    state.localStream.getTracks().forEach(track => track.stop());
                }
                
                state.peerConnections.forEach(pc => pc.close());
                
                stopSignaling();
                
                fetch(`/conference/leave/${CONFIG.conferenceId}`, {
                    method: 'POST'
                });
                
                window.location.href = '/conference';
            }
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', initialize);

        window.addEventListener('beforeunload', () => {
            sendSignal('leave');
            if (state.localStream) {
                state.localStream.getTracks().forEach(track => track.stop());
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('Page hidden');
            } else {
                console.log('Page visible');
            }
        });
    </script>
</body>
</html>