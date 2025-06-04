<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --ios-bg-main: linear-gradient(120deg, #f6faff 0%, #e8f0fa 100%);
            --ios-card-bg: rgba(255, 255, 255, 0.96);
            --ios-border-radius: 28px;
            --ios-shadow: 0 10px 40px 0 rgba(90, 120, 170, 0.12);
            --ios-accent: #007aff;
            --ios-accent-gradient: linear-gradient(90deg, #007aff 0%, #34c759 100%);
            --ios-label: #3c3c43;
            --ios-muted: #747480;
            --ios-input-bg: #f7f7fa;
            --ios-input-border: #e6e6ea;
            --ios-invalid: #ff3b30;
            --ios-success: #34c759;
        }
        body {
            min-height: 100vh;
            background: var(--ios-bg-main);
            font-family: 'SF Pro Display', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.01em;
        }
        .register-container {
            box-shadow: var(--ios-shadow);
            background: var(--ios-card-bg);
            border-radius: var(--ios-border-radius);
            overflow: hidden;
            max-width: 1020px;
            width: 100%;
            margin: 18px;
            display: flex;
        }
        .register-left {
            background: var(--ios-accent-gradient);
            color: #fff;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 270px;
            border-top-left-radius: var(--ios-border-radius);
            border-bottom-left-radius: var(--ios-border-radius);
        }
        .register-left h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: -0.02em;
        }
        .register-left p {
            font-size: 1.04rem;
            opacity: 0.93;
            margin-bottom: 28px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 1.02rem;
            font-weight: 500;
        }
        .feature-list i {
            margin-right: 10px;
            font-size: 1.21rem;
            color: #fff;
        }
        .register-right {
            flex-grow: 1;
            padding: 52px 38px;
            background: transparent;
        }
        .register-right h3 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 26px;
            color: var(--ios-accent);
            letter-spacing: -0.01em;
        }
        .form-label {
            font-weight: 600;
            color: var(--ios-label);
            margin-bottom: 6px;
            font-size: 1.02rem;
        }
        .form-control, .form-select {
            border: 1.8px solid var(--ios-input-border);
            border-radius: 12px;
            background: var(--ios-input-bg);
            padding: 14px 16px;
            font-size: 1rem;
            transition: border .22s, box-shadow .22s;
            box-shadow: none;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--ios-accent);
            box-shadow: 0 0 0 2px rgba(0,122,255,0.1);
        }
        .form-control.is-invalid, .form-control:invalid {
            border-color: var(--ios-invalid);
            background-color: #fff6f6;
        }
        .form-check-input:checked {
            background-color: var(--ios-accent);
            border-color: var(--ios-accent);
        }
        .btn-register {
            background: var(--ios-accent-gradient);
            border: none;
            color: white;
            padding: 12px 0;
            font-size: 1.13rem;
            font-weight: 700;
            border-radius: 12px;
            transition: box-shadow .2s, transform .18s;
            width: 100%;
            box-shadow: 0 3px 12px 0 rgba(0,122,255,0.14);
            letter-spacing: 0.02em;
        }
        .btn-register:hover, .btn-register:focus {
            transform: scale(1.015);
            box-shadow: 0 5px 18px 0 rgba(0,122,255,0.25);
        }
        .input-group .btn {
            border-radius: 0 12px 12px 0 !important;
            border: 1.8px solid var(--ios-input-border);
            border-left: none;
            background: var(--ios-input-bg);
            color: var(--ios-accent);
        }
        .input-group .btn:focus {
            background: #e6f2fc;
        }
        .login-link {
            text-align: center;
            margin-top: 24px;
            color: var(--ios-muted);
            font-size: 1.03rem;
        }
        .login-link a {
            color: var(--ios-accent);
            text-decoration: none;
            font-weight: 600;
            transition: color .17s;
        }
        .login-link a:hover {
            color: #0059b2;
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 0.92rem;
            color: var(--ios-muted);
            margin-top: 7px;
        }
        .password-requirements li {
            margin-bottom: 2px;
            display: flex;
            align-items: center;
        }
        .password-requirements i {
            margin-right: 7px;
        }
        .password-strength {
            height: 7px;
            border-radius: 4px;
            margin-top: 7px;
            transition: all 0.22s;
            width: 100%;
            background: #f0f0f0;
        }
        .strength-weak {
            background: linear-gradient(90deg, #ff3b30 50%, #eee 50%);
        }
        .strength-medium {
            background: linear-gradient(90deg, #ffd60a 70%, #eee 30%);
        }
        .strength-strong {
            background: linear-gradient(90deg, #34c759 100%, #eee 0%);
        }
        .modal-content {
            border-radius: 18px;
        }
        .modal-header {
            border-bottom: none;
        }
        .modal-footer {
            border-top: none;
        }
        /* IOS Switch for Agreement */
        .form-check-input[type=checkbox] {
            width: 2.3em;
            height: 1.3em;
            background-color: #e6e6ea;
            border-radius: 1em;
            position: relative;
            appearance: none;
            outline: none;
            cursor: pointer;
            transition: background-color .18s;
        }
        .form-check-input[type=checkbox]:checked {
            background-color: var(--ios-accent);
        }
        .form-check-input[type=checkbox]::before {
            content: '';
            width: 1.15em;
            height: 1.15em;
            border-radius: 50%;
            background: #fff;
            position: absolute;
            top: 0.07em;
            left: 0.07em;
            transition: transform .2s;
            box-shadow: 0 1.5px 6px 0 rgba(0,0,0,0.09);
        }
        .form-check-input[type=checkbox]:checked::before {
            transform: translateX(1em);
        }
        .form-check-label {
            margin-left: 10px;
            font-size: 1.01rem;
            user-select: none;
        }
        @media (max-width: 900px) {
            .register-container {
                flex-direction: column;
                max-width: 98vw;
            }
            .register-left {
                border-radius: var(--ios-border-radius) var(--ios-border-radius) 0 0;
                min-width: unset;
                padding: 38px 30px;
            }
            .register-right {
                padding: 36px 20px;
            }
        }
        @media (max-width: 650px) {
            .register-left {
                display: none;
            }
            .register-right {
                padding: 32px 8px;
            }
            .register-container {
                border-radius: var(--ios-border-radius);
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-left d-none d-md-flex flex-column">
            <h2>Добро пожаловать!</h2>
            <p>Присоединяйтесь к нашей системе управления задачами</p>
            <ul class="feature-list">
                <li><i class="bi bi-kanban-fill"></i> Канбан доска для визуализации задач</li>
                <li><i class="bi bi-bell-fill"></i> Уведомления в Telegram и Email</li>
                <li><i class="bi bi-people-fill"></i> Командная работа и отделы</li>
                <li><i class="bi bi-graph-up-arrow"></i> Отслеживание прогресса в реальном времени</li>
            </ul>
        </div>
        <div class="register-right">
            <h3>Регистрация</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/register" id="registerForm" autocomplete="off">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Имя <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                               required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="department_id" class="form-label">Отдел</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Выберите отдел (опционально)</option>
                        <?php if (isset($departments)): ?>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"
                                        <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password"
                               required
                               autocomplete="new-password">
                        <button class="btn" type="button" id="togglePassword" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <ul class="password-requirements mt-1 mb-0 px-1">
                        <li id="req-length"><i class="bi bi-x-circle text-danger"></i> Минимум 8 символов</li>
                        <li id="req-upper"><i class="bi bi-x-circle text-danger"></i> Одна заглавная буква</li>
                        <li id="req-lower"><i class="bi bi-x-circle text-danger"></i> Одна строчная буква</li>
                        <li id="req-number"><i class="bi bi-x-circle text-danger"></i> Одна цифра</li>
                    </ul>
                </div>
                
                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Подтвердите пароль <span class="text-danger">*</span></label>
                    <input type="password"
                           class="form-control"
                           id="password_confirm"
                           name="password_confirm"
                           required
                           autocomplete="new-password">
                    <div class="invalid-feedback">
                        Пароли не совпадают
                    </div>
                </div>
                
                <div class="form-check form-switch mb-4 d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" id="agreement" name="agreement" required>
                    <label class="form-check-label ms-2" for="agreement">
                        Я согласен с <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">условиями использования</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="bi bi-person-plus me-2"></i> Зарегистрироваться
                </button>
            </form>
            
            <div class="login-link">
                Уже есть аккаунт? <a href="/login">Войдите</a>
            </div>
        </div>
    </div>
    
    <!-- Modal условия использования -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Условия использования</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Добро пожаловать в систему управления задачами. Используя наш сервис, вы соглашаетесь со следующими условиями:</p>
                    <h6>1. Использование сервиса</h6>
                    <p>Вы обязуетесь использовать сервис только в законных целях и в соответствии с применимым законодательством.</p>
                    <h6>2. Конфиденциальность</h6>
                    <p>Мы обязуемся защищать вашу личную информацию в соответствии с нашей политикой конфиденциальности.</p>
                    <h6>3. Ответственность</h6>
                    <p>Вы несете ответственность за сохранность своих учетных данных и за все действия, совершенные под вашей учетной записью.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Переключение видимости пароля
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Проверка силы пароля
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const requirements = {
                length: password.length >= 8,
                upper: /[A-ZА-Я]/.test(password),
                lower: /[a-zа-я]/.test(password),
                number: /[0-9]/.test(password)
            };
            // Обновление индикаторов требований
            updateRequirement('req-length', requirements.length);
            updateRequirement('req-upper', requirements.upper);
            updateRequirement('req-lower', requirements.lower);
            updateRequirement('req-number', requirements.number);
            // Подсчет выполненных требований
            const strength = Object.values(requirements).filter(Boolean).length;
            // Обновление полосы силы пароля
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
        function updateRequirement(id, isValid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            if (isValid) {
                icon.classList.remove('bi-x-circle', 'text-danger');
                icon.classList.add('bi-check-circle', 'text-success');
            } else {
                icon.classList.remove('bi-check-circle', 'text-success');
                icon.classList.add('bi-x-circle', 'text-danger');
            }
        }

        // Проверка совпадения паролей
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Валидация формы перед отправкой
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirm').value;
            if (password !== confirmPassword) {
                e.preventDefault();
                document.getElementById('password_confirm').classList.add('is-invalid');
                return false;
            }
            // Проверка минимальных требований к паролю
            if (password.length < 8) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 8 символов');
                return false;
            }
        });
    </script>
</body>
</html>