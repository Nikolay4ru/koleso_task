// ==================== SIMPLE VIRTUAL BACKGROUNDS ====================
// Simplified version without MediaPipe - using blur filter only

class SimpleVirtualBackground {
    constructor() {
        this.enabled = false;
        this.blurAmount = 20;
        this.canvas = null;
        this.ctx = null;
        this.processedStream = null;
        this.animationId = null;
    }
    
    // Apply blur background to stream
    async applyBlur(inputStream, blurAmount = 20) {
        this.blurAmount = blurAmount;
        this.enabled = true; // Enable BEFORE processing
        
        // Create canvas
        const videoTrack = inputStream.getVideoTracks()[0];
        const settings = videoTrack.getSettings();
        
        this.canvas = document.createElement('canvas');
        this.canvas.width = settings.width || 1280;
        this.canvas.height = settings.height || 720;
        this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
        
        // Create video element for input
        const video = document.createElement('video');
        video.srcObject = inputStream;
        video.autoplay = true;
        video.playsInline = true;
        video.muted = true;
        
        // Wait for video to be ready
        return new Promise((resolve) => {
            video.onloadedmetadata = () => {
                console.log('📹 Video ready for blur processing');
                
                // Start processing FIRST
                this.processVideo(video);
                
                // Get stream from canvas
                const canvasStream = this.canvas.captureStream(30);
                
                // Add audio track from original stream
                const audioTracks = inputStream.getAudioTracks();
                audioTracks.forEach(track => canvasStream.addTrack(track));
                
                this.processedStream = canvasStream;
                
                console.log('✅ Blur background applied:', {
                    blurAmount: this.blurAmount,
                    videoTracks: canvasStream.getVideoTracks().length,
                    audioTracks: canvasStream.getAudioTracks().length
                });
                
                resolve(canvasStream);
            };
            
            video.onerror = (err) => {
                console.error('❌ Video error:', err);
                this.enabled = false;
                resolve(inputStream); // Fallback to original
            };
        });
    }
    
    // Process each frame
    processVideo(video) {
        if (!this.enabled) return;
        
        const {width, height} = this.canvas;
        
        // Apply blur filter and draw
        this.ctx.filter = `blur(${this.blurAmount}px)`;
        this.ctx.drawImage(video, 0, 0, width, height);
        
        // Remove filter for next frame
        this.ctx.filter = 'none';
        
        // Continue processing
        this.animationId = requestAnimationFrame(() => this.processVideo(video));
    }
    
    // Disable virtual background
    disable() {
        this.enabled = false;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
        this.processedStream = null;
    }
}

// ==================== Предустановленные фоны ====================
const PRESET_BACKGROUNDS = [
    {
        id: 'none',
        name: 'Без фона',
        type: 'none',
        icon: '❌'
    },
    {
        id: 'blur-light',
        name: 'Лёгкое размытие',
        type: 'blur',
        amount: 10,
        icon: '🌫️'
    },
    {
        id: 'blur-medium',
        name: 'Среднее размытие',
        type: 'blur',
        amount: 20,
        icon: '🌫️'
    },
    {
        id: 'blur-strong',
        name: 'Сильное размытие',
        type: 'blur',
        amount: 30,
        icon: '🌫️'
    }
];

// ==================== UI для выбора фона ====================
function showBackgroundSelector() {
    // Create modal if doesn't exist
    let modal = document.getElementById('backgroundSelectorModal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'backgroundSelectorModal';
        modal.className = 'modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        `;
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 16px; padding: 32px; max-width: 500px; width: 90%;">
            <h2 style="margin: 0 0 24px 0; font-size: 24px; color: #1a1a1a;">Виртуальный фон</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                ${PRESET_BACKGROUNDS.map(bg => `
                    <div class="background-option" data-bg-id="${bg.id}" style="
                        cursor: pointer;
                        border: 2px solid #e0e0e0;
                        border-radius: 12px;
                        padding: 20px;
                        text-align: center;
                        transition: all 0.2s;
                        background: white;
                    " onmouseover="this.style.borderColor='#0088cc'; this.style.backgroundColor='#f0f8ff'" 
                       onmouseout="this.style.borderColor='#e0e0e0'; this.style.backgroundColor='white'">
                        <div style="font-size: 48px; margin-bottom: 12px;">
                            ${bg.icon}
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: #333;">${bg.name}</div>
                    </div>
                `).join('')}
            </div>
            <button onclick="closeBackgroundSelector()" style="
                width: 100%;
                padding: 12px 24px;
                background: #0088cc;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            " onmouseover="this.style.background='#0077b3'" onmouseout="this.style.background='#0088cc'">
                Закрыть
            </button>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Add event listeners
    document.querySelectorAll('.background-option[data-bg-id]').forEach(option => {
        option.addEventListener('click', async () => {
            const bgId = option.dataset.bgId;
            if (bgId) {
                await applyBackground(bgId);
                closeBackgroundSelector();
            }
        });
    });
}

function closeBackgroundSelector() {
    const modal = document.getElementById('backgroundSelectorModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ==================== Применить фон ====================
async function applyBackground(bgId) {
    const bg = PRESET_BACKGROUNDS.find(b => b.id === bgId);
    if (!bg) return;
    
    // Get or create virtual background instance
    const vb = window.virtualBackground || new SimpleVirtualBackground();
    window.virtualBackground = vb;
    
    if (bg.type === 'none') {
        // Disable virtual background
        vb.disable();
        
        // Switch back to original stream
        if (myStream) {
            // Re-add tracks to peer connections
            peers.forEach(({pc}) => {
                const senders = pc.getSenders();
                myStream.getVideoTracks().forEach(track => {
                    const sender = senders.find(s => s.track?.kind === 'video');
                    if (sender) {
                        sender.replaceTrack(track);
                    }
                });
            });
            
            // Update local video
            const localVideo = document.querySelector('#tile-local video');
            if (localVideo) {
                localVideo.srcObject = myStream;
            }
        }
        
        if (typeof showToast === 'function') {
            showToast('Фон отключен', 'success');
        }
        
    } else if (bg.type === 'blur') {
        // Apply blur
        try {
            if (!myStream) {
                if (typeof showToast === 'function') {
                    showToast('Сначала начните видеозвонок', 'error');
                }
                return;
            }
            
            const processedStream = await vb.applyBlur(myStream, bg.amount);
            
            // Replace tracks in peer connections
            peers.forEach(({pc}) => {
                const senders = pc.getSenders();
                processedStream.getVideoTracks().forEach(track => {
                    const sender = senders.find(s => s.track?.kind === 'video');
                    if (sender) {
                        sender.replaceTrack(track);
                    }
                });
            });
            
            // Update local video
            const localVideo = document.querySelector('#tile-local video');
            if (localVideo) {
                localVideo.srcObject = processedStream;
            }
            
            if (typeof showToast === 'function') {
                showToast(`Фон: ${bg.name}`, 'success');
            }
            
        } catch (error) {
            console.error('Error applying blur:', error);
            if (typeof showToast === 'function') {
                showToast('Ошибка применения фона', 'error');
            }
        }
    }
}

// Export
window.SimpleVirtualBackground = SimpleVirtualBackground;
window.showBackgroundSelector = showBackgroundSelector;
window.closeBackgroundSelector = closeBackgroundSelector;
window.applyBackground = applyBackground;

console.log('✅ Simple Virtual Backgrounds Module Loaded');