<?php
// views/shares/password.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ к файлу защищен паролем</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .password-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .password-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-unlock {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-unlock:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="password-container mx-auto">
                    
                    <div class="password-icon">
                        <i class="bi bi-lock"></i>
                    </div>
                    
                    <h2 class="text-center mb-1 h4">Файл защищен паролем</h2>
                    <p class="text-center text-muted mb-4 small">
                        Введите пароль для доступа к файлу
                    </p>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="error-message">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Неверный пароль. Попробуйте еще раз.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/s/<?= htmlspecialchars($shortCode) ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Введите пароль"
                                       required 
                                       autofocus>
                                <button type="button" 
                                        class="btn btn-outline-secondary" 
                                        onclick="togglePasswordVisibility()"
                                        id="togglePassword">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-unlock">
                            <i class="bi bi-unlock me-2"></i>
                            Получить доступ
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            Безопасный доступ к файлам
                        </small>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }

        // Автофокус на поле пароля
        document.getElementById('password').focus();

        // Показ ошибки при неверном пароле
        <?php if (isset($_GET['error'])): ?>
        setTimeout(function() {
            document.querySelector('.error-message')?.remove();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>