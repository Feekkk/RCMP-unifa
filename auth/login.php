<?php
session_start();
require_once __DIR__ . '/../database/database.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $login_error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, full_name, email, role, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ../index.php');
            exit;
        } else {
            $login_error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — RCMP UniFa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f1419;
            background: #05070b;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .page-bg {
            position: fixed;
            inset: 0;
            background: url("../public/bgm.png") center center / cover no-repeat;
            filter: blur(10px);
            transform: scale(1.06);
            opacity: 0.85;
            z-index: -2;
        }
        .page-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at top, rgba(0,0,0,0.5), rgba(0,0,0,0.9));
            z-index: -1;
        }
        .shell {
            width: min(1040px, 100% - 3rem);
            border-radius: 26px;
            background: rgba(6, 10, 18, 0.96);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 24px 80px rgba(0,0,0,0.7);
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
            overflow: hidden;
        }
        .shell-left {
            position: relative;
            padding: 1.75rem 1.75rem 1.75rem 1.75rem;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.15), transparent 40%);
        }
        .shell-left-inner {
            position: relative;
            height: 100%;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.22);
        }
        .shell-left-photo {
            position: absolute;
            inset: 0;
            background: url("../public/bgm.png") center center / cover no-repeat;
            transform: scale(1.06);
            filter: saturate(1.1);
        }
        .shell-left-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(5,7,11,0.2) 0%, rgba(5,7,11,0.95) 80%);
        }
        .shell-left-content {
            position: relative;
            height: 100%;
            padding: 1.8rem 2.2rem 2.3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #fff;
        }
        .left-eyebrow {
            font-size: 0.7rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            opacity: 0.75;
        }
        .left-quote {
            margin-top: 0.6rem;
            font-family: 'Playfair Display', serif;
            font-size: 2.1rem;
            line-height: 1.2;
        }
        .left-sub {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.85);
        }
        .left-meta {
            font-size: 0.75rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.75);
        }
        .shell-right {
            padding: 2.25rem 2.5rem 2.4rem;
            background: #f9fafb;
        }
        .right-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
        }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #111827;
        }
        .brand-mark img {
            height: 40px;
            width: auto;
        }
        .brand-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #111827;
            color: #f9fafb;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            margin-bottom: 0.3rem;
            color: #111827;
        }
        .form-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 1.6rem;
        }
        .field {
            margin-bottom: 1rem;
        }
        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.4rem;
        }
        .field input[type="email"],
        .field input[type="password"] {
            width: 100%;
            border-radius: 0.9rem;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 0.9rem;
            font-size: 0.9rem;
            outline: none;
            background: #f9fafb;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .field input:focus {
            border-color: #111827;
            background: #ffffff;
            box-shadow: 0 0 0 1px #1118270d;
        }
        .field-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.1rem;
        }
        .field-row label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #4b5563;
            cursor: pointer;
        }
        .field-row input[type="checkbox"] {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
        }
        .field-row a {
            font-size: 0.8rem;
            color: #111827;
            text-decoration: none;
        }
        .field-row a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border-radius: 999px;
            padding: 0.8rem 1rem;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.1s ease, background 0.15s;
        }
        .btn-primary {
            background: #0f1419;
            color: #f9fafb;
            box-shadow: 0 12px 30px rgba(15,20,25,0.5);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 40px rgba(15,20,25,0.6);
        }
        .btn-ghost {
            margin-top: 0.7rem;
            background: #ffffff;
            color: #111827;
            border: 1px solid #e5e7eb;
            box-shadow: 0 0 0 rgba(0,0,0,0);
        }
        .btn-ghost:hover {
            background: #f3f4f6;
        }
        .btn-ghost img {
            height: 18px;
            margin-right: 0.4rem;
        }
        .form-footer {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #6b7280;
            text-align: center;
        }
        .form-footer a {
            color: #111827;
            text-decoration: none;
            font-weight: 500;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
        @media (max-width: 900px) {
            .shell {
                grid-template-columns: minmax(0, 1fr);
                border-radius: 22px;
            }
            .shell-left {
                display: none;
            }
            .shell-right {
                padding: 2rem 1.75rem 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    <div class="shell">
        <div class="shell-left">
            <div class="shell-left-inner">
                <div class="shell-left-photo"></div>
                <div class="shell-left-gradient"></div>
                <div class="shell-left-content">
                    <div>
                        <div class="left-eyebrow">Financial Aid for Futures</div>
                        <div class="left-quote">Support that lets you focus on what matters most.</div>
                        <p class="left-sub">
                            RCMP UniFa helps UniKL students stay on track with compassionate, structured financial assistance.
                        </p>
                    </div>
                    <div class="left-meta">UniKL &middot; Royal College of Medicine Perak</div>
                </div>
            </div>
        </div>
        <div class="shell-right">
            <div class="right-header">
                <div class="brand-mark">
                    <img src="../public/official-logo.png" alt="UniKL RCMP">
                </div>
                <span class="brand-badge">RCMP UniFa</span>
            </div>
            <h1 class="form-title">Welcome back</h1>
            <p class="form-subtitle">Enter your email and password to access your UniFa account.</p>
            <?php if (!empty($login_error)): ?>
                <p style="color:#b91c1c; font-size:0.85rem; margin-bottom:1rem; background:#fee2e2; border-radius:0.75rem; padding:0.65rem 0.8rem;">
                    <?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
            <form action="" method="post" novalidate>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password">
                </div>
                <div class="field-row">
                    <label for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-primary">Sign in</button>
                <a href="../index.php" class="btn btn-ghost">Back to home</a>
                <div class="form-footer">
                    Don&rsquo;t have an account?
                    <a href="register.php">Sign up</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>