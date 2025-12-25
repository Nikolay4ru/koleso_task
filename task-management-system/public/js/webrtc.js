// ==================== WebRTC Configuration ====================
const configuration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' }
    ],
    iceCandidatePoolSize: 10
};

// ==================== Global Conference State ====================
let localStream = null;
let localScreenStream = null;
let peerConnections = new Map(); // userId -> RTCPeerConnection
let remoteStreams = new Map(); // userId -> MediaStream
let currentConference = null;
let isMuted = false;
let isCameraOff = false;
let isScreenSharing = false;

// ==================== Media Constraints ====================
const videoConstraints = {
    video: {
        width: { ideal: 1280, max: 1920 },
        height: { ideal: 720, max: 1080 },
        frameRate: { ideal: 30, max: 30 }
    },
    audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true
    }
};

const screenConstraints = {
    video: {
        cursor: 'always',
        displaySurface: 'monitor'
    },
    audio: false
};

// ==================== Conference Management ====================
async function startVideoCall(chat) {
    try {
        // Проверка на существующую конференцию
        if (currentConference) {
            showToast('Вы уже в конференции. Завершите текущую для начала новой.', 'warning');
            return;
        }
        
        // Request media permissions
        localStream = await navigator.mediaDevices.getUserMedia(videoConstraints);
        
        // Create conference
        const conferenceId = `conf_${chat.id}_${Date.now()}`;
        currentConference = {
            id: conferenceId,
            chatId: chat.id,
            participants: new Set([currentUser.id]),
            startedAt: new Date()
        };
        
        // Show conference UI
        showConferenceUI();
        
        // Add local video
        addLocalVideo(localStream);
        
        // Join conference room
        socket.emit('conference:join', conferenceId);
        
        // Notify chat participants
        socket.emit('message:send', {
            chatId: chat.id,
            text: '📞 Видеоконференция началась',
            type: 'system',
            metadata: {
                conferenceId,
                action: 'start'
            }
        });
        
    } catch (error) {
        console.error('Error starting video call:', error);
        showToast('Не удалось получить доступ к камере/микрофону', 'error');
    }
}

async function startAudioCall(chat) {
    try {
        // Проверка на существующую конференцию
        if (currentConference) {
            showToast('Вы уже в конференции. Завершите текущую для начала новой.', 'warning');
            return;
        }
        
        // Request audio only
        localStream = await navigator.mediaDevices.getUserMedia({
            video: false,
            audio: videoConstraints.audio
        });
        
        const conferenceId = `conf_${chat.id}_${Date.now()}`;
        currentConference = {
            id: conferenceId,
            chatId: chat.id,
            participants: new Set([currentUser.id]),
            startedAt: new Date(),
            audioOnly: true
        };
        
        showConferenceUI();
        addLocalVideo(localStream, true);
        
        socket.emit('conference:join', conferenceId);
        
        socket.emit('message:send', {
            chatId: chat.id,
            text: '📞 Аудиозвонок начался',
            type: 'system',
            metadata: {
                conferenceId,
                action: 'start'
            }
        });
        
    } catch (error) {
        console.error('Error starting audio call:', error);
        showToast('Не удалось получить доступ к микрофону', 'error');
    }
}

async function joinExistingConference(conferenceId, chatId) {
    try {
        console.log(`Joining existing conference: ${conferenceId}`);
        
        // Проверка на существующую конференцию
        if (currentConference && currentConference.id === conferenceId) {
            console.log('Already in this conference, skipping join');
            return;
        }
        
        if (currentConference && currentConference.id !== conferenceId) {
            showToast('Вы уже в другой конференции. Завершите текущую.', 'warning');
            return;
        }
        
        // Request media permissions
        localStream = await navigator.mediaDevices.getUserMedia(videoConstraints);
        
        currentConference = {
            id: conferenceId,
            chatId: chatId,
            participants: new Set([currentUser.id]),
            startedAt: new Date()
        };
        
        showConferenceUI();
        addLocalVideo(localStream);
        
        // Join conference room
        socket.emit('conference:join', conferenceId);
        
        console.log(`Joined conference: ${conferenceId}`);
        
    } catch (error) {
        console.error('Error joining conference:', error);
        showToast('Не удалось присоединиться к конференции', 'error');
    }
}


function showConferenceUI() {
    document.getElementById('conferencePanel').style.display = 'flex';
    
    // Update conference info
    const chat = chats.find(c => c.id === currentConference.chatId);
    const chatName = chat?.name || 'Конференция';
    
    document.getElementById('conferenceName').textContent = chatName;
    updateParticipantCount();
}

function hideConferenceUI() {
    document.getElementById('conferencePanel').style.display = 'none';
    document.getElementById('conferenceGrid').innerHTML = '';
}

function updateParticipantCount() {
    const count = currentConference ? currentConference.participants.size : 0;
    document.getElementById('conferenceParticipants').textContent = 
        `${count} ${count === 1 ? 'участник' : count < 5 ? 'участника' : 'участников'}`;
}

// ==================== Video Tiles ====================
function addLocalVideo(stream, audioOnly = false) {
    const grid = document.getElementById('conferenceGrid');
    
    const tile = document.createElement('div');
    tile.className = 'video-tile';
    tile.id = `video-tile-local`;
    
    if (audioOnly) {
        tile.innerHTML = `
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea, #764ba2);">
                <div style="text-align: center; color: white;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <span class="material-icons" style="font-size: 40px;">person</span>
                    </div>
                    <div style="font-size: 18px; font-weight: 600;">${currentUser.name}</div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 4px;">Вы</div>
                </div>
            </div>
            <div class="video-tile-overlay">
                <span class="participant-name">${currentUser.name} (Вы)</span>
                <div class="participant-status">
                    <span class="status-icon">
                        <span class="material-icons">${isMuted ? 'mic_off' : 'mic'}</span>
                    </span>
                </div>
            </div>
        `;
    } else {
        const video = document.createElement('video');
        video.srcObject = stream;
        video.autoplay = true;
        video.muted = true;
        video.playsInline = true;
        
        tile.innerHTML = `
            <div class="video-tile-overlay">
                <span class="participant-name">${currentUser.name} (Вы)</span>
                <div class="participant-status">
                    <span class="status-icon">
                        <span class="material-icons">${isMuted ? 'mic_off' : 'mic'}</span>
                    </span>
                    <span class="status-icon">
                        <span class="material-icons">${isCameraOff ? 'videocam_off' : 'videocam'}</span>
                    </span>
                </div>
            </div>
        `;
        tile.insertBefore(video, tile.firstChild);
    }
    
    grid.appendChild(tile);
    adjustGridLayout();
}

function addRemoteVideo(userId, stream, username) {
    console.log(`Adding remote video for ${username} (${userId})`, stream);
    
    const grid = document.getElementById('conferenceGrid');
    if (!grid) {
        console.error('Conference grid not found');
        return;
    }
    
    // Remove existing tile if any
    const existingTile = document.getElementById(`video-tile-${userId}`);
    if (existingTile) {
        console.log(`Removing existing tile for ${userId}`);
        existingTile.remove();
    }
    
    const tile = document.createElement('div');
    tile.className = 'video-tile';
    tile.id = `video-tile-${userId}`;
    
    const videoTracks = stream.getVideoTracks();
    const audioTracks = stream.getAudioTracks();
    const hasVideo = videoTracks.length > 0 && videoTracks[0].enabled;
    const hasAudio = audioTracks.length > 0;
    
    console.log(`Stream info for ${username}:`, {
        videoTracks: videoTracks.length,
        audioTracks: audioTracks.length,
        hasVideo,
        hasAudio,
        streamId: stream.id,
        streamActive: stream.active
    });
    
    if (!hasVideo) {
        // Audio only - show avatar
        tile.innerHTML = `
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f093fb, #f5576c);">
                <div style="text-align: center; color: white;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <span class="material-icons" style="font-size: 40px;">person</span>
                    </div>
                    <div style="font-size: 18px; font-weight: 600;">${username}</div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 8px;">Камера выключена</div>
                </div>
            </div>
            <div class="video-tile-overlay">
                <span class="participant-name">${username}</span>
                <div class="participant-status">
                    <span class="status-icon">
                        <span class="material-icons">${hasAudio ? 'mic' : 'mic_off'}</span>
                    </span>
                </div>
            </div>
        `;
    } else {
        // Create video element with all necessary attributes
        const video = document.createElement('video');
        video.id = `video-${userId}`;
        video.srcObject = stream;
        video.autoplay = true;
        video.playsInline = true;
        video.muted = false; // Don't mute remote videos
        
        // Explicit styles
        video.style.width = '100%';
        video.style.height = '100%';
        video.style.objectFit = 'cover';
        video.style.background = '#000';
        video.style.display = 'block';
        
        // Force play with user interaction fallback
        const playVideo = async () => {
            try {
                await video.play();
                console.log(`✅ Video playing for ${username}`);
            } catch (error) {
                console.warn(`Auto-play blocked for ${username}:`, error.message);
                // Add click to play indicator
                const playBtn = document.createElement('div');
                playBtn.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.7); color: white; padding: 12px 24px; border-radius: 8px; cursor: pointer; z-index: 100;';
                playBtn.innerHTML = '<span class="material-icons" style="vertical-align: middle;">play_arrow</span> Нажмите для воспроизведения';
                playBtn.onclick = async () => {
                    try {
                        await video.play();
                        playBtn.remove();
                    } catch (e) {
                        console.error('Manual play failed:', e);
                    }
                };
                tile.appendChild(playBtn);
            }
        };
        
        // Event listeners for debugging and ensuring playback
        video.onloadedmetadata = () => {
            console.log(`📹 Video metadata loaded for ${username}:`, {
                videoWidth: video.videoWidth,
                videoHeight: video.videoHeight,
                duration: video.duration,
                readyState: video.readyState
            });
            playVideo();
        };
        
        video.onloadeddata = () => {
            console.log(`📊 Video data loaded for ${username}`);
        };
        
        video.onplay = () => {
            console.log(`▶️ Video playing for ${username}`);
        };
        
        video.onpause = () => {
            console.warn(`⏸️ Video paused for ${username}`);
            // Try to resume
            video.play().catch(e => console.error('Resume failed:', e));
        };
        
        video.onerror = (e) => {
            console.error(`❌ Video error for ${username}:`, video.error);
        };
        
        // Create tile with overlay
        tile.innerHTML = `
            <div class="video-tile-overlay">
                <span class="participant-name">${username}</span>
                <div class="participant-status">
                    <span class="status-icon">
                        <span class="material-icons">${hasAudio ? 'mic' : 'mic_off'}</span>
                    </span>
                    <span class="status-icon">
                        <span class="material-icons">videocam</span>
                    </span>
                </div>
            </div>
        `;
        tile.insertBefore(video, tile.firstChild);
        
        console.log(`✅ Video element created for ${username}`, video);
        
        // Ensure video plays after a short delay
        setTimeout(() => {
            if (video.paused) {
                console.log(`Video still paused for ${username}, attempting play...`);
                playVideo();
            }
        }, 100);
    }
    
    grid.appendChild(tile);
    adjustGridLayout();
    
    console.log(`📊 Total participants in grid: ${grid.children.length}`);
}

function removeVideoTile(userId) {
    const tile = document.getElementById(`video-tile-${userId}`);
    if (tile) {
        tile.remove();
        adjustGridLayout();
    }
}

function adjustGridLayout() {
    const grid = document.getElementById('conferenceGrid');
    const tileCount = grid.children.length;
    
    let columns;
    if (tileCount <= 1) columns = 1;
    else if (tileCount <= 4) columns = 2;
    else if (tileCount <= 9) columns = 3;
    else if (tileCount <= 16) columns = 4;
    else if (tileCount <= 25) columns = 5;
    else columns = 6;
    
    grid.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
}

// ==================== WebRTC Peer Connections ====================
async function createPeerConnection(userId) {
    const pc = new RTCPeerConnection(configuration);
    peerConnections.set(userId, pc);
    
    // Add local tracks
    if (localStream) {
        localStream.getTracks().forEach(track => {
            pc.addTrack(track, localStream);
        });
    }
    
    // Handle ICE candidates
    pc.onicecandidate = (event) => {
        if (event.candidate) {
            socket.emit('webrtc:ice-candidate', {
                to: userId,
                candidate: event.candidate,
                conferenceId: currentConference.id
            });
        }
    };
    
    // Handle remote stream - ждем все треки перед отображением
    const receivedTracks = new Set();
    pc.ontrack = (event) => {
        console.log(`Track received from ${userId}:`, event.track.kind);
        
        const stream = event.streams[0];
        if (!stream) {
            console.warn('No stream in track event');
            return;
        }
        
        // Добавляем трек в набор
        receivedTracks.add(event.track.kind);
        
        // Сохраняем stream
        remoteStreams.set(userId, stream);
        
        // Проверяем сколько треков ожидается
        const expectedTracks = localStream ? localStream.getTracks().length : 2;
        
        console.log(`Tracks received: ${receivedTracks.size}/${expectedTracks}`, Array.from(receivedTracks));
        
        // Ждем все треки или таймаут 1 секунда
        const checkAndAddVideo = () => {
            const user = users.find(u => u.id === userId);
            const username = user?.name || 'Unknown';
            
            // Проверяем что stream имеет треки
            const videoTracks = stream.getVideoTracks();
            const audioTracks = stream.getAudioTracks();
            
            console.log(`Stream ready check - Video: ${videoTracks.length}, Audio: ${audioTracks.length}`);
            
            // Добавляем видео только если есть хотя бы один трек
            if (videoTracks.length > 0 || audioTracks.length > 0) {
                addRemoteVideo(userId, stream, username);
            }
        };
        
        // Если получили оба трека - сразу добавляем
        if (receivedTracks.size >= expectedTracks) {
            checkAndAddVideo();
        } else {
            // Иначе ждем 500ms для второго трека
            setTimeout(() => {
                if (!document.getElementById(`video-tile-${userId}`)) {
                    checkAndAddVideo();
                }
            }, 500);
        }
    };
    
    // Handle connection state
    pc.onconnectionstatechange = () => {
        console.log(`Connection state with ${userId}:`, pc.connectionState);
        
        if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
            handlePeerDisconnect(userId);
        }
    };
    
    return pc;
}

async function handleNewParticipant(userId, username) {
    if (!currentConference || userId === currentUser.id) return;
    
    currentConference.participants.add(userId);
    updateParticipantCount();
    
    // Create peer connection and send offer
    const pc = await createPeerConnection(userId);
    
    try {
        const offer = await pc.createOffer({
            offerToReceiveAudio: true,
            offerToReceiveVideo: true
        });
        
        await pc.setLocalDescription(offer);
        
        socket.emit('webrtc:offer', {
            to: userId,
            offer: offer,
            conferenceId: currentConference.id
        });
        
    } catch (error) {
        console.error('Error creating offer:', error);
    }
}

async function handleOffer(from, offer) {
    if (!currentConference) {
        console.warn('Received offer but not in conference');
        return;
    }
    
    let pc = peerConnections.get(from);
    
    // If we already have a stable connection, ignore the offer (already connected)
    if (pc && pc.connectionState === 'connected') {
        console.log(`Already connected to ${from}, ignoring offer`);
        return;
    }
    
    // If peer connection exists in negotiating state, wait
    if (pc && (pc.signalingState === 'have-local-offer' || pc.signalingState === 'have-remote-offer')) {
        console.log(`Connection to ${from} is negotiating (${pc.signalingState}), closing and recreating`);
        pc.close();
        peerConnections.delete(from);
        pc = null;
    }
    
    // Create new peer connection if needed
    if (!pc) {
        pc = await createPeerConnection(from);
    }
    
    try {
        console.log(`Setting remote offer from ${from}, current state: ${pc.signalingState}`);
        await pc.setRemoteDescription(new RTCSessionDescription(offer));
        console.log(`Set remote offer from ${from}`);
        
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        console.log(`Created answer for ${from}`);
        
        socket.emit('webrtc:answer', {
            to: from,
            answer: answer,
            conferenceId: currentConference.id
        });
        
    } catch (error) {
        console.error('Error handling offer:', error);
        // Try to recover by closing connection
        if (pc) {
            pc.close();
            peerConnections.delete(from);
        }
    }
}

async function handleAnswer(from, answer) {
    const pc = peerConnections.get(from);
    if (!pc) {
        console.warn('No peer connection found for:', from);
        return;
    }
    
    try {
        console.log(`Received answer from ${from}, current state: ${pc.signalingState}`);
        
        // Проверяем состояние
        if (pc.signalingState === 'have-local-offer') {
            await pc.setRemoteDescription(new RTCSessionDescription(answer));
            console.log(`Set remote answer from ${from}`);
        } else if (pc.signalingState === 'stable') {
            // Если уже stable, возможно answer пришел поздно - игнорируем
            console.log(`Connection already stable with ${from}, ignoring late answer`);
        } else {
            console.warn(`Cannot set remote answer, wrong state: ${pc.signalingState}`);
        }
    } catch (error) {
        console.error('Error handling answer:', error);
    }
}

async function handleIceCandidate(from, candidate) {
    const pc = peerConnections.get(from);
    if (!pc) return;
    
    try {
        await pc.addIceCandidate(new RTCIceCandidate(candidate));
    } catch (error) {
        console.error('Error adding ICE candidate:', error);
    }
}

function handlePeerDisconnect(userId) {
    // Close peer connection
    const pc = peerConnections.get(userId);
    if (pc) {
        pc.close();
        peerConnections.delete(userId);
    }
    
    // Remove remote stream
    remoteStreams.delete(userId);
    
    // Remove video tile
    removeVideoTile(userId);
    
    // Update participants
    if (currentConference) {
        currentConference.participants.delete(userId);
        updateParticipantCount();
    }
}

// ==================== Media Controls ====================
function toggleMute() {
    if (!localStream) return;
    
    const audioTracks = localStream.getAudioTracks();
    audioTracks.forEach(track => {
        track.enabled = !track.enabled;
    });
    
    isMuted = !audioTracks[0].enabled;
    
    const btn = document.getElementById('toggleMicBtn');
    const icon = btn.querySelector('.material-icons');
    icon.textContent = isMuted ? 'mic_off' : 'mic';
    btn.classList.toggle('active', !isMuted);
    
    // Update local tile
    updateLocalTileStatus();
}

function toggleCamera() {
    if (!localStream) return;
    
    const videoTracks = localStream.getVideoTracks();
    videoTracks.forEach(track => {
        track.enabled = !track.enabled;
    });
    
    isCameraOff = !videoTracks[0].enabled;
    
    const btn = document.getElementById('toggleCameraBtn');
    const icon = btn.querySelector('.material-icons');
    icon.textContent = isCameraOff ? 'videocam_off' : 'videocam';
    btn.classList.toggle('active', !isCameraOff);
    
    updateLocalTileStatus();
}

async function toggleScreenShare() {
    if (isScreenSharing) {
        stopScreenShare();
    } else {
        await startScreenShare();
    }
}

async function startScreenShare() {
    try {
        localScreenStream = await navigator.mediaDevices.getDisplayMedia(screenConstraints);
        
        // Replace video track in all peer connections
        const screenTrack = localScreenStream.getVideoTracks()[0];
        
        peerConnections.forEach((pc) => {
            const sender = pc.getSenders().find(s => s.track?.kind === 'video');
            if (sender) {
                sender.replaceTrack(screenTrack);
            }
        });
        
        // Update local video
        const localVideo = document.querySelector('#video-tile-local video');
        if (localVideo) {
            localVideo.srcObject = localScreenStream;
        }
        
        // Handle screen share stop
        screenTrack.onended = () => {
            stopScreenShare();
        };
        
        isScreenSharing = true;
        const btn = document.getElementById('shareScreenBtn');
        btn.classList.add('active');
        
        showToast('Демонстрация экрана началась');
        
    } catch (error) {
        console.error('Error sharing screen:', error);
        showToast('Не удалось начать демонстрацию экрана', 'error');
    }
}

function stopScreenShare() {
    if (!localScreenStream) return;
    
    localScreenStream.getTracks().forEach(track => track.stop());
    
    // Replace back to camera
    const cameraTrack = localStream.getVideoTracks()[0];
    
    peerConnections.forEach((pc) => {
        const sender = pc.getSenders().find(s => s.track?.kind === 'video');
        if (sender && cameraTrack) {
            sender.replaceTrack(cameraTrack);
        }
    });
    
    // Update local video
    const localVideo = document.querySelector('#video-tile-local video');
    if (localVideo) {
        localVideo.srcObject = localStream;
    }
    
    localScreenStream = null;
    isScreenSharing = false;
    
    const btn = document.getElementById('shareScreenBtn');
    btn.classList.remove('active');
    
    showToast('Демонстрация экрана остановлена');
}

function updateLocalTileStatus() {
    const tile = document.getElementById('video-tile-local');
    if (!tile) return;
    
    const overlay = tile.querySelector('.video-tile-overlay');
    overlay.innerHTML = `
        <span class="participant-name">${currentUser.name} (Вы)</span>
        <div class="participant-status">
            <span class="status-icon">
                <span class="material-icons">${isMuted ? 'mic_off' : 'mic'}</span>
            </span>
            ${localStream.getVideoTracks().length > 0 ? `
                <span class="status-icon">
                    <span class="material-icons">${isCameraOff ? 'videocam_off' : 'videocam'}</span>
                </span>
            ` : ''}
        </div>
    `;
}

function endCall() {
    if (!currentConference) return;
    
    // Stop all tracks
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    if (localScreenStream) {
        localScreenStream.getTracks().forEach(track => track.stop());
        localScreenStream = null;
    }
    
    // Close all peer connections
    peerConnections.forEach(pc => pc.close());
    peerConnections.clear();
    remoteStreams.clear();
    
    // Leave conference
    socket.emit('conference:leave', currentConference.id);
    
    // Notify chat
    socket.emit('message:send', {
        chatId: currentConference.chatId,
        text: '📞 Конференция завершена',
        type: 'system',
        metadata: {
            conferenceId: currentConference.id,
            action: 'end'
        }
    });
    
    // Reset state
    currentConference = null;
    isMuted = false;
    isCameraOff = false;
    isScreenSharing = false;
    
    // Hide UI
    hideConferenceUI();
    
    showToast('Звонок завершен');
}

// ==================== Socket Event Handlers ====================
function setupWebRTCSocketHandlers() {
    if (!socket) {
        console.warn('Socket not initialized yet, will setup handlers later');
        return;
    }
    
    socket.on('conference:participant:joined', (data) => {
        handleNewParticipant(data.userId, data.username);
        showToast(`${data.username} присоединился к конференции`);
    });
    
    socket.on('conference:participant:left', (data) => {
        handlePeerDisconnect(data.userId);
        const user = users.find(u => u.id === data.userId);
        showToast(`${user?.name || 'Участник'} покинул конференцию`);
    });
    
    socket.on('conference:participants', (participants) => {
        participants.forEach(participant => {
            handleNewParticipant(participant.userId, participant.username);
        });
    });
    
    socket.on('webrtc:offer', (data) => {
        handleOffer(data.from, data.offer);
    });
    
    socket.on('webrtc:answer', (data) => {
        handleAnswer(data.from, data.answer);
    });
    
    socket.on('webrtc:ice-candidate', (data) => {
        handleIceCandidate(data.from, data.candidate);
    });
}

// Setup handlers when socket is ready
if (typeof socket !== 'undefined' && socket) {
    setupWebRTCSocketHandlers();
}

// ==================== UI Event Listeners ====================
document.addEventListener('DOMContentLoaded', () => {
    // Control buttons
    document.getElementById('toggleMicBtn')?.addEventListener('click', toggleMute);
    document.getElementById('toggleCameraBtn')?.addEventListener('click', toggleCamera);
    document.getElementById('shareScreenBtn')?.addEventListener('click', toggleScreenShare);
    document.getElementById('endCallBtn')?.addEventListener('click', endCall);
});

// ==================== Export Functions ====================
window.startVideoCall = startVideoCall;
window.startAudioCall = startAudioCall;
window.joinExistingConference = joinExistingConference;
window.endCall = endCall;
window.setupWebRTCSocketHandlers = setupWebRTCSocketHandlers;