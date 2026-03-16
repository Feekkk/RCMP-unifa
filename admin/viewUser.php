<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$type = isset($_GET['type']) && $_GET['type'] === 'staff' ? 'staff' : 'student';
$id = (int) ($_GET['id'] ?? 0);
$user = null;

if ($id > 0) {
    try {
        if ($type === 'student') {
            $stmt = $pdo->prepare('SELECT id, full_name, email, phone, address, bank_name, bank_account, role, created_at FROM users WHERE id = ? AND role = "student"');
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare('SELECT id, staff_id, full_name, email, phone, created_at FROM staff WHERE id = ? AND role = 1');
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}

if (!$user) {
    header('Location: manageUser.php');
    exit;
}

function formatDate($d) {
    return $d ? date('d M Y, H:i', strtotime($d)) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User — RCMP UniFa</title>
    <link rel="icon" href="../public/title-white.png" type="image/png">
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
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 10px; background: #fff; color: #4b5563; font-size: 0.875rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .btn-back:hover { background: #f3f4f6; color: #111827; }
        .btn-back svg { width: 18px; height: 18px; }
        .detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .detail-header { padding: 2rem 1.75rem; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .detail-avatar { width: 72px; height: 72px; border-radius: 50%; background: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 600; flex-shrink: 0; }
        .detail-header-text h2 { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem; }
        .detail-header-text .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 500; text-transform: capitalize; background: #e0e7ff; color: #4338ca; }
        .detail-body { padding: 1.75rem; }
        .detail-row { display: grid; grid-template-columns: 160px 1fr; gap: 0.75rem 1.5rem; align-items: start; padding: 0.65rem 0; border-bottom: 1px solid #f3f4f6; }
        .detail-row:last-child { border-bottom: 0; }
        .detail-label { font-size: 0.8rem; font-weight: 500; color: #6b7280; }
        .detail-value { font-size: 0.95rem; color: #111827; }
        .detail-value.empty { color: #9ca3af; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 2rem; font-size: 0.8rem; color: #9ca3af; }
        @media (max-width: 600px) { .detail-row { grid-template-columns: 1fr; } }
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
                <a href="applications.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application</a>
                <a href="manageUser.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage user</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
                <a href="announcement.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>Announcements</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $type === 'staff' ? 'Staff details' : 'Student details'; ?></h1>
                <a href="manageUser.php" class="btn-back"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Back to Manage user</a>
            </div>

            <div class="detail-card">
                <div class="detail-header">
                    <div class="detail-avatar"><?php echo strtoupper(mb_substr($user['full_name'] ?? 'U', 0, 1)); ?></div>
                    <div class="detail-header-text">
                        <h2><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h2>
                        <span class="badge"><?php echo $type === 'staff' ? 'Staff' : 'Student'; ?></span>
                    </div>
                </div>
                <div class="detail-body">
                    <?php if ($type === 'staff'): ?>
                        <div class="detail-row">
                            <span class="detail-label">Staff ID</span>
                            <span class="detail-value"><?php echo htmlspecialchars($user['staff_id'] ?? '—'); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Full name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['full_name'] ?? '—'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email'] ?? '—'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value <?php echo empty($user['phone']) ? 'empty' : ''; ?>"><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></span>
                    </div>
                    <?php if ($type === 'student'): ?>
                        <div class="detail-row">
                            <span class="detail-label">Address</span>
                            <span class="detail-value <?php echo empty($user['address']) ? 'empty' : ''; ?>"><?php echo nl2br(htmlspecialchars($user['address'] ?? '—')); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Bank name</span>
                            <span class="detail-value <?php echo empty($user['bank_name']) ? 'empty' : ''; ?>"><?php echo htmlspecialchars($user['bank_name'] ?? '—'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Bank account</span>
                            <span class="detail-value <?php echo empty($user['bank_account']) ? 'empty' : ''; ?>"><?php echo htmlspecialchars($user['bank_account'] ?? '—'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Role</span>
                            <span class="detail-value"><?php echo htmlspecialchars(ucfirst($user['role'] ?? 'student')); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Registered</span>
                        <span class="detail-value"><?php echo formatDate($user['created_at'] ?? null); ?></span>
                    </div>
                </div>
            </div>
            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
