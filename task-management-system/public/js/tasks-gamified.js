// ==================== TASKS GAMIFIED STANDALONE ====================
// Версия которая работает БЕЗ изменений в tasks.js
// Полностью автономная, агрессивная инициализация

(function() {
    'use strict';
    
    console.log('🎮 [Gamified Standalone] Loading...');
    
    // ==================== CONFIG ====================
    
    const CONFIG = {
        MOBILE_BREAKPOINT: 768,
        INIT_DELAY: 1000,
        INIT_RETRY_DELAY: 500,
        MAX_RETRIES: 20,
        OBSERVER_DELAY: 100
    };
    
    // ==================== STATE ====================
    
    let state = {
        initialized: false,
        retryCount: 0,
        observers: [],
        originalFunctions: {
            renderTasks: null,
            updateTaskStatus: null
        }
    };
    
    let gamificationData = {
        streak: 0,
        completedToday: 0,
        totalTasks: 0
    };
    
    // ==================== UTILS ====================
    
    function isMobile() {
        return window.innerWidth <= CONFIG.MOBILE_BREAKPOINT;
    }
    
    function log(msg, ...args) {
        console.log(`🎮 [Gamified]`, msg, ...args);
    }
    
    function warn(msg, ...args) {
        console.warn(`⚠️ [Gamified]`, msg, ...args);
    }
    
    function error(msg, ...args) {
        console.error(`❌ [Gamified]`, msg, ...args);
    }
    
    // ==================== INITIALIZATION ====================
    
    function init() {
        log('Init attempt', ++state.retryCount, 'of', CONFIG.MAX_RETRIES);
        
        if (state.initialized) {
            log('Already initialized');
            return;
        }
        
        if (!isMobile()) {
            log('Desktop mode, skipping');
            return;
        }
        
        // Проверяем готовность
        if (!checkReadiness()) {
            if (state.retryCount < CONFIG.MAX_RETRIES) {
                log('Not ready, retrying in', CONFIG.INIT_RETRY_DELAY, 'ms');
                setTimeout(init, CONFIG.INIT_RETRY_DELAY);
            } else {
                error('Max retries reached, giving up');
            }
            return;
        }
        
        log('✅ Ready! Initializing...');
        
        try {
            // Инжектим UI
            injectHeader();
            
            // Перехватываем функции
            interceptFunctions();
            
            // Запускаем observers
            startObservers();
            
            // Первичное обновление
            setTimeout(() => {
                updateStats();
                enhanceAllCards();
            }, CONFIG.OBSERVER_DELAY);
            
            state.initialized = true;
            log('✅ Initialization complete!');
            
        } catch (err) {
            error('Initialization failed:', err);
        }
    }
    
    function checkReadiness() {
        const checks = {
            'window.tasks': typeof window.tasks !== 'undefined',
            'window.renderTasks': typeof window.renderTasks === 'function',
            '.tasks-header': !!document.querySelector('.tasks-header'),
            'tasks.length': window.tasks?.length >= 0
        };
        
        const allReady = Object.values(checks).every(Boolean);
        
        if (!allReady) {
            log('Readiness check:', checks);
        }
        
        return allReady;
    }
    
    // ==================== INJECT HEADER ====================
    
    function injectHeader() {
        const header = document.querySelector('.tasks-header');
        if (!header) {
            warn('Header not found');
            return;
        }
        
        if (header.querySelector('.tasks-greeting')) {
            log('Header already injected');
            return;
        }
        
        const userName = window.currentUser?.name || 'User';
        const { greeting, emoji } = getGreeting();
        
        const html = `
            <div class="tasks-greeting" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <span class="tasks-greeting__emoji" style="font-size: 32px;">${emoji}</span>
                <div class="tasks-greeting__text">
                    <h2 class="tasks-greeting__title" style="font-size: 20px; font-weight: 700; margin: 0 0 4px 0;">${greeting}, ${userName}!</h2>
                    <p class="tasks-greeting__subtitle" style="font-size: 14px; color: var(--text-secondary); margin: 0;">Let's crush some tasks today</p>
                </div>
            </div>
            
            <div class="tasks-stats" style="display: flex; gap: 8px; margin-bottom: 16px; overflow-x: auto;">
                <div class="stat-badge stat-badge--streak" style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%); color: white; padding: 8px 16px; border-radius: 24px; flex-shrink: 0;">
                    <span style="font-size: 20px;">🔥</span>
                    <div>
                        <div id="gamified-streak" style="font-size: 16px; font-weight: 700;">0</div>
                        <div style="font-size: 12px; opacity: 0.9;">day streak</div>
                    </div>
                </div>
                
                <div class="stat-badge" style="display: flex; align-items: center; gap: 8px; background: var(--bg-tertiary); padding: 8px 16px; border-radius: 24px; flex-shrink: 0;">
                    <span style="font-size: 20px;">⭐</span>
                    <div>
                        <div id="gamified-completed" style="font-size: 16px; font-weight: 700;">0/0</div>
                        <div style="font-size: 12px; opacity: 0.9;">tasks</div>
                    </div>
                </div>
            </div>
            
            <div class="daily-progress" style="background: var(--bg-tertiary); border-radius: 16px; padding: 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: var(--text-secondary); margin: 0;">📊 Daily Progress</h3>
                    <span id="gamified-percent" style="font-size: 16px; font-weight: 700;">0%</span>
                </div>
                <div style="height: 12px; background: var(--bg-primary); border-radius: 24px; overflow: hidden;">
                    <div id="gamified-fill" style="height: 100%; background: linear-gradient(90deg, #00B8A9 0%, #00D4C4 50%, #34C759 100%); border-radius: 24px; width: 0%; transition: width 0.8s ease;"></div>
                </div>
                <p id="gamified-message" style="margin-top: 8px; font-size: 13px; color: var(--text-secondary);">
                    <span>💪</span>
                    <span>Start your first task!</span>
                </p>
            </div>
        `;
        
        header.insertAdjacentHTML('afterbegin', html);
        log('✅ Header injected');
    }
    
    function getGreeting() {
        const hour = new Date().getHours();
        let greeting, emoji;
        
        if (hour < 12) {
            greeting = 'Good morning';
            emoji = '☀️';
        } else if (hour < 18) {
            greeting = 'Good afternoon';
            emoji = '👋';
        } else {
            greeting = 'Good evening';
            emoji = '🌙';
        }
        
        return { greeting, emoji };
    }
    
    // ==================== INTERCEPT FUNCTIONS ====================
    
    function interceptFunctions() {
        // Intercept renderTasks
        if (window.renderTasks && !state.originalFunctions.renderTasks) {
            state.originalFunctions.renderTasks = window.renderTasks;
            
            window.renderTasks = function() {
                log('renderTasks called');
                state.originalFunctions.renderTasks.apply(this, arguments);
                
                setTimeout(() => {
                    enhanceAllCards();
                    updateStats();
                }, CONFIG.OBSERVER_DELAY);
            };
            
            log('✅ renderTasks intercepted');
        }
        
        // Intercept updateTaskStatus
        if (window.updateTaskStatus && !state.originalFunctions.updateTaskStatus) {
            state.originalFunctions.updateTaskStatus = window.updateTaskStatus;
            
            window.updateTaskStatus = async function(taskId, newStatus) {
                log('updateTaskStatus called:', taskId, newStatus);
                
                await state.originalFunctions.updateTaskStatus.apply(this, arguments);
                
                if (newStatus === 'done') {
                    setTimeout(() => {
                        showAchievement('Task Completed!', '🎉 Great job!');
                        showConfetti();
                        
                        if (navigator.vibrate) {
                            navigator.vibrate([10, 50, 10]);
                        }
                    }, 300);
                }
                
                setTimeout(() => {
                    updateStats();
                }, 500);
            };
            
            log('✅ updateTaskStatus intercepted');
        }
    }
    
    // ==================== OBSERVERS ====================
    
    function startObservers() {
        const kanban = document.getElementById('kanbanBoard');
        const tasksList = document.getElementById('tasksList');
        
        const observer = new MutationObserver((mutations) => {
            let hasChanges = false;
            
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && 
                        (node.classList?.contains('task-card') ||
                         node.querySelector?.('.task-card'))) {
                        hasChanges = true;
                    }
                });
            });
            
            if (hasChanges) {
                log('DOM changes detected');
                setTimeout(enhanceAllCards, CONFIG.OBSERVER_DELAY);
            }
        });
        
        if (kanban) {
            observer.observe(kanban, { childList: true, subtree: true });
            state.observers.push(observer);
            log('✅ Observing kanban');
        }
        
        if (tasksList) {
            observer.observe(tasksList, { childList: true, subtree: true });
            state.observers.push(observer);
            log('✅ Observing tasksList');
        }
    }
    
    // ==================== ENHANCE CARDS ====================
    
    function enhanceAllCards() {
        if (!isMobile()) return;
        
        const cards = document.querySelectorAll('.task-card');
        log('Enhancing', cards.length, 'cards');
        
        cards.forEach(card => {
            if (card.dataset.gamifiedStandalone === 'true') return;
            card.dataset.gamifiedStandalone = 'true';
            
            enhanceCard(card);
        });
    }
    
    function enhanceCard(card) {
        const taskId = card.dataset.taskId;
        if (!taskId || !window.tasks) return;
        
        const task = window.tasks.find(t => t.id === taskId);
        if (!task) return;
        
        // Set priority
        if (task.priority) {
            card.setAttribute('data-priority', task.priority);
        }
        
        // Add progress bar
        if (!card.querySelector('.task-progress-section')) {
            const progress = getProgress(task);
            const progressHtml = `
                <div class="task-progress-section" style="margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">Progress</span>
                        <span style="font-size: 18px; font-weight: 700;">${progress}%</span>
                    </div>
                    <div style="height: 10px; background: var(--bg-primary); border-radius: 24px; overflow: hidden;">
                        <div style="height: 100%; background: linear-gradient(90deg, #00B8A9, #00D4C4, #34C759); border-radius: 24px; width: ${progress}%; transition: width 0.6s ease;"></div>
                    </div>
                </div>
            `;
            
            const footer = card.querySelector('.task-card-footer');
            if (footer) {
                footer.insertAdjacentHTML('beforebegin', progressHtml);
            } else {
                card.insertAdjacentHTML('beforeend', progressHtml);
            }
        }
        
        // Add action buttons
        if (!card.querySelector('.task-actions-gamified') && canQuickComplete(task)) {
            const actionsHtml = `
                <div class="task-actions task-actions-gamified" style="display: flex; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--divider);">
                    <button onclick="event.stopPropagation(); window.openTaskDetails?.('${taskId}');" style="flex: 1; padding: 12px; background: var(--accent-primary); color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 20px;">visibility</span>
                        View
                    </button>
                    <button onclick="event.stopPropagation(); window.tasksGamified?.quickComplete('${taskId}');" style="flex: 1; padding: 12px; background: var(--accent-success); color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 20px;">check_circle</span>
                        Done
                    </button>
                </div>
            `;
            
            card.insertAdjacentHTML('beforeend', actionsHtml);
        }
    }
    
    function getProgress(task) {
        const map = {
            'todo': 0,
            'in_progress': 50,
            'review': 75,
            'done': 100
        };
        return map[task.status] || 0;
    }
    
    function canQuickComplete(task) {
        if (task.status === 'done') return false;
        if (task.creatorId === window.currentUser?.id) return true;
        return false;
    }
    
    // ==================== QUICK COMPLETE ====================
    
    async function quickComplete(taskId) {
        log('Quick complete:', taskId);
        
        if (!window.updateTaskStatus) {
            error('updateTaskStatus not available');
            return;
        }
        
        const task = window.tasks?.find(t => t.id === taskId);
        if (!task) return;
        
        // Check permissions
        if (window.canCompleteTask && !window.canCompleteTask(task)) {
            if (window.showToast) {
                window.showToast('У вас нет прав для завершения задачи', 'warning');
            }
            return;
        }
        
        // Animate
        const card = document.querySelector(`[data-task-id="${taskId}"]`);
        if (card) {
            card.style.transition = 'all 0.5s ease';
            card.style.transform = 'scale(0.95)';
            card.style.opacity = '0.5';
        }
        
        // Update
        await window.updateTaskStatus(taskId, 'done');
    }
    
    // ==================== UPDATE STATS ====================
    
    function updateStats() {
        if (!window.tasks) return;
        
        const all = window.tasks;
        const done = all.filter(t => t.status === 'done');
        const completed = done.length;
        const total = all.length;
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
        
        log('Stats:', { completed, total, percent });
        
        // Update UI
        const elCompleted = document.getElementById('gamified-completed');
        const elPercent = document.getElementById('gamified-percent');
        const elFill = document.getElementById('gamified-fill');
        const elMessage = document.getElementById('gamified-message');
        
        if (elCompleted) elCompleted.textContent = `${completed}/${total}`;
        if (elPercent) elPercent.textContent = `${percent}%`;
        if (elFill) elFill.style.width = `${percent}%`;
        
        if (elMessage) {
            const messages = [
                { t: 0, i: '💪', m: 'Start your first task!' },
                { t: 25, i: '🚀', m: "You're on fire!" },
                { t: 50, i: '⚡', m: 'Halfway there!' },
                { t: 75, i: '🌟', m: 'Almost done!' },
                { t: 100, i: '🎉', m: 'All tasks completed!' }
            ];
            
            const msg = messages.reverse().find(m => percent >= m.t);
            if (msg) {
                elMessage.innerHTML = `<span>${msg.i}</span> <span>${msg.m}</span>`;
            }
        }
        
        updateStreak();
        
        gamificationData = { streak: gamificationData.streak, completedToday: completed, totalTasks: total };
    }
    
    function updateStreak() {
        const el = document.getElementById('gamified-streak');
        if (!el) return;
        
        const lastDate = localStorage.getItem('gamified_lastActive');
        const streak = parseInt(localStorage.getItem('gamified_streak') || '0');
        const today = new Date().toDateString();
        
        if (lastDate === today) {
            el.textContent = streak;
            gamificationData.streak = streak;
        } else {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (lastDate === yesterday.toDateString()) {
                const newStreak = streak + 1;
                localStorage.setItem('gamified_streak', newStreak);
                localStorage.setItem('gamified_lastActive', today);
                el.textContent = newStreak;
                gamificationData.streak = newStreak;
                
                if (newStreak === 7) {
                    setTimeout(() => {
                        showAchievement('7 Day Streak!', '🔥 Unstoppable!');
                    }, 1000);
                }
            } else {
                localStorage.setItem('gamified_streak', '1');
                localStorage.setItem('gamified_lastActive', today);
                el.textContent = '1';
                gamificationData.streak = 1;
            }
        }
    }
    
    // ==================== ACHIEVEMENT ====================
    
    function showAchievement(title, desc) {
        const existing = document.querySelector('.achievement-popup');
        if (existing) existing.remove();
        
        const popup = document.createElement('div');
        popup.className = 'achievement-popup';
        popup.style.cssText = `
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-120%);
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            padding: 16px 24px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(255,215,0,0.6);
            z-index: 10000;
            max-width: 90%;
            animation: slideIn 0.6s ease forwards;
        `;
        
        popup.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 36px;">🏆</span>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700;">${title}</h4>
                    <p style="margin: 0; font-size: 13px;">${desc}</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(popup);
        
        if (navigator.vibrate) {
            navigator.vibrate([50, 100, 50]);
        }
        
        setTimeout(() => {
            popup.style.animation = 'slideOut 0.4s ease forwards';
            setTimeout(() => popup.remove(), 400);
        }, 3000);
    }
    
    // ==================== CONFETTI ====================
    
    function showConfetti() {
        const container = document.createElement('div');
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        `;
        document.body.appendChild(container);
        
        const colors = ['#FF6B35', '#00B8A9', '#FFD700', '#00D4C4', '#FF9500'];
        
        for (let i = 0; i < 50; i++) {
            const piece = document.createElement('div');
            piece.style.cssText = `
                position: absolute;
                width: 10px;
                height: 10px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${Math.random() * 100}%;
                animation: fall ${Math.random() * 2 + 2}s ease forwards;
                animation-delay: ${Math.random() * 0.5}s;
            `;
            container.appendChild(piece);
        }
        
        setTimeout(() => container.remove(), 4000);
    }
    
    // ==================== PUBLIC API ====================
    
    window.tasksGamified = {
        init,
        updateStats,
        showAchievement,
        showConfetti,
        quickComplete,
        getStats: () => gamificationData
    };
    
    // ==================== AUTO INIT ====================
    
    // Add animations to head
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            0% { transform: translateX(-50%) translateY(-120%) scale(0.8); opacity: 0; }
            100% { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; }
        }
        @keyframes slideOut {
            0% { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; }
            100% { transform: translateX(-50%) translateY(-120%) scale(0.8); opacity: 0; }
        }
        @keyframes fall {
            0% { transform: translateY(-10vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(init, CONFIG.INIT_DELAY);
        });
    } else {
        setTimeout(init, CONFIG.INIT_DELAY);
    }
    
    // Resize handler
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (isMobile() && !state.initialized) {
                init();
            }
        }, 250);
    });
    
    log('✅ Module loaded, will init in', CONFIG.INIT_DELAY, 'ms');
    
})();