// ==================== THEME MANAGEMENT ====================

class ThemeManager {
    constructor() {
        this.currentTheme = this.loadTheme();
        this.applyTheme(this.currentTheme);
        this.setupSystemThemeListener();
    }

    loadTheme() {
        const saved = localStorage.getItem('theme');
        if (saved) return saved;
        
        // По умолчанию используем системную тему
        return 'auto';
    }

    saveTheme(theme) {
        localStorage.setItem('theme', theme);
        this.currentTheme = theme;
    }

    applyTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'auto') {
            // Используем системную тему
            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.setAttribute('data-theme', isDark ? 'dark' : 'light');
        } else {
            html.setAttribute('data-theme', theme);
        }
    }

    setTheme(theme) {
        this.saveTheme(theme);
        this.applyTheme(theme);
    }

    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            if (this.currentTheme === 'auto') {
                this.applyTheme('auto');
            }
        });
    }

    getCurrentTheme() {
        return this.currentTheme;
    }

    getActiveTheme() {
        return document.documentElement.getAttribute('data-theme');
    }
}

// Инициализация менеджера тем
const themeManager = new ThemeManager();
window.themeManager = themeManager;

console.log('✅ Theme manager loaded');