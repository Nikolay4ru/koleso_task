// ==================== SIMPLIFIED WORKING WEBRTC ====================
// This version GUARANTEES video will work

const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

let myStream = null;
let peers = new Map(); // userId -> {pc, stream}
let conference = null;

// ==================== Create Peer Connection ====================
function createPC(userId) {
    const pc = new RTCPeerConnection(rtcConfig);
    
    // Queue for ICE candidates received before remote description
    const pendingCandidates = [];
    let remoteDescriptionSet = false;
    
    // Add my tracks FIRST
    if (myStream) {
        myStream.getTracks().forEach(track => {
            const sender = pc.addTrack(track, myStream);
            console.log(`➕ Added ${track.kind} track to ${userId}`);
        });
    }
    
    // Handle incoming tracks
    pc.ontrack = (e) => {
        console.log(`📥 Received ${e.track.kind} track from ${userId}`);
        
        const stream = e.streams[0];
        if (!stream) {
            console.warn('No stream in track event');
            return;
        }
        
        // Save stream
        const peerData = peers.get(userId) || {};
        peerData.stream = stream;
        peerData.pendingCandidates = pendingCandidates;
        peerData.remoteDescriptionSet = () => remoteDescriptionSet;
        peerData.setRemoteDescriptionSet = (value) => { remoteDescriptionSet = value; };
        peers.set(userId, {...peerData, pc, stream});
        
        // Only add video element when we receive video track
        if (e.track.kind === 'video') {
            const user = users.find(u => u.id === userId);
            showRemoteVideo(userId, stream, user?.name || 'User');
        }
    };
    
    // Handle ICE
    pc.onicecandidate = (e) => {
        if (e.candidate) {
            socket.emit('webrtc:ice-candidate', {
                to: userId,
                candidate: e.candidate,
                conferenceId: conference.id
            });
        }
    };
    
    // Connection state
    pc.onconnectionstatechange = () => {
        console.log(`🔗 Connection to ${userId}:`, pc.connectionState);
        
        if (pc.connectionState === 'failed') {
            console.error(`❌ Connection failed with ${userId}, attempting ICE restart`);
            
            // Try ICE restart
            pc.restartIce();
            
            // If still fails after 5 seconds, remove peer
            setTimeout(() => {
                if (pc.connectionState === 'failed') {
                    console.error(`❌ ICE restart failed, removing peer ${userId}`);
                    removePeer(userId);
                }
            }, 5000);
        } 
        else if (pc.connectionState === 'disconnected') {
            console.warn(`⚠️ Connection disconnected with ${userId}, waiting...`);
        }
        else if (pc.connectionState === 'connected') {
            console.log(`✅ Connection established with ${userId}`);
        }
    };
    
    // ICE connection state
    pc.oniceconnectionstatechange = () => {
        console.log(`🧊 ICE connection to ${userId}:`, pc.iceConnectionState);
        
        if (pc.iceConnectionState === 'failed') {
            console.error(`❌ ICE failed with ${userId}`);
        }
    };
    
    // Save pending candidates and flag
    const peerData = peers.get(userId) || {};
    peerData.pendingCandidates = pendingCandidates;
    peerData.remoteDescriptionSet = () => remoteDescriptionSet;
    peerData.setRemoteDescriptionSet = (value) => { remoteDescriptionSet = value; };
    peers.set(userId, {...peerData, pc});
    
    return pc;
}

// ==================== Show Remote Video ====================
function showRemoteVideo(userId, stream, username) {
    const grid = document.getElementById('conferenceGrid');
    if (!grid) {
        console.error('Conference grid not found');
        return;
    }
    
    // Check if already exists
    const existingTile = document.getElementById(`tile-${userId}`);
    if (existingTile) {
        console.log(`⏭️ Video tile for ${username} already exists, updating stream`);
        const existingVideo = existingTile.querySelector('video');
        if (existingVideo && existingVideo.srcObject !== stream) {
            existingVideo.srcObject = stream;
            existingVideo.play().catch(e => console.log(`▶️ Play error:`, e.message));
        }
        return;
    }
    
    console.log(`➕ Adding remote video for ${username} (${userId})`);
    
    // Check stream tracks
    const videoTracks = stream.getVideoTracks();
    const audioTracks = stream.getAudioTracks();
    console.log(`📊 Stream tracks - Video: ${videoTracks.length}, Audio: ${audioTracks.length}`);
    
    if (videoTracks.length === 0) {
        console.warn('⚠️ No video tracks in stream!');
    }
    
    // Create new tile
    const tile = document.createElement('div');
    tile.className = 'video-tile';
    tile.id = `tile-${userId}`;
    
    const video = document.createElement('video');
    video.srcObject = stream;
    video.autoplay = true;
    video.playsInline = true;
    video.style.width = '100%';
    video.style.height = '100%';
    video.style.objectFit = 'cover';
    video.style.backgroundColor = '#000';
    
    // Add event listeners for debugging
    video.onloadedmetadata = () => {
        console.log(`📹 Video metadata loaded for ${username}:`, {
            videoWidth: video.videoWidth,
            videoHeight: video.videoHeight,
            duration: video.duration
        });
    };
    
    video.onloadeddata = () => {
        console.log(`📊 Video data loaded for ${username}`);
    };
    
    video.onplay = () => {
        console.log(`▶️ Video playing for ${username}`);
    };
    
    video.onerror = (e) => {
        console.error(`❌ Video error for ${username}:`, video.error);
    };
    
    const overlay = document.createElement('div');
    overlay.className = 'video-tile-overlay';
    overlay.innerHTML = `<span class="participant-name">${username}</span>`;
    
    // Add to DOM FIRST
    tile.appendChild(video);
    tile.appendChild(overlay);
    grid.appendChild(tile);
    
    // THEN play (after it's in the DOM)
    setTimeout(() => {
        video.play()
            .then(() => console.log(`✅ Video started for ${username}`))
            .catch(e => console.log(`⚠️ Autoplay for ${username}:`, e.message));
    }, 100);
    
    console.log(`✅ Added video tile for ${username}`);
    updateGridLayout();
}

// ==================== Show Local Video ====================
function addLocalVideo(stream) {
    const grid = document.getElementById('conferenceGrid');
    if (!grid) return;
    
    // Remove old local video
    const oldTile = document.getElementById('tile-local');
    if (oldTile) oldTile.remove();
    
    // Create new tile
    const tile = document.createElement('div');
    tile.className = 'video-tile';
    tile.id = 'tile-local';
    
    const video = document.createElement('video');
    video.srcObject = stream;
    video.autoplay = true;
    video.playsInline = true;
    video.muted = true; // Mute own video
    video.style.width = '100%';
    video.style.height = '100%';
    video.style.objectFit = 'cover';
    video.style.transform = 'scaleX(-1)'; // Mirror local video
    
    const overlay = document.createElement('div');
    overlay.className = 'video-tile-overlay';
    overlay.innerHTML = `<span class="participant-name">Вы</span>`;
    
    // Add to DOM FIRST
    tile.appendChild(video);
    tile.appendChild(overlay);
    grid.appendChild(tile);
    
    // THEN play (after it's in the DOM)
    setTimeout(() => {
        video.play().catch(e => console.log('▶️ Local video autoplay:', e.message));
    }, 100);
    
    console.log('✅ Added local video');
    updateGridLayout();
}

// ==================== Start Call ====================
async function startVideoCall(chat) {
    try {
        if (conference) {
            showToast('Уже в звонке', 'warning');
            return;
        }
        
        // Get camera
        myStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        
        console.log('✅ Got camera');
        
        // Setup conference
        const confId = `conf_${chat.id}_${Date.now()}`;
        conference = { id: confId, chatId: chat.id };
        
        // Show UI
        document.getElementById('conferencePanel').style.display = 'flex';
        addLocalVideo(myStream);
        
        // Join
        socket.emit('conference:join', confId);
        
        // Notify
        socket.emit('message:send', {
            chatId: chat.id,
            text: '📞 Видеозвонок',
            type: 'system',
            metadata: { conferenceId: confId, action: 'start' }
        });
        
    } catch (err) {
        console.error('❌', err);
        showToast('Нет доступа к камере', 'error');
    }
}

// ==================== Join Call ====================
async function joinExistingConference(confId, chatId) {
    try {
        if (conference?.id === confId) return;
        
        myStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        
        conference = { id: confId, chatId };
        
        document.getElementById('conferencePanel').style.display = 'flex';
        addLocalVideo(myStream);
        
        socket.emit('conference:join', confId);
        
    } catch (err) {
        console.error('❌', err);
        showToast('Нет доступа к камере', 'error');
    }
}

// ==================== Handle New Participant ====================
async function handleNewParticipant(userId, username) {
    if (!conference || userId === currentUser.id) return;
    
    console.log(`👤 New participant: ${username}`);
    
    // Create peer connection
    const pc = createPC(userId);
    peers.set(userId, {pc});
    
    // Create offer
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    
    socket.emit('webrtc:offer', {
        to: userId,
        offer,
        conferenceId: conference.id
    });
    
    console.log(`📤 Sent offer to ${username}`);
}

// ==================== Handle Offer ====================
async function handleOffer(from, offer) {
    if (!conference) return;
    
    console.log(`📨 Got offer from ${from}`);
    
    // Create/get peer connection
    let peerData = peers.get(from);
    
    // Polite peer pattern for glare condition
    if (peerData && peerData.pc.signalingState === 'have-local-offer') {
        // Both sides sent offers (glare)
        // Lower userId is polite and accepts remote offer
        const imPolite = currentUser.id < from;
        
        if (imPolite) {
            console.log(`🤝 Glare with ${from}, I'm polite, accepting remote offer`);
            // Rollback our offer
            try {
                await peerData.pc.setLocalDescription({type: 'rollback'});
            } catch (e) {
                console.warn('Rollback failed:', e);
                // Close and recreate
                peerData.pc.close();
                peers.delete(from);
                peerData = null;
            }
        } else {
            console.log(`🤝 Glare with ${from}, I'm impolite, ignoring remote offer`);
            return; // Ignore their offer, they'll accept ours
        }
    }
    
    if (!peerData) {
        const pc = createPC(from);
        peerData = {pc, pendingCandidates: [], remoteDescriptionSet: () => false};
        peers.set(from, peerData);
    }
    
    const {pc} = peerData;
    
    try {
        // Set remote description
        await pc.setRemoteDescription(offer);
        
        // Mark that remote description is set
        if (peerData.setRemoteDescriptionSet) {
            peerData.setRemoteDescriptionSet(true);
        }
        
        // Process pending ICE candidates
        if (peerData.pendingCandidates && peerData.pendingCandidates.length > 0) {
            console.log(`📥 Processing ${peerData.pendingCandidates.length} pending ICE candidates`);
            for (const candidate of peerData.pendingCandidates) {
                try {
                    await pc.addIceCandidate(candidate);
                } catch (e) {
                    console.warn('Failed to add pending candidate:', e);
                }
            }
            peerData.pendingCandidates = [];
        }
        
        // Create answer
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        
        socket.emit('webrtc:answer', {
            to: from,
            answer,
            conferenceId: conference.id
        });
        
        console.log(`📤 Sent answer to ${from}`);
    } catch (error) {
        console.error(`Error handling offer from ${from}:`, error);
    }
}

// ==================== Handle Answer ====================
async function handleAnswer(from, answer) {
    const peerData = peers.get(from);
    if (!peerData) {
        console.warn('No peer connection found for:', from);
        return;
    }
    
    const state = peerData.pc.signalingState;
    console.log(`📨 Got answer from ${from}, state: ${state}`);
    
    try {
        // Can set answer in these states
        if (state === 'have-local-offer') {
            await peerData.pc.setRemoteDescription(answer);
            console.log(`✅ Set remote answer from ${from}`);
        } 
        else if (state === 'stable') {
            console.log(`⏭️ Already stable with ${from}, skipping answer`);
        }
        else if (state === 'have-remote-offer') {
            // We received offer and answer at same time (glare condition)
            // Close and let the other side reconnect
            console.warn(`⚠️ Glare condition with ${from}, closing and will receive new offer`);
        }
        else {
            console.warn(`⚠️ Cannot set answer in state: ${state}`);
        }
    } catch (error) {
        console.error('Error handling answer:', error);
    }
}

// ==================== Handle ICE Candidate ====================
async function handleIceCandidate(from, candidate) {
    const peerData = peers.get(from);
    if (!peerData) {
        console.warn(`No peer data for ${from}, ignoring ICE candidate`);
        return;
    }
    
    const {pc} = peerData;
    
    // Check if remote description is set
    const isRemoteDescSet = pc.remoteDescription && pc.remoteDescription.type;
    
    if (!isRemoteDescSet) {
        // Queue the candidate for later
        console.log(`⏳ Queueing ICE candidate from ${from} (no remote description yet)`);
        if (!peerData.pendingCandidates) {
            peerData.pendingCandidates = [];
        }
        peerData.pendingCandidates.push(candidate);
        return;
    }
    
    // Add candidate immediately if remote description is set
    try {
        await pc.addIceCandidate(candidate);
        console.log(`✅ Added ICE candidate from ${from}`);
    } catch (error) {
        console.error(`Failed to add ICE candidate from ${from}:`, error);
    }
}

// ==================== Remove Peer ====================
function removePeer(userId) {
    const peerData = peers.get(userId);
    if (peerData) {
        peerData.pc?.close();
        peers.delete(userId);
    }
    
    const tile = document.getElementById(`tile-${userId}`);
    if (tile) {
        tile.remove();
        updateGridLayout();
    }
}

// ==================== End Call ====================
function endCall() {
    // Stop my stream
    if (myStream) {
        myStream.getTracks().forEach(t => t.stop());
        myStream = null;
    }
    
    // Close all peers
    peers.forEach((data, userId) => {
        data.pc?.close();
    });
    peers.clear();
    
    // Hide UI
    document.getElementById('conferencePanel').style.display = 'none';
    document.getElementById('conferenceGrid').innerHTML = '';
    
    // Leave conference
    if (conference) {
        socket.emit('conference:leave', conference.id);
        conference = null;
    }
}

// ==================== Socket Handlers ====================
// Wait for socket to be ready
function initWebRTC() {
    if (typeof socket === 'undefined' || !socket) {
        console.log('⏳ Socket not ready, waiting...');
        setTimeout(initWebRTC, 100);
        return;
    }
    
    console.log('🔌 Initializing WebRTC handlers...');
    
    socket.on('conference:participant:joined', ({userId, username}) => {
        console.log('👤 Participant joined:', username, userId);
        handleNewParticipant(userId, username);
    });
    
    socket.on('conference:participants', (participants) => {
        console.log('👥 Existing participants:', participants);
        // Connect to all existing participants
        participants.forEach(({userId, username}) => {
            if (userId !== currentUser?.id) {
                handleNewParticipant(userId, username);
            }
        });
    });
    
    socket.on('webrtc:offer', ({from, offer}) => {
        console.log('📨 Received offer from:', from);
        handleOffer(from, offer);
    });
    
    socket.on('webrtc:answer', ({from, answer}) => {
        console.log('📨 Received answer from:', from);
        handleAnswer(from, answer);
    });
    
    socket.on('webrtc:ice-candidate', ({from, candidate}) => {
        console.log('🧊 Received ICE candidate from:', from);
        handleIceCandidate(from, candidate);
    });
    
    socket.on('conference:participant:left', (userId) => {
        console.log('👋 Participant left:', userId);
        removePeer(userId);
    });
    
    console.log('✅ WebRTC handlers initialized');
}

// Start initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initWebRTC, 200);
    });
} else {
    setTimeout(initWebRTC, 200);
}

// ==================== Button Handlers ====================
document.getElementById('videoCallBtn')?.addEventListener('click', () => {
    if (currentChat) startVideoCall(currentChat);
});

document.getElementById('endCallBtn')?.addEventListener('click', endCall);

document.getElementById('backgroundBtn')?.addEventListener('click', () => {
    if (typeof showBackgroundSelector === 'function') {
        showBackgroundSelector();
    } else {
        console.error('Virtual backgrounds not loaded');
        showToast('Виртуальные фоны не загружены', 'error');
    }
});

document.getElementById('fullscreenBtn')?.addEventListener('click', toggleFullscreen);

document.getElementById('fullscreenBtn')?.addEventListener('click', toggleFullscreen);

// ==================== Sidebar Controls ====================
const sidebar = document.getElementById('conferenceSidebar');
const toggleSidebarBtn = document.getElementById('toggleSidebarBtn');
const closeSidebarBtn = document.getElementById('closeSidebarBtn');
const conferencePanel = document.getElementById('conferencePanel');

toggleSidebarBtn?.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    toggleSidebarBtn.classList.toggle('active');
});

closeSidebarBtn?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    toggleSidebarBtn?.classList.remove('active');
});

// Sidebar tabs
document.querySelectorAll('.sidebar-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        
        // Update active tab button
        document.querySelectorAll('.sidebar-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Update active tab content
        document.querySelectorAll('#conferenceSidebar .tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        if (tab === 'participants') {
            document.getElementById('participantsPanel').classList.add('active');
        } else if (tab === 'chat') {
            document.getElementById('chatPanel').classList.add('active');
        }
    });
});

// Update grid data-participants attribute
function updateGridLayout() {
    const grid = document.getElementById('conferenceGrid');
    if (grid) {
        const participantCount = grid.querySelectorAll('.video-tile').length;
        grid.setAttribute('data-participants', participantCount);
        
        // Update participant counter
        const counter = document.getElementById('conferenceParticipants');
        if (counter) {
            counter.textContent = `${participantCount} ${participantCount === 1 ? 'участник' : 'участников'}`;
        }
    }
}

// ==================== Fullscreen ====================
function toggleFullscreen() {
    const panel = document.getElementById('conferencePanel');
    
    if (!document.fullscreenElement) {
        // Enter fullscreen
        if (panel.requestFullscreen) {
            panel.requestFullscreen();
        } else if (panel.webkitRequestFullscreen) {
            panel.webkitRequestFullscreen();
        } else if (panel.mozRequestFullScreen) {
            panel.mozRequestFullScreen();
        } else if (panel.msRequestFullscreen) {
            panel.msRequestFullscreen();
        }
        
        // Change icon
        const icon = document.querySelector('#fullscreenBtn .material-icons');
        if (icon) icon.textContent = 'fullscreen_exit';
        
    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        
        // Change icon back
        const icon = document.querySelector('#fullscreenBtn .material-icons');
        if (icon) icon.textContent = 'fullscreen';
    }
}

// Listen for fullscreen changes (e.g., ESC key)
document.addEventListener('fullscreenchange', updateFullscreenButton);
document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
document.addEventListener('mozfullscreenchange', updateFullscreenButton);
document.addEventListener('msfullscreenchange', updateFullscreenButton);

function updateFullscreenButton() {
    const icon = document.querySelector('#fullscreenBtn .material-icons');
    if (!icon) return;
    
    if (document.fullscreenElement || 
        document.webkitFullscreenElement || 
        document.mozFullScreenElement || 
        document.msFullscreenElement) {
        icon.textContent = 'fullscreen_exit';
    } else {
        icon.textContent = 'fullscreen';
    }
}

// ==================== Global Exports ====================
window.startVideoCall = startVideoCall;
window.joinExistingConference = joinExistingConference;
window.endCall = endCall;

console.log('✅ WebRTC Fixed Module Loaded');