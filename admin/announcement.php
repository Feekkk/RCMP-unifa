<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$adminId   = $_SESSION['admin_id'] ?? null;
$adminName = $_SESSION['user_name'] ?? 'Staff';

// ── Ensure table exists ──────────────────────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS announcements (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(255) NOT NULL,
            body        TEXT,
            is_active   TINYINT(1) NOT NULL DEFAULT 1,
            pinned      TINYINT(1) NOT NULL DEFAULT 0,
            created_at  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at  DATETIME   NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {}

$success = '';
$error   = '';

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title      = trim($_POST['title'] ?? '');
        $body       = trim($_POST['body']  ?? '');
        $pinned     = isset($_POST['pinned']) ? 1 : 0;
        $expires_at = trim($_POST['expires_at'] ?? '');
        if ($title === '') {
            $error = 'Title is required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO announcements (title, body, is_active, pinned, expires_at)
                    VALUES (?, ?, 1, ?, ?)
                ");
                $stmt->execute([$title, $body ?: null, $pinned, $expires_at ?: null]);
                $success = 'Announcement published successfully.';
            } catch (PDOException $e) {
                $error = 'Could not publish announcement. Please try again.';
            }
        }
    }

    elseif ($action === 'toggle_pin') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pdo->prepare("UPDATE announcements SET pinned = 1 - pinned WHERE id = ?")->execute([$id]);
            $success = 'Pin status updated.';
        } catch (PDOException $e) { $error = 'Could not update.'; }
    }

    elseif ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pdo->prepare("UPDATE announcements SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
            $success = 'Visibility updated.';
        } catch (PDOException $e) { $error = 'Could not update.'; }
    }

    elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
            $success = 'Announcement deleted.';
        } catch (PDOException $e) { $error = 'Could not delete.'; }
    }

    // PRG pattern
    $qs = $success ? '?msg=ok' : '?msg=err';
    header('Location: announcement.php' . $qs);
    exit;
}

// Flash messages from redirect
$flashMsg = $_GET['msg'] ?? '';

// ── Fetch all announcements ──────────────────────────────────────────────────
$announcements = [];
try {
    $stmt = $pdo->query("
        SELECT id, title, body, is_active, pinned, created_at, expires_at
        FROM   announcements
        ORDER  BY pinned DESC, created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Count active announcements without using arrow functions
$activeCount = 0;
foreach ($announcements as $a) {
    if ((int)($a['is_active'] ?? 0) === 1) {
        $activeCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements — RCMP UniFa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: #f9fafb; color: #111827; }
        .page-bg { position: fixed; inset: 0; background: url("../public/bgm.png") center/cover no-repeat; filter: blur(12px); transform: scale(1.05); opacity: 0.15; z-index: -2; }
        .page-overlay { position: fixed; inset: 0; background: linear-gradient(180deg, rgba(249,250,251,0.7) 0%, #f9fafb 50%); z-index: -1; }
        .app { position: relative; z-index: 1; display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar { width: 260px; flex-shrink: 0; background: #fff; border-right: 1px solid #e5e7eb; padding: 1.5rem 0; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 0 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem; }
        .sidebar-brand img { height: 48px; width: auto; object-fit: contain; }
        .sidebar-nav { flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; color: #4b5563; text-decoration: none; font-size: 0.9rem; transition: background 0.15s, color 0.15s; }
        .sidebar-nav a:hover { background: #f3f4f6; color: #111827; }
        .sidebar-nav a.active { background: rgba(79,70,229,0.08); color: #4f46e5; font-weight: 500; }
        .sidebar-nav a svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem 1.25rem 0; border-top: 1px solid #e5e7eb; }
        .sidebar-logout { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; color: #dc2626; text-decoration: none; font-size: 0.9rem; transition: background 0.15s, color 0.15s; }
        .sidebar-logout:hover { background: #fef2f2; color: #b91c1c; }
        .sidebar-logout svg { width: 20px; height: 20px; flex-shrink: 0; }
        .nav-badge { margin-left: auto; font-size: 0.7rem; font-weight: 700; background: #6366f1; color: #fff; border-radius: 999px; padding: 0.1rem 0.45rem; }

        /* ── Main ── */
        .main-content { flex: 1; min-width: 0; padding: 1.5rem 2rem 3rem; overflow-x: hidden; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        .dashboard-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; }
        .user-menu { display: flex; align-items: center; gap: 0.75rem; }
        .user-menu a { color: #4b5563; text-decoration: none; font-size: 0.875rem; transition: color 0.15s; }
        .user-menu a:hover { color: #111827; }

        /* ── Two-column layout ── */
        .page-grid { display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start; }
        @media (max-width: 1024px) { .page-grid { grid-template-columns: 1fr; } }

        /* ── Create form card ── */
        .form-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 1.75rem 1.75rem 2rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 1.5rem;
        }
        .form-card h2 { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 600; color: #111827; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-card h2 svg { width: 20px; height: 20px; color: #6366f1; }
        .field { margin-bottom: 1rem; }
        .field label { display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .field input[type="text"],
        .field input[type="datetime-local"],
        .field textarea { width: 100%; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.65rem 0.85rem; font-size: 0.9rem; font-family: inherit; outline: none; background: #fff; transition: border-color 0.15s, box-shadow 0.15s; }
        .field input:focus, .field textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .field textarea { min-height: 100px; resize: vertical; line-height: 1.55; }
        .field-hint { font-size: 0.75rem; color: #9ca3af; margin-top: 0.3rem; }
        .checkbox-row { display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 0.9rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; user-select: none; transition: border-color 0.15s, background 0.15s; }
        .checkbox-row:hover { border-color: #a5b4fc; background: rgba(99,102,241,0.02); }
        .checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: #6366f1; cursor: pointer; }
        .checkbox-row span { font-size: 0.875rem; color: #374151; font-weight: 500; }
        .btn-publish { width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.8rem; border-radius: 999px; background: #111827; color: #f9fafb; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; margin-top: 1.25rem; transition: transform 0.1s, box-shadow 0.15s; }
        .btn-publish:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(17,24,39,0.3); }
        .btn-publish svg { width: 18px; height: 18px; }

        /* ── Flash / Alert ── */
        .alert { padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert svg { width: 16px; height: 16px; flex-shrink: 0; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        /* ── List panel ── */
        .list-panel {}
        .list-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        .list-header h2 { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 0.5rem; }
        .list-header h2 svg { width: 20px; height: 20px; color: #6b7280; }
        .count-badge { font-size: 0.7rem; font-weight: 700; background: #6366f1; color: #fff; border-radius: 999px; padding: 0.15rem 0.5rem; }

        /* ── Announcement row card ── */
        .ann-row {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.1rem 1.25rem;
            margin-bottom: 0.75rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: box-shadow 0.2s, border-color 0.2s;
            border-left: 4px solid #6366f1;
            position: relative;
        }
        .ann-row:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.07); border-color: #d1d5db; }
        .ann-row.inactive { opacity: 0.55; border-left-color: #d1d5db; }
        .ann-row.pinned-row { border-left-color: #f59e0b; }
        .ann-row-icon {
            width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(99,102,241,0.1); color: #6366f1;
        }
        .ann-row.pinned-row .ann-row-icon { background: rgba(245,158,11,0.12); color: #d97706; }
        .ann-row-icon svg { width: 18px; height: 18px; }
        .ann-row-body { flex: 1; min-width: 0; }
        .ann-row-title { font-weight: 600; font-size: 0.95rem; color: #111827; display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; margin-bottom: 0.25rem; }
        .tag { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; border-radius: 999px; padding: 0.15rem 0.45rem; }
        .tag-pin    { background: #fef3c7; color: #92400e; }
        .tag-hidden { background: #f3f4f6; color: #6b7280; }
        .ann-row-body-text { font-size: 0.85rem; color: #4b5563; line-height: 1.55; margin-bottom: 0.4rem; white-space: pre-line; }
        .ann-row-meta { font-size: 0.75rem; color: #9ca3af; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .ann-row-meta svg { width: 12px; height: 12px; }
        .ann-row-meta span { display: flex; align-items: center; gap: 0.25rem; }
        .ann-row-actions { display: flex; flex-direction: column; gap: 0.4rem; flex-shrink: 0; }
        .act-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem;
            padding: 0.4rem 0.7rem; border-radius: 8px;
            font-size: 0.75rem; font-weight: 600;
            border: 1px solid #e5e7eb; cursor: pointer;
            background: #fff; color: #374151;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            min-width: 80px;
        }
        .act-btn svg { width: 13px; height: 13px; }
        .act-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        .act-btn--pin:hover { background: #fefce8; color: #a16207; border-color: #fde68a; }
        .act-btn--hide:hover { background: #f9fafb; color: #374151; border-color: #d1d5db; }
        .act-btn--show:hover { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .act-btn--delete { color: #b91c1c; }
        .act-btn--delete:hover { background: #fef2f2; border-color: #fecaca; }

        /* ── Empty state ── */
        .ann-empty { text-align: center; padding: 4rem 2rem; border-radius: 18px; background: #fff; border: 1.5px dashed #e5e7eb; color: #9ca3af; }
        .ann-empty svg { width: 48px; height: 48px; margin: 0 auto 1rem; display: block; opacity: 0.3; }
        .ann-empty p { font-size: 0.95rem; color: #6b7280; font-weight: 500; }

        /* ── Footer ── */
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 2rem; font-size: 0.8rem; color: #9ca3af; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
            .form-card { position: static; }
            .ann-row { flex-wrap: wrap; }
            .ann-row-actions { flex-direction: row; flex-wrap: wrap; }
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
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </a>
                <a href="application.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Application
                </a>
                <a href="receipt.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Receipt
                </a>
                <a href="manageUser.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Manage user
                </a>
                <a href="history.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    History
                </a>
                <a href="announcement.php" class="active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Announcements
                    <?php if ($activeCount > 0): ?>
                        <span class="nav-badge"><?php echo $activeCount; ?></span>
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
            <header class="dashboard-header">
                <h1 class="dashboard-title">Announcements</h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <span style="font-size:0.875rem;color:#6b7280"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
            </header>

            <?php if ($flashMsg === 'ok'): ?>
                <div class="alert alert-success">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Action completed successfully.
                </div>
            <?php elseif ($flashMsg === 'err'): ?>
                <div class="alert alert-error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Something went wrong. Please try again.
                </div>
            <?php endif; ?>

            <div class="page-grid">

                <!-- ── Create form ── -->
                <div class="form-card">
                    <h2>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Announcement
                    </h2>

                    <form method="post" action="">
                        <input type="hidden" name="action" value="create">

                        <div class="field">
                            <label for="title">Title <span style="color:#ef4444">*</span></label>
                            <input type="text" id="title" name="title" placeholder="e.g. System maintenance on Friday" maxlength="255" required>
                        </div>

                        <div class="field">
                            <label for="body">Body <span style="color:#9ca3af;font-weight:400;text-transform:none">(optional)</span></label>
                            <textarea id="body" name="body" placeholder="Additional details…"></textarea>
                        </div>

                        <div class="field">
                            <label for="expires_at">Expiry date & time <span style="color:#9ca3af;font-weight:400;text-transform:none">(optional)</span></label>
                            <input type="datetime-local" id="expires_at" name="expires_at">
                            <p class="field-hint">Leave blank to keep active indefinitely.</p>
                        </div>

                        <div class="field">
                            <label class="checkbox-row" for="pinned">
                                <input type="checkbox" id="pinned" name="pinned" value="1">
                                <span>📌 Pin the announcement</span>
                            </label>
                        </div>

                        <button type="submit" class="btn-publish">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            Publish Announcement
                        </button>
                    </form>
                </div>

                <!-- ── List ── -->
                <div class="list-panel">
                    <div class="list-header">
                        <h2>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            All Announcements
                        </h2>
                        <?php if (!empty($announcements)): ?>
                            <span class="count-badge"><?php echo count($announcements); ?> total</span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($announcements)): ?>
                        <div class="ann-empty">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <p>No announcements yet. Create your first one!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                            <?php
                                $isPinned   = (int)$ann['pinned']    === 1;
                                $isActive   = (int)$ann['is_active'] === 1;
                                $rowClass   = '';
                                if (!$isActive)  $rowClass .= ' inactive';
                                if ($isPinned)   $rowClass .= ' pinned-row';
                            ?>
                            <div class="ann-row<?php echo $rowClass; ?>">
                                <div class="ann-row-icon">
                                    <?php if ($isPinned): ?>
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                    <?php else: ?>
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                    <?php endif; ?>
                                </div>

                                <div class="ann-row-body">
                                    <div class="ann-row-title">
                                        <?php echo htmlspecialchars($ann['title']); ?>
                                        <?php if ($isPinned): ?><span class="tag tag-pin">📌 Pinned</span><?php endif; ?>
                                        <?php if (!$isActive): ?><span class="tag tag-hidden">Hidden</span><?php endif; ?>
                                    </div>
                                    <?php if (!empty($ann['body'])): ?>
                                        <?php
                                            $bodyText = (string)($ann['body'] ?? '');
                                            $preview = strlen($bodyText) > 140 ? substr($bodyText, 0, 140) . '…' : $bodyText;
                                        ?>
                                        <div class="ann-row-body-text"><?php echo htmlspecialchars($preview); ?></div>
                                    <?php endif; ?>
                                    <div class="ann-row-meta">
                                        <span>
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <?php echo date('d M Y, g:i A', strtotime($ann['created_at'])); ?>
                                        </span>
                                        <?php if (!empty($ann['expires_at'])): ?>
                                            <span>
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Expires <?php echo date('d M Y, g:i A', strtotime($ann['expires_at'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="ann-row-actions">
                                    <!-- Pin / Unpin -->
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_pin">
                                        <input type="hidden" name="id" value="<?php echo (int)$ann['id']; ?>">
                                        <button type="submit" class="act-btn act-btn--pin" title="<?php echo $isPinned ? 'Unpin' : 'Pin to top'; ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                            <?php echo $isPinned ? 'Unpin' : 'Pin'; ?>
                                        </button>
                                    </form>
                                    <!-- Show / Hide -->
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?php echo (int)$ann['id']; ?>">
                                        <button type="submit" class="act-btn <?php echo $isActive ? 'act-btn--hide' : 'act-btn--show'; ?>">
                                            <?php if ($isActive): ?>
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                Hide
                                            <?php else: ?>
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Show
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <!-- Delete -->
                                    <form method="post" onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$ann['id']; ?>">
                                        <button type="submit" class="act-btn act-btn--delete">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div><!-- /.page-grid -->

            <footer class="page-footer">&copy; University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
