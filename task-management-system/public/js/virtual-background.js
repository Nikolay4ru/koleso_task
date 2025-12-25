// ==================== VIRTUAL BACKGROUNDS ====================
// MediaPipe-based background replacement

class VirtualBackground {
    constructor() {
        this.enabled = false;
        this.backgroundType = 'none'; // none, blur, image
        this.backgroundImage = null;
        this.segmentation = null;
        this.canvas = null;
        this.ctx = null;
        this.processedStream = null;
    }
    
    // Initialize MediaPipe
    async init() {
        try {
            // Load MediaPipe Selfie Segmentation
            const {SelfieSegmentation} = await import('https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation');
            
            this.segmentation = new SelfieSegmentation({
                locateFile: (file) => {
                    return `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`;
                }
            });
            
            this.segmentation.setOptions({
                modelSelection: 1, // 0 = general, 1 = landscape
                selfieMode: true
            });
            
            console.log('✅ Virtual backgrounds initialized');
            return true;
            
        } catch (error) {
            console.error('❌ Failed to load virtual backgrounds:', error);
            return false;
        }
    }
    
    // Apply virtual background to stream
    async applyToStream(inputStream, backgroundType = 'blur', backgroundImage = null) {
        if (!this.segmentation) {
            await this.init();
        }
        
        this.backgroundType = backgroundType;
        this.backgroundImage = backgroundImage;
        
        // Create canvas
        const videoTrack = inputStream.getVideoTracks()[0];
        const settings = videoTrack.getSettings();
        
        this.canvas = document.createElement('canvas');
        this.canvas.width = settings.width || 1280;
        this.canvas.height = settings.height || 720;
        this.ctx = this.canvas.getContext('2d');
        
        // Create video element for input
        const video = document.createElement('video');
        video.srcObject = inputStream;
        video.autoplay = true;
        video.playsInline = true;
        
        // Setup segmentation callback
        this.segmentation.onResults((results) => {
            this.processFrame(video, results);
        });
        
        // Start processing
        this.processVideo(video);
        
        // Get stream from canvas
        const canvasStream = this.canvas.captureStream(30);
        
        // Add audio track from original stream
        const audioTracks = inputStream.getAudioTracks();
        audioTracks.forEach(track => canvasStream.addTrack(track));
        
        this.processedStream = canvasStream;
        this.enabled = true;
        
        return canvasStream;
    }
    
    // Process each frame
    processFrame(video, results) {
        const {width, height} = this.canvas;
        
        // Save context
        this.ctx.save();
        
        // Clear canvas
        this.ctx.clearRect(0, 0, width, height);
        
        // Draw background
        this.drawBackground();
        
        // Draw segmentation mask
        this.ctx.globalCompositeOperation = 'destination-atop';
        this.ctx.drawImage(results.segmentationMask, 0, 0, width, height);
        
        // Draw person
        this.ctx.globalCompositeOperation = 'destination-over';
        this.ctx.drawImage(video, 0, 0, width, height);
        
        this.ctx.restore();
    }
    
    // Draw background based on type
    drawBackground() {
        const {width, height} = this.canvas;
        
        switch (this.backgroundType) {
            case 'blur':
                // Draw blurred version of video
                this.ctx.filter = 'blur(20px)';
                this.ctx.drawImage(this.canvas, 0, 0, width, height);
                this.ctx.filter = 'none';
                break;
                
            case 'image':
                if (this.backgroundImage) {
                    this.ctx.drawImage(this.backgroundImage, 0, 0, width, height);
                } else {
                    this.ctx.fillStyle = '#1a1a1a';
                    this.ctx.fillRect(0, 0, width, height);
                }
                break;
                
            case 'color':
                this.ctx.fillStyle = this.backgroundColor || '#00ff00';
                this.ctx.fillRect(0, 0, width, height);
                break;
                
            default:
                // No background
                break;
        }
    }
    
    // Process video continuously
    async processVideo(video) {
        if (!this.enabled) return;
        
        await this.segmentation.send({image: video});
        
        requestAnimationFrame(() => this.processVideo(video));
    }
    
    // Disable virtual background
    disable() {
        this.enabled = false;
        this.processedStream = null;
    }
    
    // Load background image
    async loadBackgroundImage(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = url;
        });
    }
}

// ==================== Предустановленные фоны ====================
const PRESET_BACKGROUNDS = [
    {
        id: 'none',
        name: 'Без фона',
        type: 'none'
    },
    {
        id: 'blur',
        name: 'Размытие',
        type: 'blur'
    },
    {
        id: 'office',
        name: 'Офис',
        type: 'image',
        url: '/assets/backgrounds/office.jpg'
    },
    {
        id: 'nature',
        name: 'Природа',
        type: 'image',
        url: '/assets/backgrounds/nature.jpg'
    },
    {
        id: 'abstract',
        name: 'Абстракция',
        type: 'image',
        url: '/assets/backgrounds/abstract.jpg'
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
        <div style="background: white; border-radius: 16px; padding: 32px; max-width: 600px; width: 90%;">
            <h2 style="margin: 0 0 24px 0; font-size: 24px; color: #1a1a1a;">Виртуальный фон</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                ${PRESET_BACKGROUNDS.map(bg => `
                    <div class="background-option" data-bg-id="${bg.id}" style="
                        cursor: pointer;
                        border: 2px solid #e0e0e0;
                        border-radius: 12px;
                        padding: 16px;
                        text-align: center;
                        transition: all 0.2s;
                    " onmouseover="this.style.borderColor='#0088cc'" onmouseout="this.style.borderColor='#e0e0e0'">
                        <div style="font-size: 48px; margin-bottom: 8px;">
                            ${bg.type === 'blur' ? '🌫️' : bg.type === 'none' ? '❌' : '🖼️'}
                        </div>
                        <div style="font-size: 14px; font-weight: 500;">${bg.name}</div>
                    </div>
                `).join('')}
                <div class="background-option" id="uploadCustomBg" style="
                    cursor: pointer;
                    border: 2px dashed #0088cc;
                    border-radius: 12px;
                    padding: 16px;
                    text-align: center;
                    transition: all 0.2s;
                " onmouseover="this.style.backgroundColor='#f0f8ff'" onmouseout="this.style.backgroundColor='white'">
                    <div style="font-size: 48px; margin-bottom: 8px;">➕</div>
                    <div style="font-size: 14px; font-weight: 500;">Загрузить</div>
                </div>
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
            ">Закрыть</button>
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
    
    document.getElementById('uploadCustomBg').addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                await applyCustomBackground(url);
                closeBackgroundSelector();
            }
        };
        input.click();
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
    
    const vb = window.virtualBackground || new VirtualBackground();
    window.virtualBackground = vb;
    
    if (bg.type === 'none') {
        vb.disable();
        // Switch back to original stream
        if (myStream) {
            // Re-add tracks to peer connections
            peers.forEach(({pc}) => {
                myStream.getTracks().forEach(track => {
                    pc.getSenders().forEach(sender => {
                        if (sender.track?.kind === track.kind) {
                            sender.replaceTrack(track);
                        }
                    });
                });
            });
        }
    } else {
        let backgroundImage = null;
        if (bg.type === 'image') {
            backgroundImage = await vb.loadBackgroundImage(bg.url);
        }
        
        const processedStream = await vb.applyToStream(myStream, bg.type, backgroundImage);
        
        // Replace tracks in peer connections
        peers.forEach(({pc}) => {
            processedStream.getVideoTracks().forEach(track => {
                pc.getSenders().forEach(sender => {
                    if (sender.track?.kind === 'video') {
                        sender.replaceTrack(track);
                    }
                });
            });
        });
        
        // Update local video
        const localVideo = document.querySelector('#conferenceGrid video');
        if (localVideo) {
            localVideo.srcObject = processedStream;
        }
    }
    
    showToast(`Фон: ${bg.name}`, 'success');
}

// Export
window.VirtualBackground = VirtualBackground;
window.showBackgroundSelector = showBackgroundSelector;

console.log('✅ Virtual Backgrounds Module Loaded');