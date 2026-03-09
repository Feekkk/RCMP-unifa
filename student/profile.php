<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$profile_success = '';
$profile_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');
    try {
        $stmt = $pdo->prepare('UPDATE users SET phone = ?, address = ?, bank_name = ?, bank_account = ? WHERE id = ?');
        $stmt->execute([$phone, $address, $bank_name, $bank_account, $userId]);
        $profile_success = 'Profile updated successfully.';
    } catch (PDOException $e) {
        $profile_error = 'Could not update profile. Please try again.';
    }
}

$user = null;
try {
    $stmt = $pdo->prepare('SELECT full_name, email, phone, address, bank_name, bank_account, role, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = [
        'full_name' => $_SESSION['user_name'] ?? 'Student',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => '',
        'address' => '',
        'bank_name' => '',
        'bank_account' => '',
        'role' => 'student',
        'created_at' => null
    ];
}
if (!$user) {
    $user = ['full_name' => '—', 'email' => '—', 'phone' => '', 'address' => '', 'bank_name' => '', 'bank_account' => '', 'role' => 'student', 'created_at' => null];
}
$initial = strtoupper(mb_substr($user['full_name'], 0, 1));
$memberSince = $user['created_at'] ? date('F Y', strtotime($user['created_at'])) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — RCMP UniFa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: #f9fafb; color: #111827; }
        .page-bg { position: fixed; inset: 0; background: url("../public/bgm.png") center/cover no-repeat; filter: blur(12px); transform: scale(1.05); opacity: 0.15; z-index: -2; }
        .page-overlay { position: fixed; inset: 0; background: linear-gradient(180deg, rgba(249,250,251,0.7) 0%, #f9fafb 50%); z-index: -1; }
        .app { position: relative; z-index: 1; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; flex-shrink: 0; background: #fff; border-right: 1px solid #e5e7eb; padding: 1.5rem 0; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 0 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem; }
        .sidebar-brand img { height: 48px; width: auto; object-fit: contain; }
        .sidebar-nav { flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; color: #4b5563; text-decoration: none; font-size: 0.9rem; transition: background 0.15s, color 0.15s; }
        .sidebar-nav a:hover { background: #f3f4f6; color: #111827; }
        .sidebar-nav a.active { background: rgba(79, 70, 229, 0.08); color: #4f46e5; font-weight: 500; }
        .sidebar-nav a svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem 1.25rem 0; border-top: 1px solid #e5e7eb; }
        .sidebar-logout { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; color: #dc2626; text-decoration: none; font-size: 0.9rem; transition: background 0.15s, color 0.15s; }
        .sidebar-logout:hover { background: #fef2f2; color: #b91c1c; }
        .sidebar-logout svg { width: 20px; height: 20px; flex-shrink: 0; }
        .main-content { flex: 1; min-width: 0; padding: 1.5rem 2rem 2rem; overflow-x: hidden; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; margin: 0; }
        .btn-home { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 10px; background: #fff; color: #4b5563; font-size: 0.875rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .btn-home:hover { background: #f3f4f6; color: #111827; }
        .btn-home svg { width: 18px; height: 18px; }
        .profile-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .profile-header { padding: 2rem 1.75rem; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .profile-avatar { width: 72px; height: 72px; border-radius: 50%; background: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 600; flex-shrink: 0; }
        .profile-header-text h2 { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem; }
        .profile-header-text p { font-size: 0.9rem; color: #6b7280; }
        .profile-body { padding: 1.75rem; }
        .profile-row { display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem 1.5rem; align-items: start; padding: 0.65rem 0; border-bottom: 1px solid #f3f4f6; }
        .profile-row:last-child { border-bottom: 0; }
        .profile-label { font-size: 0.8rem; font-weight: 500; color: #6b7280; }
        .profile-value { font-size: 0.95rem; color: #111827; }
        .profile-value.empty { color: #9ca3af; }
        .profile-body input, .profile-body textarea { width: 100%; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.6rem 0.85rem; font-size: 0.9rem; outline: none; background: #fff; transition: border-color 0.15s; font-family: inherit; }
        .profile-body input:focus, .profile-body textarea:focus { border-color: #4f46e5; }
        .profile-body textarea { min-height: 80px; resize: vertical; }
        .btn-save { display: inline-flex; align-items: center; justify-content: center; padding: 0.65rem 1.5rem; border-radius: 999px; background: #0f1419; color: #f9fafb; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; margin-top: 1rem; transition: transform 0.1s, box-shadow 0.15s; }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,20,25,0.3); }
        .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 2rem; font-size: 0.8rem; color: #9ca3af; }
        @media (max-width: 600px) {
            .profile-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    <div class="app">
        <aside class="sidebar">
            <div class="sidebar-brand"><img src="../public/official-logo.png" alt="RCMP UniFa"></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>Dashboard</a>
                <a href="appInformation.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Application info</a>
                <a href="application.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application form</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>View history</a>
                <a href="profile.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Profile</h1>
                <a href="dashboard.php" class="btn-home"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home</a>
            </div>

            <?php if ($profile_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($profile_success); ?></div><?php endif; ?>
            <?php if ($profile_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($profile_error); ?></div><?php endif; ?>

            <form method="post" action="">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo htmlspecialchars($initial); ?></div>
                    <div class="profile-header-text">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p>Member since <?php echo htmlspecialchars($memberSince); ?></p>
                    </div>
                </div>
                <div class="profile-body">
                    <div class="profile-row">
                        <span class="profile-label">Full name</span>
                        <span class="profile-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Email</span>
                        <span class="profile-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Phone</span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +60 12-345 6789">
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Address</span>
                        <textarea name="address" placeholder="Current correspondence address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Bank name</span>
                        <input type="text" name="bank_name" value="<?php echo htmlspecialchars($user['bank_name'] ?? ''); ?>" placeholder="e.g. Maybank, CIMB">
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Bank account</span>
                        <input type="text" name="bank_account" value="<?php echo htmlspecialchars($user['bank_account'] ?? ''); ?>" placeholder="Account number">
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Role</span>
                        <span class="profile-value"><?php echo htmlspecialchars(ucfirst($user['role'] ?? 'student')); ?></span>
                    </div>
                    <button type="submit" class="btn-save">Save changes</button>
                </div>
            </div>
            </form>
            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
