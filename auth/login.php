<?php
session_start();
require_once __DIR__ . '/../database/database.php';

$login_error = '';
$login_type = trim($_POST['login_type'] ?? $_GET['type'] ?? 'student');
if (!in_array($login_type, ['student', 'staff'])) $login_type = 'student';
$email = '';
$staff_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = trim($_POST['login_type'] ?? 'student');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $staff_id = trim($_POST['staff_id'] ?? '');

    if ($login_type === 'staff') {
        if ($staff_id === '' || $password === '') {
            $login_error = 'Please enter staff ID and password.';
        } else {
            $row = null;
            try {
                $stmt = $pdo->prepare('SELECT id, staff_id, full_name, email, password_hash, role FROM staff WHERE LOWER(TRIM(staff_id)) = LOWER(?) LIMIT 1');
                $stmt->execute([$staff_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $row = null;
            }
            $hash = $row['password_hash'] ?? '';
            $pwdOk = $row && (
                $password === (string) $hash ||
                (strlen($hash) > 20 && password_verify($password, $hash))
            );
            if ($pwdOk) {
                $role = (int) $row['role'];
                if ($role === 2) {
                    $_SESSION['committee_id'] = (int) $row['id'];
                    $_SESSION['committee_staff_id'] = $row['staff_id'];
                    $_SESSION['user_name'] = $row['full_name'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_role'] = 'committee';
                    header('Location: ../committee/dashboard.php');
                } elseif ($role === 3) {
                    $_SESSION['ceo_id'] = (int) $row['id'];
                    $_SESSION['ceo_staff_id'] = $row['staff_id'];
                    $_SESSION['user_name'] = $row['full_name'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_role'] = 'ceo';
                    header('Location: ../ceo/dashboard.php');
                } else {
                    $_SESSION['admin_id'] = (int) $row['id'];
                    $_SESSION['admin_staff_id'] = $row['staff_id'];
                    $_SESSION['user_name'] = $row['full_name'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_role'] = 'admin';
                    header('Location: ../admin/dashboard.php');
                }
                exit;
            }
            $login_error = 'Invalid staff ID or password.';
        }
    } else {
        $email = trim($_POST['email'] ?? '');
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
                header('Location: ../student/dashboard.php');
                exit;
            } else {
                $login_error = 'Invalid email or password.';
            }
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
            margin-bottom: 0.35rem;
        }
        .form-footer-top {
            font-size: 0.8rem;
            color: #6b7280;
            text-align: left;
            margin-bottom: 1.25rem;
        }
        .form-footer-top a {
            color: #111827;
            text-decoration: none;
            font-weight: 500;
        }
        .form-footer-top a:hover {
            text-decoration: underline;
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
        .field input[type="password"],
        .field input[type="text"].pw-field {
            width: 100%;
            border-radius: 0.9rem;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 2.75rem 0.75rem 0.9rem;
            font-size: 0.9rem;
            outline: none;
            background: #f9fafb;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .pw-wrap { position: relative; }
        .pw-wrap .btn-toggle-pw {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        .pw-wrap .btn-toggle-pw:hover { color: #111827; background: #f3f4f6; }
        .pw-wrap .btn-toggle-pw svg { width: 18px; height: 18px; }
        .field input:focus {
            border-color: #111827;
            background: #ffffff;
            box-shadow: 0 0 0 1px #1118270d;
        }
        .field-hint {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #6b7280;
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
        .login-tabs {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1.25rem;
            background: #e5e7eb;
            border-radius: 999px;
            padding: 0.25rem;
        }
        .login-tabs button {
            flex: 1;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            background: transparent;
            color: #6b7280;
            transition: background 0.15s, color 0.15s;
        }
        .login-tabs button.active {
            background: #fff;
            color: #111827;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .login-form-block { display: none; }
        .login-form-block.active { display: block; }
        .field input[type="text"] {
            width: 100%;
            border-radius: 0.9rem;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 0.9rem;
            font-size: 0.9rem;
            outline: none;
            background: #f9fafb;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .field input[type="text"]:focus {
            border-color: #111827;
            background: #ffffff;
            box-shadow: 0 0 0 1px #1118270d;
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
            <p class="form-subtitle" id="formSubtitle">Enter your email and password to access your UniFa account.</p>
            <p class="form-footer-top">Don&rsquo;t have an account? <a href="register.php">Sign up</a></p>
            <?php if (!empty($login_error)): ?>
                <p style="color:#b91c1c; font-size:0.85rem; margin-bottom:1rem; background:#fee2e2; border-radius:0.75rem; padding:0.65rem 0.8rem;">
                    <?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
            <form action="" method="post" novalidate id="loginForm" autocomplete="off">
                <input type="hidden" name="login_type" id="loginType" value="<?php echo htmlspecialchars($login_type); ?>">
                <div class="login-tabs">
                    <button type="button" class="login-tab active" data-type="student">Student</button>
                    <button type="button" class="login-tab" data-type="staff">Staff</button>
                </div>
                <div id="blockStudent" class="login-form-block <?php echo $login_type === 'student' ? 'active' : ''; ?>">
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                </div>
                <div id="blockStaff" class="login-form-block <?php echo $login_type === 'staff' ? 'active' : ''; ?>">
                    <div class="field">
                        <label for="staff_id">Staff ID</label>
                        <input type="text" id="staff_id" name="staff_id" placeholder="Enter your staff ID" value="<?php echo isset($staff_id) ? htmlspecialchars($staff_id, ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="off">
                        <button type="button" class="btn-toggle-pw" aria-label="Show password" title="Show password">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                    <p class="field-hint">Forgot password? Please contact admin.</p>
                </div>
                <button type="submit" class="btn btn-primary">Sign in</button>
                <a href="../index.php" class="btn btn-ghost">Back to home</a>
            </form>
            <script>
            (function() {
                var form = document.getElementById('loginForm');
                var type = document.getElementById('loginType');
                var blockStudent = document.getElementById('blockStudent');
                var blockStaff = document.getElementById('blockStaff');
                var subtitle = document.getElementById('formSubtitle');
                var tabs = document.querySelectorAll('.login-tab');
                var subtitles = { student: 'Enter your email and password to access your UniFa account.', staff: 'Enter your staff ID and password.' };
                function show(t) {
                    type.value = t;
                    blockStudent.classList.toggle('active', t === 'student');
                    blockStaff.classList.toggle('active', t === 'staff');
                    subtitle.textContent = subtitles[t] || subtitles.student;
                    tabs.forEach(function(btn) { btn.classList.toggle('active', btn.getAttribute('data-type') === t); });
                }
                tabs.forEach(function(btn) {
                    btn.addEventListener('click', function() { show(btn.getAttribute('data-type')); });
                });
                form.addEventListener('submit', function() {
                    if (blockStaff.classList.contains('active')) type.value = 'staff';
                });
                var pwInput = document.getElementById('password');
                var pwBtn = form.querySelector('.btn-toggle-pw');
                if (pwBtn && pwInput) {
                    pwBtn.addEventListener('click', function() {
                        var isPw = pwInput.type === 'password';
                        pwInput.type = isPw ? 'text' : 'password';
                        pwBtn.setAttribute('aria-label', isPw ? 'Hide password' : 'Show password');
                        pwBtn.setAttribute('title', isPw ? 'Hide password' : 'Show password');
                        pwBtn.innerHTML = isPw
                            ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>'
                            : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
                    });
                }
            })();
            </script>
        </div>
    </div>
</body>
</html>