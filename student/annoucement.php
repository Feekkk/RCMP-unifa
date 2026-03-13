<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Student';

// Fetch all active announcements
$announcements = [];
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS announcements (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(255) NOT NULL,
            body        TEXT,
            type        ENUM('info','warning','success') NOT NULL DEFAULT 'info',
            is_active   TINYINT(1) NOT NULL DEFAULT 1,
            pinned      TINYINT(1) NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at  DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $stmt = $pdo->query("
        SELECT id, title, body, type, pinned, created_at
        FROM   announcements
        WHERE  is_active = 1
          AND  (expires_at IS NULL OR expires_at > NOW())
        ORDER  BY pinned DESC, created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Notification count for sidebar bell
$unreadCount = 0;
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $unreadCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements — RCMP UniFa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #f9fafb;
            color: #111827;
        }

        /* ── Background ── */
        .page-bg {
            position: fixed; inset: 0;
            background: url("../public/bgm.png") center center / cover no-repeat;
            filter: blur(12px);
            transform: scale(1.05);
            opacity: 0.15;
            z-index: -2;
        }
        .page-overlay {
            position: fixed; inset: 0;
            background: linear-gradient(180deg, rgba(249,250,251,0.7) 0%, #f9fafb 50%);
            z-index: -1;
        }

        /* ── Layout ── */
        .app { position: relative; z-index: 1; display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 0 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }
        .sidebar-brand img { height: 48px; width: auto; object-fit: contain; }
        .sidebar-nav { flex: 1; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 1.25rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-nav a:hover { background: #f3f4f6; color: #111827; }
        .sidebar-nav a.active {
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            font-weight: 500;
        }
        .sidebar-nav a svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer {
            padding: 1rem 1.25rem 0;
            border-top: 1px solid #e5e7eb;
        }
        .sidebar-logout {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.7rem 1.25rem;
            color: #dc2626; text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-logout:hover { background: #fef2f2; color: #b91c1c; }
        .sidebar-logout svg { width: 20px; height: 20px; flex-shrink: 0; }
        .nav-badge {
            margin-left: auto;
            font-size: 0.7rem;
            font-weight: 700;
            background: #6366f1;
            color: #fff;
            border-radius: 999px;
            padding: 0.1rem 0.45rem;
        }

        /* ── Main Content ── */
        .main-content {
            flex: 1; min-width: 0;
            padding: 1.5rem 2.5rem 3rem;
            overflow-x: hidden;
        }

        /* ── Page Header ── */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-eyebrow {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 0.35rem;
        }
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-title svg { width: 28px; height: 28px; color: #6b7280; }
        .page-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 0.4rem;
        }
        .title-divider {
            height: 1px;
            background: linear-gradient(90deg, #e5e7eb 0%, transparent 100%);
            margin: 1.5rem 0;
        }

        /* ── Announcement Cards ── */
        .ann-list { display: flex; flex-direction: column; gap: 1rem; }

        .ann-card {
            border-radius: 18px;
            padding: 1.4rem 1.75rem;
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .ann-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        /* Accent left border by type */
        .ann-card--info    { border-left: 5px solid #6366f1; }
        .ann-card--warning { border-left: 5px solid #f59e0b; }
        .ann-card--success { border-left: 5px solid #10b981; }

        /* Subtle bg tint */
        .ann-card--info::before {
            content: '';
            position: absolute; top: 0; right: 0; width: 35%; height: 100%;
            background: radial-gradient(circle at 100% 50%, rgba(99,102,241,0.04), transparent 70%);
            pointer-events: none;
        }
        .ann-card--warning::before {
            content: '';
            position: absolute; top: 0; right: 0; width: 35%; height: 100%;
            background: radial-gradient(circle at 100% 50%, rgba(245,158,11,0.04), transparent 70%);
            pointer-events: none;
        }
        .ann-card--success::before {
            content: '';
            position: absolute; top: 0; right: 0; width: 35%; height: 100%;
            background: radial-gradient(circle at 100% 50%, rgba(16,185,129,0.04), transparent 70%);
            pointer-events: none;
        }

        .ann-icon {
            width: 46px; height: 46px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .ann-icon svg { width: 22px; height: 22px; }
        .ann-card--info    .ann-icon { background: rgba(99,102,241,0.1);  color: #6366f1; }
        .ann-card--warning .ann-icon { background: rgba(245,158,11,0.1);  color: #d97706; }
        .ann-card--success .ann-icon { background: rgba(16,185,129,0.1);  color: #059669; }

        .ann-body { flex: 1; min-width: 0; }
        .ann-title {
            font-weight: 600;
            font-size: 1rem;
            color: #111827;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .ann-pin {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #fff;
            background: #f59e0b;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
        }
        .ann-type-badge {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
        }
        .ann-type-badge--info    { background: rgba(99,102,241,0.1);  color: #6366f1; }
        .ann-type-badge--warning { background: rgba(245,158,11,0.12); color: #d97706; }
        .ann-type-badge--success { background: rgba(16,185,129,0.1);  color: #059669; }

        .ann-text {
            font-size: 0.92rem;
            color: #4b5563;
            line-height: 1.65;
            white-space: pre-line;
        }
        .ann-date {
            font-size: 0.78rem;
            color: #9ca3af;
            margin-top: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .ann-date svg { width: 13px; height: 13px; }

        /* ── Empty State ── */
        .ann-empty {
            text-align: center;
            padding: 5rem 2rem;
            border-radius: 20px;
            background: #ffffff;
            border: 1.5px dashed #e5e7eb;
            color: #9ca3af;
        }
        .ann-empty svg {
            width: 52px; height: 52px;
            margin: 0 auto 1.25rem;
            display: block;
            opacity: 0.3;
        }
        .ann-empty p { font-size: 1rem; margin-bottom: 0.3rem; color: #6b7280; font-weight: 500; }
        .ann-empty span { font-size: 0.875rem; }

        /* ── Footer ── */
        .page-footer {
            text-align: right;
            padding: 1rem 0;
            margin-top: 2.5rem;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
                padding: 0.75rem 1rem;
                gap: 0.25rem;
            }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
            .main-content { padding: 1.25rem 1rem 2rem; }
        }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>

    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../public/official-logo.png" alt="RCMP UniFa">
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="appInformation.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Application info
                </a>
                <a href="application.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Application form
                </a>
                <a href="history.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    View history
                </a>
                <a href="annoucement.php" class="active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Announcements
                    <?php if (count($announcements) > 0): ?>
                        <span class="nav-badge"><?php echo count($announcements); ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Announcements
                </h1>
                <p class="page-subtitle">
                    <?php if (!empty($announcements)): ?>
                        <?php echo count($announcements); ?> active announcement<?php echo count($announcements) !== 1 ? 's' : ''; ?> from the administration.
                    <?php else: ?>
                        All official notices and updates from the administration.
                    <?php endif; ?>
                </p>
            </div>

            <div class="title-divider"></div>

            <?php if (empty($announcements)): ?>
                <div class="ann-empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.006 6.006 0 00-4.949-5.893A3.5 3.5 0 009.05 5.1M15 17v1a3 3 0 01-6 0v-1m6 0H9"/>
                    </svg>
                    <p>No announcements yet</p>
                    <span>Check back later for updates from the administration.</span>
                </div>
            <?php else: ?>
                <div class="ann-list">
                    <?php
                    $icons = [
                        'info'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ];
                    $typeLabels = ['info' => 'Info', 'warning' => 'Notice', 'success' => 'Update'];
                    foreach ($announcements as $ann):
                        $t = $ann['type'];
                        $iconPath = $icons[$t] ?? $icons['info'];
                        $typeLabel = $typeLabels[$t] ?? 'Info';
                    ?>
                    <div class="ann-card ann-card--<?php echo htmlspecialchars($t); ?>">
                        <div class="ann-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $iconPath; ?></svg>
                        </div>
                        <div class="ann-body">
                            <div class="ann-title">
                                <?php echo htmlspecialchars($ann['title']); ?>
                                <?php if ((int)$ann['pinned']): ?>
                                    <span class="ann-pin">📌 Pinned</span>
                                <?php endif; ?>
                                <span class="ann-type-badge ann-type-badge--<?php echo htmlspecialchars($t); ?>"><?php echo $typeLabel; ?></span>
                            </div>
                            <?php if (!empty($ann['body'])): ?>
                                <div class="ann-text"><?php echo htmlspecialchars($ann['body']); ?></div>
                            <?php endif; ?>
                            <div class="ann-date">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <?php echo date('d M Y, g:i A', strtotime($ann['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <footer class="page-footer">&copy; University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
