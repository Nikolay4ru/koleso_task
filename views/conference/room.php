<?php
// views/conference/room.php
// Полноценная рабочая видеоконференция с WebRTC
?>
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
            --ios-blue: #007AFF;
            --ios-green: #34C759;
            --ios-red: #FF3B30;
            --ios-orange: #FF9500;
            --ios-gray: #8E8E93;
            --ios-gray-2: #AEAEB2;
            --ios-gray-3: #C7C7CC;
            --ios-gray-4: #D1D1D6;
            --ios-gray-5: #E5E5EA;
            --ios-gray-6: #F2F2F7;
            
            --bg-primary: #000000;
            --bg-secondary: rgba(28, 28, 30, 0.95);
            --bg-tertiary: rgba(44, 44, 46, 0.95);
            --bg-elevated: rgba(58, 58, 60, 0.95);
            
            --text-primary: #FFFFFF;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --text-tertiary: rgba(255, 255, 255, 0.3);
            
            --blur: saturate(180%) blur(20px);
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.5);
            
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 28px;
            
            --spring: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
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
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: var(--bg-secondary);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            padding: env(safe-area-inset-top) 20px 12px;
            padding-top: max(env(safe-area-inset-top), 12px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 4px;
            border: none;
            background: none;
            color: var(--ios-blue);
            font-size: 17px;
            font-weight: 400;
            cursor: pointer;
            padding: 4px;
        }

        .back-button:active {
            opacity: 0.5;
        }

        .conference-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .conference-title {
            font-size: 17px;
            font-weight: 600;
            letter-spacing: -0.4px;
        }

        .conference-status {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .live-indicator {
            width: 6px;
            height: 6px;
            background: var(--ios-green);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.2); }
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .header-button {
            width: 36px;
            height: 36px;
            border-radius: 18px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-button:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 0.2);
        }

        /* Videos Grid */
        .videos-container {
            flex: 1;
            padding: calc(env(safe-area-inset-top) + 60px) 8px calc(env(safe-area-inset-bottom) + 88px);
            padding-top: max(calc(env(safe-area-inset-top) + 60px), 72px);
            display: grid;
            gap: 8px;
            background: var(--bg-primary);
            position: relative;
            transition: all 0.3s var(--ease);
        }

        .videos-container[data-count="1"] {
            grid-template-columns: 1fr;
            padding: calc(env(safe-area-inset-top) + 60px) 0 calc(env(safe-area-inset-bottom) + 88px);
            padding-top: max(calc(env(safe-area-inset-top) + 60px), 72px);
        }

        .videos-container[data-count="2"] {
            grid-template-columns: 1fr;
            grid-template-rows: 1fr 1fr;
        }

        .videos-container[data-count="3"],
        .videos-container[data-count="4"] {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
        }

        .videos-container[data-count="5"],
        .videos-container[data-count="6"] {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(3, 1fr);
        }

        .video-tile {
            position: relative;
            background: #1C1C1E;
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        .video-tile.speaking {
            box-shadow: 0 0 0 3px var(--ios-green);
        }

        .video-tile video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .video-avatar {
            width: 80px;
            height: 80px;
            border-radius: 40px;
            background: linear-gradient(135deg, var(--ios-blue), #5AC8FA);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .video-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .video-name {
            font-size: 14px;
            font-weight: 500;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .video-indicators {
            display: flex;
            gap: 6px;
        }

        .indicator {
            width: 24px;
            height: 24px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .indicator ion-icon {
            font-size: 14px;
            color: white;
        }

        .indicator.muted {
            background: rgba(255, 59, 48, 0.3);
        }

        .indicator.muted ion-icon {
            color: var(--ios-red);
        }

        /* Controls */
        .controls-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 20px;
            padding-bottom: max(env(safe-area-inset-bottom), 16px);
            background: var(--bg-secondary);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            border-top: 0.5px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }

        .controls-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
        }

        .control-button {
            width: 54px;
            height: 54px;
            border-radius: 27px;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s var(--ease);
        }

        .control-button ion-icon {
            font-size: 24px;
        }

        .control-button:active {
            transform: scale(0.9);
        }

        .control-button.active {
            background: rgba(255, 255, 255, 0.25);
        }

        .control-button.muted {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-tertiary);
        }

        .control-button.end-call {
            background: var(--ios-red);
            width: 60px;
            height: 60px;
            border-radius: 30px;
        }

        /* Chat Panel */
        .chat-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60vh;
            max-height: 500px;
            background: var(--bg-secondary);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            transform: translateY(100%);
            transition: transform 0.3s var(--ease);
            z-index: 200;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
        }

        .chat-panel.open {
            transform: translateY(0);
        }

        .chat-header {
            padding: 8px 20px 16px;
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .chat-handle {
            width: 36px;
            height: 5px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2.5px;
            margin: 0 auto 12px;
        }

        .chat-title {
            font-size: 20px;
            font-weight: 600;
            text-align: center;
        }

        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            max-width: 75%;
            animation: messageSlide 0.3s var(--spring);
        }

        @keyframes messageSlide {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.own {
            align-self: flex-end;
        }

        .message-bubble {
            padding: 10px 14px;
            border-radius: 18px;
            background: var(--bg-elevated);
        }

        .message.own .message-bubble {
            background: var(--ios-blue);
        }

        .message-author {
            font-size: 12px;
            font-weight: 600;
            color: var(--ios-blue);
            margin-bottom: 4px;
        }

        .message.own .message-author {
            display: none;
        }

        .message-text {
            font-size: 16px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-time {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .message.own .message-time {
            color: rgba(255, 255, 255, 0.7);
        }

        .message.system {
            align-self: center;
            max-width: 100%;
        }

        .message.system .message-bubble {
            background: transparent;
            color: var(--text-secondary);
            font-size: 13px;
            text-align: center;
            padding: 6px 12px;
        }

        .chat-input-container {
            padding: 12px 16px;
            padding-bottom: max(env(safe-area-inset-bottom), 12px);
            border-top: 0.5px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 10px;
            align-items: flex-end;
            background: var(--bg-tertiary);
        }

        .chat-input-wrapper {
            flex: 1;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            min-height: 36px;
        }

        .chat-input {
            flex: 1;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 16px;
            outline: none;
            resize: none;
            max-height: 100px;
            line-height: 1.4;
        }

        .send-button {
            width: 36px;
            height: 36px;
            border-radius: 18px;
            background: var(--ios-blue);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .send-button ion-icon {
            font-size: 20px;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: calc(env(safe-area-inset-top) + 70px);
            top: max(calc(env(safe-area-inset-top) + 70px), 82px);
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: var(--bg-elevated);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            padding: 12px 20px;
            border-radius: var(--radius-xl);
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow-md);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s var(--spring);
            z-index: 300;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            background: var(--ios-green);
        }

        .toast.error {
            background: var(--ios-red);
        }

        /* Loading */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 48px;
            height: 48px;
        }

        .loading-spinner::after {
            content: '';
            display: block;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 3px solid var(--ios-gray-4);
            border-top-color: var(--ios-blue);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Desktop adjustments */
        @media (min-width: 768px) {
            .videos-container[data-count="2"] {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr;
            }

            .chat-panel {
                right: -380px;
                left: auto;
                width: 380px;
                height: 100%;
                max-height: 100%;
                border-radius: 0;
                transform: translateX(0);
                transition: right 0.3s var(--ease);
            }

            .chat-panel.open {
                right: 0;
            }

            .chat-handle {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .videos-container.chat-open {
                transform: scale(0.6) translateY(-15%);
                opacity: 0.7;
                pointer-events: none;
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
                    <span>Назад</span>
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
                <button class="header-button" onclick="toggleChat()">
                    <ion-icon name="chatbubble-outline"></ion-icon>
                </button>
            </div>
        </div>

        <!-- Videos Container -->
        <div class="videos-container" id="videosGrid" data-count="1">
            <div class="loading-spinner" id="loadingSpinner"></div>
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
                <button class="control-button" onclick="toggleScreenShare()">
                    <ion-icon name="desktop-outline"></ion-icon>
                </button>
                <button class="control-button end-call" onclick="leaveConference()">
                    <ion-icon name="call"></ion-icon>
                </button>
            </div>
        </div>

        <!-- Chat Panel -->
        <div class="chat-panel" id="chatPanel">
            <div class="chat-header">
                <div class="chat-handle"></div>
                <div class="chat-title">Чат</div>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="message system">
                    <div class="message-bubble">Добро пожаловать в конференцию</div>
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
                    <textarea class="chat-input" id="messageInput" placeholder="Сообщение" rows="1"
                              onkeypress="handleChatKeyPress(event)" oninput="autoResize(this)"></textarea>
                </div>
                <button class="send-button" onclick="sendMessage()">
                    <ion-icon name="arrow-up"></ion-icon>
                </button>
            </div>
        </div>

        <!-- Toast -->
        <div class="toast" id="toast"></div>
    </div>

    <script>
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
                { urls: 'stun:stun1.l.google.com:19302' },
                { urls: 'stun:stun2.l.google.com:19302' },
                { urls: 'stun:stun3.l.google.com:19302' },
                { urls: 'stun:stun4.l.google.com:19302' }
            ]
        };

        console.log('Conference Config:', CONFIG);

        // ===== STATE MANAGEMENT =====
        const state = {
            localStream: null,
            peerConnections: new Map(), // userId -> RTCPeerConnection
            participants: new Map(), // userId -> {userName, stream}
            pendingCandidates: new Map(), // userId -> candidates[]
            isScreenSharing: false,
            lastSignalId: 0,
            pollingInterval: null,
            heartbeatInterval: null,
            reconnectAttempts: new Map() // userId -> attemptCount
        };

        // ===== INITIALIZATION =====
        async function initialize() {
            console.log('Initializing conference...');
            showToast('Подключение к конференции...', 'info');
            
            try {
                // Get user media
                state.localStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
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
                
                // Hide loading spinner
                document.getElementById('loadingSpinner').style.display = 'none';
                
                // Start signaling
                startSignaling();
                
                // Send join signal
                await sendSignal('join', null, {
                    userId: CONFIG.userId,
                    userName: CONFIG.userName
                });
                
                // Load existing participants after a delay
                setTimeout(async () => {
                    await loadExistingParticipants();
                }, 1500);
                
                showToast('Подключено', 'success');
                
            } catch (error) {
                console.error('Media access error:', error);
                showToast('Ошибка доступа к камере/микрофону', 'error');
                document.getElementById('loadingSpinner').style.display = 'none';
                
                // Continue without video
                startSignaling();
                await sendSignal('join', null, {
                    userId: CONFIG.userId,
                    userName: CONFIG.userName
                });
            }
        }

        // ===== LOCAL VIDEO =====
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
            document.getElementById('localVideo').srcObject = state.localStream;
            document.getElementById('localAvatar').style.display = 'none';
            
            updateLayout();
        }

        // ===== WEBRTC CONNECTION =====
        function createPeerConnection(userId, userName, isInitiator = false) {
            console.log(`Creating peer connection for ${userName} (${userId}), initiator: ${isInitiator}`);
            
            // Close existing connection if any
            if (state.peerConnections.has(userId)) {
                const oldPc = state.peerConnections.get(userId);
                oldPc.close();
                state.peerConnections.delete(userId);
            }
            
            const pc = new RTCPeerConnection({
                iceServers: CONFIG.iceServers,
                iceCandidatePoolSize: 10
            });
            
            // Add local tracks
            if (state.localStream) {
                state.localStream.getTracks().forEach(track => {
                    pc.addTrack(track, state.localStream);
                    console.log(`Added ${track.kind} track to peer connection`);
                });
            }
            
            // Handle incoming tracks
            pc.ontrack = (event) => {
                console.log(`Received ${event.track.kind} track from ${userName}`);
                handleRemoteStream(userId, userName, event.streams[0]);
            };
            
            // Handle ICE candidates
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    console.log(`Sending ICE candidate to ${userName}`);
                    sendSignal('ice-candidate', userId, {
                        candidate: event.candidate.toJSON()
                    });
                }
            };
            
            // Handle connection state changes
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
            }
            
            // Handle ICE gathering state
            pc.onicegatheringstatechange = () => {
                console.log(`ICE gathering state with ${userName}: ${pc.iceGatheringState}`);
            };
            
            // Apply pending ICE candidates if any
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
            
            // If initiator, create and send offer
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
                // Store candidate for later
                if (!state.pendingCandidates.has(fromUserId)) {
                    state.pendingCandidates.set(fromUserId, []);
                }
                state.pendingCandidates.get(fromUserId).push(candidate);
                console.log(`Stored pending ICE candidate for user ${fromUserId}`);
            }
        }

        function handleRemoteStream(userId, userName, stream) {
            console.log(`Adding remote stream for ${userName}`);
            
            // Store participant info
            state.participants.set(userId, { userName, stream });
            
            // Check if video tile exists
            let tile = document.querySelector(`[data-user-id="${userId}"]`);
            
            if (!tile) {
                // Create new video tile
                tile = createVideoTile(userId, userName);
                document.getElementById('videosGrid').appendChild(tile);
            }
            
            // Set video stream
            const video = tile.querySelector('video');
            if (video) {
                video.srcObject = stream;
                video.play().catch(e => console.error('Error playing video:', e));
            }
            
            // Hide avatar when video is playing
            const avatar = tile.querySelector('.video-avatar');
            if (avatar) {
                video.onplaying = () => {
                    avatar.style.display = 'none';
                };
            }
            
            updateLayout();
            updateParticipantCount();
        }

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

        function removeParticipant(userId) {
            console.log(`Removing participant ${userId}`);
            
            // Close peer connection
            const pc = state.peerConnections.get(userId);
            if (pc) {
                pc.close();
                state.peerConnections.delete(userId);
            }
            
            // Remove from participants
            state.participants.delete(userId);
            
            // Remove video tile
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

        // ===== SIGNALING =====
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
                    
                    // Skip own signals
                    if (signal.from_user_id == CONFIG.userId) {
                        continue;
                    }
                    
                    // Process signal if it's for us or broadcast
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
                    // Update participant last seen
                    break;
            }
        }

        function handleNewParticipant(userId, userName) {
            if (!state.peerConnections.has(userId)) {
                console.log(`New participant joined: ${userName}`);
                showToast(`${userName} присоединился`, 'success');
                
                // Create peer connection and send offer
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
                const response = await fetch(`/api/conference/${CONFIG.conferenceId}/participants`);
                if (response.ok) {
                    const participants = await response.json();
                    
                    for (const participant of participants) {
                        if (participant.user_id != CONFIG.userId && participant.is_active) {
                            console.log(`Found existing participant: ${participant.user_name}`);
                            handleNewParticipant(participant.user_id, participant.user_name);
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading participants:', error);
            }
        }

        function startSignaling() {
            // Start polling for signals
            state.pollingInterval = setInterval(pollSignals, 1000);
            
            // Start heartbeat
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
            if (!state.isScreenSharing) {
                try {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({
                        video: true,
                        audio: false
                    });
                    
                    const videoTrack = screenStream.getVideoTracks()[0];
                    
                    // Replace video track in all peer connections
                    state.peerConnections.forEach((pc) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            sender.replaceTrack(videoTrack);
                        }
                    });
                    
                    // Update local video
                    document.getElementById('localVideo').srcObject = screenStream;
                    
                    videoTrack.onended = () => {
                        stopScreenShare();
                    };
                    
                    state.isScreenSharing = true;
                    showToast('Демонстрация экрана включена', 'success');
                    
                } catch (error) {
                    console.error('Error sharing screen:', error);
                    showToast('Ошибка при демонстрации экрана', 'error');
                }
            } else {
                stopScreenShare();
            }
        }

        function stopScreenShare() {
            if (state.localStream) {
                const videoTrack = state.localStream.getVideoTracks()[0];
                
                // Replace video track back in all peer connections
                state.peerConnections.forEach((pc) => {
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender && videoTrack) {
                        sender.replaceTrack(videoTrack);
                    }
                });
                
                // Update local video
                document.getElementById('localVideo').srcObject = state.localStream;
            }
            
            state.isScreenSharing = false;
            showToast('Демонстрация экрана выключена', 'info');
        }

        // ===== CHAT =====
        function toggleChat() {
            const panel = document.getElementById('chatPanel');
            const container = document.getElementById('videosGrid');
            
            panel.classList.toggle('open');
            container.classList.toggle('chat-open');
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Send via signaling
            sendSignal('chat', null, { message });
            
            // Add to local chat
            addChatMessage(CONFIG.userId, CONFIG.userName, message, true);
            
            // Clear input
            input.value = '';
            input.style.height = 'auto';
        }

        function handleChatMessage(userId, userName, message) {
            addChatMessage(userId, userName, message, false);
            
            // Show notification if chat is closed
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
            
            // Save to server
            fetch('/api/conference/message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conference_id: CONFIG.conferenceId,
                    user_id: userId,
                    message: message
                })
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

        // ===== UI HELPERS =====
        function updateLayout() {
            const grid = document.getElementById('videosGrid');
            const tileCount = grid.children.length - 1; // Exclude spinner
            grid.dataset.count = Math.min(tileCount, 6);
        }

        function updateParticipantCount() {
            const count = state.participants.size + 1; // +1 for self
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
                // Send leave signal
                await sendSignal('leave');
                
                // Stop all streams
                if (state.localStream) {
                    state.localStream.getTracks().forEach(track => track.stop());
                }
                
                // Close all connections
                state.peerConnections.forEach(pc => pc.close());
                
                // Stop signaling
                stopSignaling();
                
                // Update participant status
                fetch(`/api/conference/${CONFIG.conferenceId}/leave`, {
                    method: 'POST'
                });
                
                // Redirect
                window.location.href = '/conferences';
            }
        }

        // ===== INITIALIZATION ON LOAD =====
        document.addEventListener('DOMContentLoaded', initialize);

        // Handle page unload
        window.addEventListener('beforeunload', () => {
            sendSignal('leave');
            if (state.localStream) {
                state.localStream.getTracks().forEach(track => track.stop());
            }
        });

        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('Page hidden, pausing video');
                // Optionally pause video when tab is hidden
            } else {
                console.log('Page visible, resuming video');
                // Resume video when tab is visible
            }
        });
    </script>
</body>
</html>