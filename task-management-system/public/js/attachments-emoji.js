// ==================== FILE ATTACHMENTS ====================

let selectedFiles = [];

// Attach button handler
document.getElementById('attachBtn')?.addEventListener('click', () => {
    const attachMenu = document.getElementById('attachMenu');
    attachMenu.style.display = attachMenu.style.display === 'none' ? 'flex' : 'none';
});

// Close attach menu when clicking outside
document.addEventListener('click', (e) => {
    const attachBtn = document.getElementById('attachBtn');
    const attachMenu = document.getElementById('attachMenu');
    
    if (attachMenu && !attachBtn.contains(e.target) && !attachMenu.contains(e.target)) {
        attachMenu.style.display = 'none';
    }
});

// Attach menu items
document.querySelectorAll('.attach-item').forEach(item => {
    item.addEventListener('click', () => {
        const type = item.dataset.type;
        const fileInput = document.getElementById('fileInput');
        
        // Set accept attribute based on type
        if (type === 'photo') {
            fileInput.accept = 'image/*,video/*';
        } else if (type === 'document') {
            fileInput.accept = '.pdf,.doc,.docx,.txt,.xlsx,.xls,.ppt,.pptx,.zip,.rar';
        } else if (type === 'audio') {
            fileInput.accept = 'audio/*';
        }
        
        fileInput.click();
        document.getElementById('attachMenu').style.display = 'none';
    });
});

// File input change handler
document.getElementById('fileInput')?.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    
    files.forEach(file => {
        if (!selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
            selectedFiles.push(file);
        }
    });
    
    renderFilePreview();
    e.target.value = ''; // Reset input
});

// Render file preview
function renderFilePreview() {
    const filePreview = document.getElementById('filePreview');
    const filePreviewList = document.getElementById('filePreviewList');
    
    if (selectedFiles.length === 0) {
        filePreview.style.display = 'none';
        return;
    }
    
    filePreview.style.display = 'block';
    
    filePreviewList.innerHTML = selectedFiles.map((file, index) => {
        const isImage = file.type.startsWith('image/');
        const isVideo = file.type.startsWith('video/');
        const fileSize = formatFileSize(file.size);
        
        if (isImage) {
            const url = URL.createObjectURL(file);
            return `
                <div class="file-preview-item image">
                    <img src="${url}" alt="${file.name}">
                    <div class="file-remove" onclick="removeFile(${index})">
                        <span class="material-icons">close</span>
                    </div>
                </div>
            `;
        } else {
            const icon = getFileIcon(file.type);
            return `
                <div class="file-preview-item document">
                    <div class="file-info">
                        <div class="file-icon">
                            <span class="material-icons">${icon}</span>
                        </div>
                        <div class="file-details">
                            <div class="file-name">${file.name}</div>
                            <div class="file-size">${fileSize}</div>
                        </div>
                    </div>
                    <div class="file-remove" onclick="removeFile(${index})">
                        <span class="material-icons">close</span>
                    </div>
                </div>
            `;
        }
    }).join('');
}

// Remove file from selection
window.removeFile = function(index) {
    selectedFiles.splice(index, 1);
    renderFilePreview();
};

// Clear all files
document.getElementById('clearFilesBtn')?.addEventListener('click', () => {
    selectedFiles = [];
    renderFilePreview();
});

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Get file icon
function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'picture_as_pdf';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'description';
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'table_chart';
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'slideshow';
    if (mimeType.includes('zip') || mimeType.includes('rar')) return 'folder_zip';
    if (mimeType.includes('audio')) return 'audiotrack';
    if (mimeType.includes('video')) return 'videocam';
    return 'insert_drive_file';
}

// Update sendMessage to include files
const originalSendMessage = window.sendMessage;
window.sendMessage = async function() {
    const textarea = document.getElementById('messageTextarea');
    const text = textarea.value.trim();
    
    if (!text && selectedFiles.length === 0) return;
    if (!currentChat) return;
    
    // Send text message if present
    if (text) {
        socket.emit('message:send', {
            chatId: currentChat.id,
            text,
            type: 'text'
        });
        textarea.value = '';
        textarea.style.height = 'auto';
    }
    
    // Send files if present
    if (selectedFiles.length > 0) {
        await sendFiles(selectedFiles);
        selectedFiles = [];
        renderFilePreview();
    }
};

// Send files to server
async function sendFiles(files) {
    for (const file of files) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('chatId', currentChat.id);
        
        try {
            const token = localStorage.getItem('token') || sessionStorage.getItem('token');
            const response = await fetch('/api/upload', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });
            
            if (!response.ok) throw new Error('Upload failed');
            
            const data = await response.json();
            
            // Send file message
            socket.emit('message:send', {
                chatId: currentChat.id,
                text: file.name,
                type: getFileType(file.type),
                metadata: {
                    fileName: file.name,
                    fileSize: file.size,
                    fileUrl: data.fileUrl,
                    mimeType: file.type
                }
            });
            
        } catch (error) {
            console.error('File upload error:', error);
            showToast('Ошибка загрузки файла', 'error');
        }
    }
}

function getFileType(mimeType) {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    return 'file';
}

// ==================== EMOJI PICKER ====================

const emojis = {
    smileys: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','😚','😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','😵','🤯','🤠','🥳','🥸','😎','🤓','🧐','😕','😟','🙁','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱'],
    people: ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦿','🦵','🦶','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁','👅','👄'],
    animals: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐽','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🐣','🐥','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🦟','🦗','🕷','🕸','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🦧','🐘','🦛','🦏','🐪','🐫','🦒','🦘','🐃','🐂','🐄','🐎','🐖','🐏','🐑','🦙','🐐','🦌','🐕','🐩','🦮','🐕‍🦺','🐈','🐓','🦃','🦚','🦜','🦢','🦩','🕊','🐇','🦝','🦨','🦡','🦦','🦥','🐁','🐀','🐿','🦔'],
    food: ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐','🥯','🍞','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🦴','🌭','🍔','🍟','🍕','🫓','🥪','🥙','🧆','🌮','🌯','🫔','🥗','🥘','🫕','🥫','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥠','🥮','🍢','🍡','🍧','🍨','🍦','🥧','🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯'],
    travel: ['🚗','🚕','🚙','🚌','🚎','🏎','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🦯','🦽','🦼','🛴','🚲','🛵','🏍','🛺','🚨','🚔','🚍','🚘','🚖','🚡','🚠','🚟','🚃','🚋','🚞','🚝','🚄','🚅','🚈','🚂','🚆','🚇','🚊','🚉','✈️','🛫','🛬','🛩','💺','🛰','🚀','🛸','🚁','🛶','⛵','🚤','🛥','🛳','⛴','🚢','⚓','🪝','⛽','🚧','🚦','🚥','🚏','🗺','🗿','🗽','🗼','🏰','🏯','🏟','🎡','🎢','🎠','⛲','⛱','🏖','🏝','🏜','🌋','⛰','🏔','🗻','🏕','⛺','🏠','🏡','🏘','🏚','🏗','🏭','🏢','🏬','🏣','🏤','🏥','🏦','🏨','🏪','🏫','🏩','💒','🏛','⛪','🕌','🕍','🛕','🕋'],
    objects: ['⌚','📱','📲','💻','⌨️','🖥','🖨','🖱','🖲','🕹','🗜','💽','💾','💿','📀','📼','📷','📸','📹','🎥','📽','🎞','📞','☎️','📟','📠','📺','📻','🎙','🎚','🎛','🧭','⏱','⏲','⏰','🕰','⌛','⏳','📡','🔋','🔌','💡','🔦','🕯','🪔','🧯','🛢','💸','💵','💴','💶','💷','🪙','💰','💳','💎','⚖️','🪜','🧰','🪛','🔧','🔨','⚒','🛠','⛏','🪚','🔩','⚙️','🪤','🧱','⛓','🧲','🔫','💣','🧨','🪓','🔪','🗡','⚔️','🛡','🚬','⚰️','🪦','⚱️','🏺','🔮','📿','🧿','💈','⚗️','🔭','🔬','🕳','🩹','🩺','💊','💉','🩸','🧬','🦠','🧫','🧪','🌡','🧹','🪠','🧺','🧻','🚽','🚰','🚿','🛁','🛀','🧼','🪒','🧽','🪥','🧴','🛎','🔑','🗝','🚪','🪑','🛋','🛏','🛌','🧸','🪆','🖼','🪞','🪟','🛍','🛒','🎁','🎈','🎏','🎀','🪄','🪅','🎊','🎉','🎎','🏮','🎐','🪩','🧧'],
    symbols: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','🆔','⚛️','🉑','☢️','☣️','📴','📳','🈶','🈚','🈸','🈺','🈷️','✴️','🆚','💮','🉐','㊙️','㊗️','🈴','🈵','🈹','🈲','🅰️','🅱️','🆎','🆑','🅾️','🆘','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨️','🚷','🚯','🚳','🚱','🔞','📵','🚭','❗','❕','❓','❔','‼️','⁉️','🔅','🔆','〽️','⚠️','🚸','🔱','⚜️','🔰','♻️','✅','🈯','💹','❇️','✳️','❎','🌐','💠','Ⓜ️','🌀','💤','🏧','🚾','♿','🅿️','🛗','🈳','🈂️','🛂','🛃','🛄','🛅','🚹','🚺','🚼','⚧','🚻','🚮','🎦','📶','🈁','🔣','ℹ️','🔤','🔡','🔠','🆖','🆗','🆙','🆒','🆕','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟'],
    flags: ['🏁','🚩','🎌','🏴','🏳️','🏳️‍🌈','🏳️‍⚧️','🏴‍☠️','🇦🇨','🇦🇩','🇦🇪','🇦🇫','🇦🇬','🇦🇮','🇦🇱','🇦🇲','🇦🇴','🇦🇶','🇦🇷','🇦🇸','🇦🇹','🇦🇺','🇦🇼','🇦🇽','🇦🇿','🇧🇦','🇧🇧','🇧🇩','🇧🇪','🇧🇫','🇧🇬','🇧🇭','🇧🇮','🇧🇯','🇧🇱','🇧🇲','🇧🇳','🇧🇴','🇧🇶','🇧🇷','🇧🇸','🇧🇹','🇧🇻','🇧🇼','🇧🇾','🇧🇿','🇨🇦','🇨🇨','🇨🇩','🇨🇫','🇨🇬','🇨🇭','🇨🇮','🇨🇰','🇨🇱','🇨🇲','🇨🇳','🇨🇴','🇨🇵','🇨🇷','🇨🇺','🇨🇻','🇨🇼','🇨🇽','🇨🇾','🇨🇿','🇩🇪','🇩🇬','🇩🇯','🇩🇰','🇩🇲','🇩🇴','🇩🇿','🇪🇦','🇪🇨','🇪🇪','🇪🇬','🇪🇭','🇪🇷','🇪🇸','🇪🇹','🇪🇺','🇫🇮','🇫🇯','🇫🇰','🇫🇲','🇫🇴','🇫🇷','🇬🇦','🇬🇧','🇬🇩','🇬🇪','🇬🇫','🇬🇬','🇬🇭','🇬🇮','🇬🇱','🇬🇲','🇬🇳','🇬🇵','🇬🇶','🇬🇷','🇬🇸','🇬🇹','🇬🇺','🇬🇼','🇬🇾','🇭🇰','🇭🇲','🇭🇳','🇭🇷','🇭🇹','🇭🇺','🇮🇨','🇮🇩','🇮🇪','🇮🇱','🇮🇲','🇮🇳','🇮🇴','🇮🇶','🇮🇷','🇮🇸','🇮🇹','🇯🇪','🇯🇲','🇯🇴','🇯🇵','🇰🇪','🇰🇬','🇰🇭','🇰🇮','🇰🇲','🇰🇳','🇰🇵','🇰🇷','🇰🇼','🇰🇾','🇰🇿','🇱🇦','🇱🇧','🇱🇨','🇱🇮','🇱🇰','🇱🇷','🇱🇸','🇱🇹','🇱🇺','🇱🇻','🇱🇾','🇲🇦','🇲🇨','🇲🇩','🇲🇪','🇲🇫','🇲🇬','🇲🇭','🇲🇰','🇲🇱','🇲🇲','🇲🇳','🇲🇴','🇲🇵','🇲🇶','🇲🇷','🇲🇸','🇲🇹','🇲🇺','🇲🇻','🇲🇼','🇲🇽','🇲🇾','🇲🇿','🇳🇦','🇳🇨','🇳🇪','🇳🇫','🇳🇬','🇳🇮','🇳🇱','🇳🇴','🇳🇵','🇳🇷','🇳🇺','🇳🇿','🇴🇲','🇵🇦','🇵🇪','🇵🇫','🇵🇬','🇵🇭','🇵🇰','🇵🇱','🇵🇲','🇵🇳','🇵🇷','🇵🇸','🇵🇹','🇵🇼','🇵🇾','🇶🇦','🇷🇪','🇷🇴','🇷🇸','🇷🇺','🇷🇼','🇸🇦','🇸🇧','🇸🇨','🇸🇩','🇸🇪','🇸🇬','🇸🇭','🇸🇮','🇸🇯','🇸🇰','🇸🇱','🇸🇲','🇸🇳','🇸🇴','🇸🇷','🇸🇸','🇸🇹','🇸🇻','🇸🇽','🇸🇾','🇸🇿','🇹🇦','🇹🇨','🇹🇩','🇹🇫','🇹🇬','🇹🇭','🇹🇯','🇹🇰','🇹🇱','🇹🇲','🇹🇳','🇹🇴','🇹🇷','🇹🇹','🇹🇻','🇹🇼','🇹🇿','🇺🇦','🇺🇬','🇺🇲','🇺🇳','🇺🇸','🇺🇾','🇺🇿','🇻🇦','🇻🇨','🇻🇪','🇻🇬','🇻🇮','🇻🇳','🇻🇺','🇼🇫','🇼🇸','🇽🇰','🇾🇪','🇾🇹','🇿🇦','🇿🇲','🇿🇼','🏴󐁧󐁢󐁥󐁮󐁧󐁿','🏴󐁧󐁢󐁳󐁣󐁴󐁿','🏴󐁧󐁢󐁷󐁬󐁳󐁿']
};

let currentEmojiCategory = 'smileys';

// Emoji button handler
document.getElementById('emojiBtn')?.addEventListener('click', () => {
    const emojiPicker = document.getElementById('emojiPicker');
    emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'block' : 'none';
    
    if (emojiPicker.style.display === 'block') {
        renderEmojis(currentEmojiCategory);
    }
});

// Close emoji picker when clicking outside
document.addEventListener('click', (e) => {
    const emojiBtn = document.getElementById('emojiBtn');
    const emojiPicker = document.getElementById('emojiPicker');
    
    if (emojiPicker && !emojiBtn.contains(e.target) && !emojiPicker.contains(e.target)) {
        emojiPicker.style.display = 'none';
    }
});

// Emoji category buttons
document.querySelectorAll('.emoji-category').forEach(btn => {
    btn.addEventListener('click', () => {
        const category = btn.dataset.category;
        currentEmojiCategory = category;
        
        document.querySelectorAll('.emoji-category').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        renderEmojis(category);
    });
});

// Render emojis for category
function renderEmojis(category) {
    const emojiList = document.getElementById('emojiList');
    const categoryEmojis = emojis[category] || [];
    
    emojiList.innerHTML = categoryEmojis.map(emoji => `
        <div class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</div>
    `).join('');
}

// Insert emoji into textarea
window.insertEmoji = function(emoji) {
    const textarea = document.getElementById('messageTextarea');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    textarea.value = text.substring(0, start) + emoji + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
    
    // Don't close picker - allow multiple emoji selection
};

// Emoji search
document.getElementById('emojiSearch')?.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    
    if (!query) {
        renderEmojis(currentEmojiCategory);
        return;
    }
    
    const allEmojis = Object.values(emojis).flat();
    const emojiList = document.getElementById('emojiList');
    
    // Simple filter - in real app would use emoji names/keywords
    emojiList.innerHTML = allEmojis.slice(0, 64).map(emoji => `
        <div class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</div>
    `).join('');
});

// ==================== CLICKABLE ELEMENTS ====================

// Make chat name clickable to open info
document.getElementById('chatName')?.addEventListener('click', () => {
    if (currentChat) {
        const chatInfoPanel = document.getElementById('chatInfoPanel');
        openChatInfo(currentChat);
        chatInfoPanel.classList.add('open');
    }
});

// Make message sender names clickable
function makeMessageSendersClickable() {
    document.querySelectorAll('.message-sender-name').forEach(nameEl => {
        nameEl.style.cursor = 'pointer';
        
        nameEl.addEventListener('click', () => {
            const senderId = nameEl.dataset.senderId;
            if (senderId) {
                openUserProfile(senderId);
            }
        });
    });
}

// Open user profile (can be expanded)
function openUserProfile(userId) {
    const user = users.find(u => u.id === userId);
    if (!user) return;
    
    // For now, open chat info as user profile
    const chatInfoPanel = document.getElementById('chatInfoPanel');
    const chatInfoAvatar = document.getElementById('chatInfoAvatar');
    const chatInfoName = document.getElementById('chatInfoName');
    const chatInfoStatus = document.getElementById('chatInfoStatus');
    const chatMembersSection = document.getElementById('chatMembersSection');
    
    chatInfoAvatar.style.background = generateGradient(user.name);
    chatInfoAvatar.innerHTML = getUserInitials(user.name);
    chatInfoName.textContent = user.name;
    
    const isOnline = onlineUsers.has(user.id);
    chatInfoStatus.textContent = isOnline ? 'в сети' : 'не в сети';
    
    chatMembersSection.style.display = 'none';
    chatInfoPanel.classList.add('open');
}

console.log('✅ Attachments & Emoji features loaded');