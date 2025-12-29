// ==================== MOBILE NAVIGATION MODULE ====================
// Telegram-style mobile navigation with automatic screen transitions

(function() {
    'use strict';
    
    // ==================== CONFIGURATION ====================
    const MOBILE_BREAKPOINT = 768;
    const TRANSITION_DURATION = 300; // ms
    
    // ==================== STATE MANAGEMENT ====================
    let currentScreen = 'sidebar'; // 'sidebar', 'chat', 'tasks', 'admin'
    let navigationHistory = ['sidebar'];
    let isMobile = window.innerWidth <= MOBILE_BREAKPOINT;
    
    // ==================== UTILITY FUNCTIONS ====================
    
    function isMobileDevice() {
        return window.innerWidth <= MOBILE_BREAKPOINT;
    }
    
    function updateMobileState() {
        const wasMobile = isMobile;
        isMobile = isMobileDevice();
        
        if (wasMobile !== isMobile) {
            // Режим изменился, сбрасываем навигацию
            if (!isMobile) {
                resetToDesktopMode();
            }
        }
    }
    
    function resetToDesktopMode() {
        const mainApp = document.querySelector('.main-app');
        if (mainApp) {
            mainApp.classList.remove('chat-active', 'tasks-active', 'admin-active');
        }
        navigationHistory = ['sidebar'];
        currentScreen = 'sidebar';
    }
    
    // ==================== SCREEN NAVIGATION ====================
    
    function navigateToScreen(screenName, addToHistory = true) {
        if (!isMobile) return; // Только для мобильных
        
        console.log('📱 Navigating to:', screenName);
        
        const mainApp = document.querySelector('.main-app');
        if (!mainApp) return;
        
        // Убираем все классы активности
        mainApp.classList.remove('chat-active', 'tasks-active', 'admin-active');
        
        // Получаем контейнеры
        const tasksArea = document.getElementById('tasksArea');
        const adminArea = document.getElementById('adminArea');
        const chatContainer = document.getElementById('chatContainer');
        
        // Скрываем все области по умолчанию
        if (tasksArea) tasksArea.style.display = 'none';
        if (adminArea) adminArea.style.display = 'none';
        if (chatContainer) chatContainer.style.display = 'none';
        
        // Добавляем нужный класс и показываем нужную область
        if (screenName === 'chat') {
            mainApp.classList.add('chat-active');
            if (chatContainer) chatContainer.style.display = 'flex';
        } else if (screenName === 'tasks') {
            mainApp.classList.add('tasks-active');
            if (tasksArea) {
                tasksArea.style.display = 'block';
                // Активируем таб задач
                const tasksTab = document.querySelector('.tab-btn[data-tab="tasks"]');
                if (tasksTab) {
                    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                    tasksTab.classList.add('active');
                }
            }
        } else if (screenName === 'admin') {
            mainApp.classList.add('admin-active');
            if (adminArea) {
                adminArea.style.display = 'flex';
                // Активируем таб админки
                const adminTab = document.querySelector('.tab-btn[data-tab="admin"]');
                if (adminTab) {
                    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                    adminTab.classList.add('active');
                }
            }
        }
        
        // Обновляем историю
        if (addToHistory) {
            if (currentScreen !== screenName) {
                navigationHistory.push(screenName);
                currentScreen = screenName;
            }
        } else {
            currentScreen = screenName;
        }
        
        // Обновляем мета-теги для iOS
        updateStatusBarColor(screenName);
        
        console.log('Navigation history:', navigationHistory);
    }
    
    function goBack() {
        if (!isMobile) return;
        
        console.log('📱 Going back, history:', navigationHistory);
        
        if (navigationHistory.length > 1) {
            // Удаляем текущий экран из истории
            navigationHistory.pop();
            
            // Получаем предыдущий экран
            const previousScreen = navigationHistory[navigationHistory.length - 1];
            
            console.log('📱 Previous screen:', previousScreen);
            
            // Переходим на предыдущий экран без добавления в историю
            navigateToScreen(previousScreen, false);
            
            // Если возвращаемся на sidebar, очищаем активные экраны
            if (previousScreen === 'sidebar') {
                // Очищаем активный чат в UI
                document.querySelectorAll('.chat-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Скрываем контейнер чата
                const chatContainer = document.getElementById('chatContainer');
                const welcomeScreen = document.getElementById('welcomeScreen');
                if (chatContainer) chatContainer.style.display = 'none';
                if (welcomeScreen && !isMobile) welcomeScreen.style.display = 'flex';
                
                // ВАЖНО: Скрываем tasks и admin области
                const tasksArea = document.getElementById('tasksArea');
                const adminArea = document.getElementById('adminArea');
                if (tasksArea) tasksArea.style.display = 'none';
                if (adminArea) adminArea.style.display = 'none';
                
                // НЕ меняем активный таб принудительно!
                // Пользователь может захотеть открыть контакты или другой таб
                
                // Если активен таб задач или админа - переключаем на чаты
                const activeTab = document.querySelector('.tab-btn.active');
                if (activeTab) {
                    const activeTabName = activeTab.dataset.tab;
                    if (activeTabName === 'tasks' || activeTabName === 'admin') {
                        // Только если был tasks/admin, переключаем на чаты
                        document.querySelectorAll('.tab-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        
                        const chatsTab = document.querySelector('.tab-btn[data-tab="chats"]');
                        if (chatsTab) chatsTab.classList.add('active');
                        
                        // Показываем content чатов
                        document.querySelectorAll('.tab-content').forEach(content => {
                            content.style.display = 'none';
                        });
                        const chatsContent = document.getElementById('chatsTab');
                        if (chatsContent) chatsContent.style.display = 'block';
                    }
                    // Если активен таб контактов или чатов - оставляем как есть
                }
                
                // Глобальная переменная
                window.currentChat = null;
            }
        } else {
            console.log('📱 Already at root screen');
        }
    }
    
    function updateStatusBarColor(screenName) {
        // Обновляем цвет статус-бара на iOS
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        
        // Цвета для разных экранов
        const colors = {
            light: {
                sidebar: '#FFFFFF',
                chat: '#FFFFFF',
                tasks: '#FFFFFF',
                admin: '#FFFFFF'
            },
            dark: {
                sidebar: '#1C1C1E',
                chat: '#1C1C1E',
                tasks: '#1C1C1E',
                admin: '#1C1C1E'
            }
        };
        
        const color = isDark ? colors.dark[screenName] : colors.light[screenName];
        metaThemeColor.content = color;
    }
    
    // ==================== MOBILE BACK BUTTON INJECTION ====================
    
    function injectMobileBackButtons() {
        // Добавляем кнопку "Назад" в заголовок чата
        const chatHeader = document.querySelector('.chat-header');
        if (chatHeader && !chatHeader.querySelector('.mobile-back-btn')) {
            const backBtn = document.createElement('button');
            backBtn.className = 'mobile-back-btn';
            backBtn.innerHTML = '<span class="material-icons">arrow_back</span>';
            backBtn.onclick = goBack;
            
            // Вставляем в начало header
            chatHeader.insertBefore(backBtn, chatHeader.firstChild);
        }
        
        // Добавляем обработчики для кнопок "Назад" в Tasks и Admin
        setupTasksBackButton();
        setupAdminBackButton();
    }
    
    function setupTasksBackButton() {
        const tasksHeader = document.querySelector('.tasks-header');
        if (tasksHeader) {
            tasksHeader.addEventListener('click', function(e) {
                const rect = tasksHeader.getBoundingClientRect();
                // Клик в области слева (где ::before кнопка)
                if (e.clientX < rect.left + 56 && isMobile) {
                    goBack();
                }
            });
        }
    }
    
    function setupAdminBackButton() {
        const adminHeader = document.querySelector('.admin-header');
        if (adminHeader) {
            adminHeader.addEventListener('click', function(e) {
                const rect = adminHeader.getBoundingClientRect();
                // Клик в области слева
                if (e.clientX < rect.left + 56 && isMobile) {
                    goBack();
                }
            });
        }
    }
    
    // ==================== EVENT HANDLERS ====================
    
    function setupMobileEventHandlers() {
        // Перехватываем открытие чата
        const originalOpenChat = window.openChat;
        if (originalOpenChat) {
            window.openChat = function(chat) {
                originalOpenChat.call(this, chat);
                
                // На мобильных автоматически переходим в чат
                if (isMobile) {
                    setTimeout(() => {
                        navigateToScreen('chat');
                    }, 50);
                }
            };
        }
        
        // Перехватываем функцию openChatById
        const originalOpenChatById = window.openChatById;
        if (originalOpenChatById) {
            window.openChatById = function(chatId) {
                originalOpenChatById.call(this, chatId);
                
                if (isMobile) {
                    setTimeout(() => {
                        navigateToScreen('chat');
                    }, 50);
                }
            };
        }
        
        // Перехватываем selectContact
        const originalSelectContact = window.selectContact;
        if (originalSelectContact) {
            window.selectContact = function(userId) {
                originalSelectContact.call(this, userId);
                
                if (isMobile) {
                    setTimeout(() => {
                        navigateToScreen('chat');
                    }, 50);
                }
            };
        }
        
        // Перехватываем создание чата
        const originalCreateChatForContact = window.createChatForContact;
        if (originalCreateChatForContact) {
            window.createChatForContact = async function(user) {
                const result = await originalCreateChatForContact.call(this, user);
                
                if (isMobile) {
                    setTimeout(() => {
                        navigateToScreen('chat');
                    }, 50);
                }
                
                return result;
            };
        }
        
        // НЕ добавляем обработчик табов здесь - это делается в app.js
        // app.js теперь учитывает мобильную навигацию
        
        // Обработка аппаратной кнопки "Назад" на Android
        window.addEventListener('popstate', function(e) {
            if (isMobile) {
                e.preventDefault();
                goBack();
            }
        });
        
        // Добавляем начальное состояние в history для Android back button
        if (isMobile && window.history) {
            window.history.pushState({ screen: 'sidebar' }, '', '');
        }
    }
    
    // ==================== SWIPE GESTURES (ОПЦИОНАЛЬНО) ====================
    
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;
    
    function handleSwipe() {
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        const minSwipeDistance = 50;
        
        // Проверяем что это горизонтальный свайп
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
            if (deltaX > 0 && currentScreen !== 'sidebar') {
                // Свайп вправо = назад
                goBack();
            }
        }
    }
    
    function setupSwipeGestures() {
        if (!isMobile) return;
        
        const chatArea = document.querySelector('.chat-area');
        const tasksContainer = document.querySelector('.tasks-container');
        
        [chatArea, tasksContainer].forEach(container => {
            if (!container) return;
            
            container.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });
            
            container.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                handleSwipe();
            }, { passive: true });
        });
    }
    
    // ==================== KEYBOARD HANDLING ====================
    
function setupKeyboardHandling() {
    if (!isMobile) return;
    
    console.log('📱 Setting up keyboard handling...');
    
    // Ждём загрузки элемента
    const waitForElement = (selector, timeout = 5000) => {
        return new Promise((resolve) => {
            const checkElement = () => {
                const el = document.querySelector(selector);
                if (el) {
                    resolve(el);
                } else if (timeout > 0) {
                    timeout -= 100;
                    setTimeout(checkElement, 100);
                } else {
                    resolve(null);
                }
            };
            checkElement();
        });
    };
    
    waitForElement('#messageTextarea').then(messageInput => {
        if (!messageInput) {
            console.warn('⚠️ #messageTextarea not found');
            return;
        }
        
        console.log('✅ Found #messageTextarea, attaching handlers');
        
        // ========== VISUAL VIEWPORT API (iOS Safari, Chrome) ==========
        
        if (window.visualViewport) {
            console.log('✅ Visual Viewport API available');
            
            let keyboardHeight = 0;
            
            const handleViewportChange = () => {
                const viewportHeight = window.visualViewport.height;
                const windowHeight = window.innerHeight;
                const diff = windowHeight - viewportHeight;
                
                if (diff > 150) {
                    // Клавиатура открыта
                    keyboardHeight = diff;
                    document.body.classList.add('keyboard-open');
                    
                    console.log('⌨️ Keyboard open:', {
                        viewportHeight,
                        windowHeight,
                        keyboardHeight
                    });
                    
                    // Auto-scroll к последнему сообщению
                    requestAnimationFrame(() => {
                        const messagesContainer = document.querySelector('.messages-container');
                        if (messagesContainer) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    });
                } else if (keyboardHeight > 0 && diff < 100) {
                    // Клавиатура закрылась
                    keyboardHeight = 0;
                    document.body.classList.remove('keyboard-open');
                    
                    console.log('⌨️ Keyboard closed');
                }
            };
            
            // Слушаем изменения viewport
            window.visualViewport.addEventListener('resize', handleViewportChange);
            window.visualViewport.addEventListener('scroll', handleViewportChange);
            
            // Дополнительно: прямое отслеживание focus
            messageInput.addEventListener('focus', () => {
                console.log('📱 Textarea focused');
                
                // Даём время клавиатуре открыться
                setTimeout(() => {
                    handleViewportChange();
                    
                    // Скроллим к последнему сообщению
                    const messagesContainer = document.querySelector('.messages-container');
                    if (messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }, 300);
            });
            
            messageInput.addEventListener('blur', () => {
                console.log('📱 Textarea blurred');
                
                setTimeout(() => {
                    handleViewportChange();
                }, 100);
            });
            
        } else {
            console.log('⚠️ Visual Viewport API not available, using resize fallback');
            
            // ========== RESIZE FALLBACK (старые Android) ==========
            
            let lastHeight = window.innerHeight;
            
            const handleResize = () => {
                const currentHeight = window.innerHeight;
                const diff = lastHeight - currentHeight;
                
                if (diff > 150) {
                    // Клавиатура открылась
                    document.body.classList.add('keyboard-open');
                    
                    console.log('⌨️ Keyboard detected (resize):', {
                        lastHeight,
                        currentHeight,
                        diff
                    });
                    
                    setTimeout(() => {
                        const messagesContainer = document.querySelector('.messages-container');
                        if (messagesContainer) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    }, 100);
                } else if (diff < -150) {
                    // Клавиатура закрылась
                    document.body.classList.remove('keyboard-open');
                    console.log('⌨️ Keyboard hidden (resize)');
                }
                
                lastHeight = currentHeight;
            };
            
            window.addEventListener('resize', handleResize);
            
            // Focus/Blur для дополнительной надёжности
            messageInput.addEventListener('focus', () => {
                console.log('📱 Textarea focused (fallback)');
                
                setTimeout(() => {
                    const messagesContainer = document.querySelector('.messages-container');
                    if (messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }, 300);
            });
        }
        
        console.log('✅ Keyboard handling setup complete');
    });
}
    
    // ==================== VIEWPORT HEIGHT FIX (iOS) ====================
    
    function fixViewportHeight() {
        // Фикс для iOS - правильная высота viewport
        const setVH = () => {
            // Method 1: window.innerHeight (традиционный)
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
            
            // Method 2: Visual Viewport API (Safari iOS 13+)
            if (window.visualViewport) {
                const vvh = window.visualViewport.height * 0.01;
                document.documentElement.style.setProperty('--vvh', `${vvh}px`);
                
                console.log('📱 Viewport heights:', {
                    innerHeight: window.innerHeight,
                    visualHeight: window.visualViewport.height,
                    difference: window.innerHeight - window.visualViewport.height
                });
            }
        };
        
        setVH();
        
        // Обновляем при resize
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', setVH);
        
        // Visual Viewport API listeners (для Safari)
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', setVH);
            window.visualViewport.addEventListener('scroll', setVH);
        }
    }
    
    // ==================== PREVENT ZOOM ON INPUT FOCUS (iOS) ====================
    
    function preventZoomOnFocus() {
        // Добавляем meta viewport с maximum-scale только на iOS
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        
        if (isIOS && isMobile) {
            let viewportMeta = document.querySelector('meta[name="viewport"]');
            if (viewportMeta) {
                viewportMeta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            }
        }
    }
    
    // ==================== INITIALIZATION ====================
    
    function initMobileNavigation() {
        console.log('📱 Initializing mobile navigation...');
        
        updateMobileState();
        
        if (isMobile) {
            console.log('📱 Mobile mode detected');
            
            // Inject back buttons
            injectMobileBackButtons();
            
            // Setup event handlers
            setupMobileEventHandlers();
            
            // Setup swipe gestures
            setupSwipeGestures();
            
            // Setup keyboard handling
            setupKeyboardHandling();
            
            // Fix viewport height
            fixViewportHeight();
            
            // Prevent zoom on input focus
            preventZoomOnFocus();
            
            // Initial status bar color
            updateStatusBarColor('sidebar');
            
            console.log('📱 Mobile navigation initialized');
        }
        
        // Listen for resize events
        window.addEventListener('resize', () => {
            updateMobileState();
        });
    }
    
    // ==================== PUBLIC API ====================
    
    window.mobileNavigation = {
        navigateTo: navigateToScreen,
        goBack: goBack,
        isMobile: () => isMobile,
        getCurrentScreen: () => currentScreen,
        getHistory: () => [...navigationHistory]
    };
    
    // ==================== AUTO-INITIALIZE ====================
    
    // Инициализируем когда DOM загружен
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNavigation);
    } else {
        initMobileNavigation();
    }
    
    // Реинициализируем когда приложение загружено
    window.addEventListener('load', () => {
        setTimeout(initMobileNavigation, 100);
    });
    
})();