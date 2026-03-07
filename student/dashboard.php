<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Student';

$applicationsSubmitted = 0;
$pendingReview = 0;
$approvedOrDisbursed = 0;
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE user_id = ?');
    $stmt->execute([$userId]);
    $applicationsSubmitted = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE user_id = ? AND status_id IN (1, 2)');
    $stmt->execute([$userId]);
    $pendingReview = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE user_id = ? AND status_id IN (3, 5)');
    $stmt->execute([$userId]);
    $approvedOrDisbursed = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    // applications table not created yet; counts stay 0
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — RCMP UniFa</title>
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
        .page-bg {
            position: fixed;
            inset: 0;
            background: url("../public/bgm.png") center center / cover no-repeat;
            filter: blur(12px);
            transform: scale(1.05);
            opacity: 0.15;
            z-index: -2;
        }
        .page-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(249,250,251,0.7) 0%, #f9fafb 50%);
            z-index: -1;
        }
        .app {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
        }
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
        .sidebar-brand img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }
        .sidebar-nav {
            flex: 1;
        }
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
        .sidebar-nav a:hover {
            background: #f3f4f6;
            color: #111827;
        }
        .sidebar-nav a.active {
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            font-weight: 500;
        }
        .sidebar-nav a svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .sidebar-footer {
            padding: 1rem 1.25rem 0;
            border-top: 1px solid #e5e7eb;
        }
        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 1.25rem;
            color: #dc2626;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-logout:hover {
            background: #fef2f2;
            color: #b91c1c;
        }
        .sidebar-logout svg { width: 20px; height: 20px; flex-shrink: 0; }
        .main-content {
            flex: 1;
            min-width: 0;
            padding: 1.5rem 2rem 2rem;
            overflow-x: hidden;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-mark img {
            height: 60px;
            width: auto;
            object-fit: contain;
        }
        .brand-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #ffffff;
            color: #111827;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .dashboard-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #111827;
        }
        .top-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #4b5563;
            cursor: pointer;
            position: relative;
            transition: background 0.15s, border-color 0.15s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn-icon:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #111827;
        }
        .btn-icon svg { width: 20px; height: 20px; }
        .notif-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }
        .search-wrap {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.5rem 0.85rem;
            min-width: 200px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .search-wrap svg { width: 18px; height: 18px; color: #9ca3af; flex-shrink: 0; margin-right: 0.5rem; }
        .search-wrap input {
            background: none;
            border: none;
            color: #111827;
            font-size: 0.9rem;
            outline: none;
            width: 100%;
        }
        .search-wrap input::placeholder { color: #9ca3af; }
        .kbd-hint {
            font-size: 0.75rem;
            color: #6b7280;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .user-menu a {
            color: #4b5563;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.15s;
        }
        .user-menu a:hover { color: #111827; }
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
        }
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
        }
        .hero-card {
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%);
            border: 1px solid #e5e7eb;
            padding: 2rem 2.25rem;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
        }
        .hero-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: radial-gradient(circle at 80% 30%, rgba(79, 70, 229, 0.06), transparent 50%);
            pointer-events: none;
        }
        .hero-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        .hero-card p {
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.5;
            max-width: 320px;
        }
        .hero-card .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 1.25rem;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            background: #0f1419;
            color: #f9fafb;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.15s;
            width: fit-content;
        }
        .hero-card .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15,20,25,0.3);
        }
        .stat-cards {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .stat-card {
            border-radius: 18px;
            padding: 1.35rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 100px;
        }
        .stat-card--dark {
            background: #111827;
            color: #f9fafb;
            border: 1px solid #1f2937;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        .stat-card--purple {
            background: linear-gradient(135deg, #4c1d95 0%, #5b21b6 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(76, 29, 149, 0.25);
        }
        .stat-card--light {
            background: #ffffff;
            color: #111827;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .stat-card .num {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        .stat-card .label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        .stat-card .icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            flex-shrink: 0;
        }
        .stat-card--dark .icon { background: rgba(255,255,255,0.12); }
        .stat-card--purple .icon { background: rgba(255,255,255,0.2); }
        .stat-card--light .icon { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }
        .stat-card .icon svg { width: 22px; height: 22px; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 2rem; font-size: 0.8rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    <div class="app">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="../public/official-logo.png" alt="RCMP UniFa">
            </div>
            <nav class="sidebar-nav">
                <a href="application.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application form</a>
                <a href="appInformation.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Application info</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>View history</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
        <header class="dashboard-header">
            <h1 class="dashboard-title">Student Dashboard</h1>
            <div class="top-actions">
                <button type="button" class="btn-icon" aria-label="Notifications">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-6-6 6 6 0 00-6 6v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="notif-dot"></span>
                </button>
                <div class="search-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" placeholder="Search" aria-label="Search">
                </div>
            </div>
            <div class="user-menu">
                <a href="../index.php">Home</a>
            </div>
        </header>

        <main class="main-grid">
            <section class="hero-card">
                <div>
                    <h2>Apply for financial aid</h2>
                    <p>Submit a new fund request or check eligibility. Track your applications and disbursements in one place.</p>
                    <a href="apply.php" class="btn-primary">New application</a>
                </div>
            </section>

            <div class="stat-cards">
                <div class="stat-card stat-card--dark">
                    <div>
                        <div class="num"><?php echo (int) $applicationsSubmitted; ?></div>
                        <div class="label">Applications submitted</div>
                    </div>
                    <div class="icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="stat-card stat-card--purple">
                    <div>
                        <div class="num"><?php echo (int) $pendingReview; ?></div>
                        <div class="label">Pending review</div>
                    </div>
                    <div class="icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                </div>
                <div class="stat-card stat-card--light">
                    <div>
                        <div class="num"><?php echo (int) $approvedOrDisbursed; ?></div>
                        <div class="label">Approved / Disbursed</div>
                    </div>
                    <div class="icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </main>
            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
