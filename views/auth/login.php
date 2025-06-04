<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- iOS style font -->
    <link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(120deg, #e3f0ff 0%, #f5faff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'SF Pro Display', Arial, sans-serif;
            letter-spacing: 0.01em;
        }
        .container {
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 2rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: box-shadow 0.3s;
            padding: 2.2rem 2rem 2rem 2rem;
        }
        .card:hover {
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.18);
        }
        .card-header {
            background: none;
            border-bottom: none;
            text-align: center;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        h4 {
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.01em;
            font-size: 2rem;
        }
        .form-label {
            font-weight: 500;
            color: #313A4C;
            letter-spacing: 0.01em;
            margin-bottom: .25rem;
        }
        .form-control {
            border-radius: 1.2rem;
            padding: 1.1rem 1rem;
            font-size: 1.15rem;
            background: #f8fafc;
            border: 1px solid #e5e9f2;
            box-shadow: 0 2px 10px rgba(80, 150, 255, 0.03);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #4f8eff;
            box-shadow: 0 0 0 0.12rem rgba(79, 142, 255, 0.15);
            background: #f0f6ff;
        }
        .btn-primary {
            background: linear-gradient(90deg, #4f8eff 0%, #30cfd0 100%);
            border: none;
            border-radius: 1.2rem;
            font-weight: 600;
            font-size: 1.18rem;
            letter-spacing: 0.02em;
            padding: 0.85rem 0;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 2px 9px rgba(79, 142, 255, 0.09);
        }
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(90deg, #3a7bd5 0%, #30cfd0 100%);
            box-shadow: 0 4px 16px rgba(79, 142, 255, 0.18);
        }
        .alert {
            border-radius: 1.1rem;
            font-size: 1.02rem;
        }
        .register-link {
            color: #4f8eff;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            transition: color 0.18s;
        }
        .register-link:hover {
            color: #30cfd0;
            text-decoration: underline;
        }
        .divider {
            display: block;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, rgba(79,142,255,0.12) 0%, rgba(48,207,208,0.12) 100%);
            margin: 2rem 0 1.2rem 0;
            border: none;
            border-radius: 1px;
        }
        @media (max-width: 576px) {
            .card {
                margin: 1rem;
                border-radius: 1.2rem;
                padding: 1.5rem 0.9rem;
            }
            h4 {
                font-size: 1.45rem;
            }
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="card shadow-lg">
                <div class="card-header">
                    <h4>Вход в систему</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST" autocomplete="on">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required autofocus autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" class="form-control" name="password" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                    <hr class="divider">
                    <div class="text-center">
                        <a href="/register" class="register-link">Регистрация</a>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</body>
</html>