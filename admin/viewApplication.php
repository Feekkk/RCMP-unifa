<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Staff';
$adminStaffId = (int) ($_SESSION['admin_id'] ?? 0);
$appId = (int) ($_GET['id'] ?? 0);
$recommendMsg = $_GET['m'] ?? '';
$app = null;
$user = null;
$statusName = '';
$documents = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recommend' && $appId > 0) {
    $comment = trim((string) ($_POST['comment'] ?? ''));
    try {
        $stmt = $pdo->prepare('SELECT status_id FROM applications WHERE id = ?');
        $stmt->execute([$appId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int) $row['status_id'] === 1) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE applications SET status_id = 2 WHERE id = ?')->execute([$appId]);
            $pdo->prepare('INSERT INTO application_history (application_id, from_status_id, to_status_id, staff_id, action, notes) VALUES (?, 1, 2, ?, ?, ?)')
                ->execute([$appId, $adminStaffId ?: null, 'recommend', $comment ?: null]);
            $pdo->commit();
            $recommendMsg = 'success';
        } else {
            $recommendMsg = 'invalid';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $recommendMsg = 'error';
    }
    header('Location: viewApplication.php?id=' . $appId . '&m=' . $recommendMsg);
    exit;
}

if ($appId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name, u.email, u.course, u.phone, u.address,
                   u.bank_name AS user_bank_name, u.bank_account AS user_bank_account,
                   s.name AS status_name
            FROM applications a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN status s ON s.id = a.status_id
            WHERE a.id = ?
        ");
        $stmt->execute([$appId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $app = $row;
            $user = [
                'full_name' => $row['full_name'] ?? '',
                'email' => $row['email'] ?? '',
                'course' => $row['course'] ?? '',
                'phone' => $row['phone'] ?? '',
                'address' => $row['address'] ?? '',
            ];
            $statusName = $row['status_name'] ?? 'Unknown';
        }

        $stmt = $pdo->prepare("SELECT id, file_path, document_type, created_at FROM document WHERE application_id = ? ORDER BY id");
        $stmt->execute([$appId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $app = null;
    }
}

function fmt($v) { return $v !== null && $v !== '' ? htmlspecialchars((string)$v) : '-'; }
function fmtDate($v) { return $v ? date('d M Y', strtotime($v)) : '-'; }
function fmtDateTime($v) { return $v ? date('d M Y, H:i', strtotime($v)) : '-'; }
function docLabel($t) { return ucwords(str_replace('_', ' ', $t)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app ? 'Application #' . $appId : 'Application'; ?> — RCMP UniFa Admin</title>
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
        .user-menu { display: flex; align-items: center; gap: 0.75rem; }
        .user-menu a { color: #4b5563; text-decoration: none; font-size: 0.875rem; transition: color 0.15s; }
        .user-menu a:hover { color: #111827; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 10px; background: #e5e7eb; color: #374151; font-size: 0.9rem; font-weight: 500; text-decoration: none; margin-bottom: 1.5rem; }
        .btn-back:hover { background: #d1d5db; }
        .card { border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .card h3 { font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem 1.5rem; }
        .info-item { display: flex; flex-direction: column; gap: 0.2rem; }
        .info-label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; }
        .info-value { font-size: 0.9rem; color: #111827; }
        .doc-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .doc-item { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.9rem; background: #f9fafb; border-radius: 10px; border: 1px solid #e5e7eb; }
        .doc-item a { color: #4f46e5; text-decoration: none; font-weight: 500; }
        .doc-item a:hover { text-decoration: underline; }
        .doc-type { font-size: 0.75rem; color: #6b7280; }
        .not-found { padding: 2rem; text-align: center; color: #6b7280; background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 500; }
        .badge--pending { background: #fffbeb; color: #92400e; }
        .badge--under_review { background: #eff6ff; color: #1d4ed8; }
        .badge--approved { background: #ecfdf5; color: #166534; }
        .badge--rejected { background: #fef2f2; color: #b91c1c; }
        .badge--disbursed { background: #eef2ff; color: #3730a3; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1rem; font-size: 0.8rem; color: #9ca3af; }
        .action-card { border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .action-card textarea { width: 100%; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.65rem 0.85rem; font-size: 0.9rem; min-height: 80px; resize: vertical; }
        .action-card .btn-recommend { padding: 0.6rem 1.25rem; border-radius: 10px; background: #4f46e5; color: #fff; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; margin-top: 0.75rem; }
        .action-card .btn-recommend:hover { background: #4338ca; }
        .flash { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.9rem; }
        .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
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
                <a href="application.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application</a>
                <a href="receipt.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Receipt</a>
                <a href="manageUser.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage user</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
                <a href="announcement.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>Announcements</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="page-header">
                <h1 class="page-title"><?php echo $app ? 'Application #' . $appId : 'View Application'; ?></h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <span style="font-size:0.875rem;color:#6b7280"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
            </header>

            <a href="application.php" class="btn-back">← Back to Applications</a>

            <?php if ($recommendMsg === 'success'): ?><div class="flash flash-success">Application recommended and moved to Under Review.</div><?php endif; ?>
            <?php if ($recommendMsg === 'error'): ?><div class="flash flash-error">Could not update. Please try again.</div><?php endif; ?>

            <?php if (!$app): ?>
                <div class="not-found">Application not found or invalid ID.</div>
            <?php else: ?>
                <div class="card">
                    <h3>Status</h3>
                    <span class="badge badge--<?php echo htmlspecialchars(str_replace(' ', '_', $statusName)); ?>"><?php echo fmt($statusName); ?></span>
                </div>

                <div class="card">
                    <h3>Student Information</h3>
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">Full Name</span><span class="info-value"><?php echo fmt($user['full_name']); ?></span></div>
                        <div class="info-item"><span class="info-label">Email</span><span class="info-value"><?php echo fmt($user['email']); ?></span></div>
                        <div class="info-item"><span class="info-label">Course</span><span class="info-value"><?php echo fmt($user['course']); ?></span></div>
                        <div class="info-item"><span class="info-label">Phone</span><span class="info-value"><?php echo fmt($user['phone']); ?></span></div>
                        <div class="info-item" style="grid-column: 1 / -1;"><span class="info-label">Address</span><span class="info-value"><?php echo fmt($user['address']); ?></span></div>
                    </div>
                </div>

                <div class="card">
                    <h3>Application Details</h3>
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">Category</span><span class="info-value"><?php echo fmt(ucwords(str_replace('_', ' ', $app['category'] ?? ''))); ?></span></div>
                        <div class="info-item"><span class="info-label">Subtype</span><span class="info-value"><?php echo fmt(ucwords(str_replace('_', ' ', $app['subtype'] ?? ''))); ?></span></div>
                        <div class="info-item"><span class="info-label">Amount Applied (RM)</span><span class="info-value"><?php echo $app['amount_applied'] !== null ? number_format((float)$app['amount_applied'], 2) : '-'; ?></span></div>
                        <div class="info-item"><span class="info-label">Bank Name</span><span class="info-value"><?php echo fmt($app['bank_name']); ?></span></div>
                        <div class="info-item"><span class="info-label">Bank Account</span><span class="info-value"><?php echo fmt($app['bank_account']); ?></span></div>
                        <div class="info-item"><span class="info-label">Submitted At</span><span class="info-value"><?php echo fmtDateTime($app['created_at']); ?></span></div>
                    </div>
                </div>

                <?php if ($app['clinic_name'] || $app['reason_visit'] || $app['visit_datetime'] || $app['checkin_date'] || $app['checkout_date'] || $app['case_description']): ?>
                <div class="card">
                    <h3>Category-specific Details</h3>
                    <div class="info-grid">
                        <?php if ($app['clinic_name']): ?><div class="info-item"><span class="info-label">Clinic Name</span><span class="info-value"><?php echo fmt($app['clinic_name']); ?></span></div><?php endif; ?>
                        <?php if ($app['reason_visit']): ?><div class="info-item"><span class="info-label">Reason for Visit</span><span class="info-value"><?php echo fmt($app['reason_visit']); ?></span></div><?php endif; ?>
                        <?php if ($app['visit_datetime']): ?><div class="info-item"><span class="info-label">Visit Date/Time</span><span class="info-value"><?php echo fmtDateTime($app['visit_datetime']); ?></span></div><?php endif; ?>
                        <?php if ($app['checkin_date']): ?><div class="info-item"><span class="info-label">Check-in Date</span><span class="info-value"><?php echo fmtDate($app['checkin_date']); ?></span></div><?php endif; ?>
                        <?php if ($app['checkout_date']): ?><div class="info-item"><span class="info-label">Check-out Date</span><span class="info-value"><?php echo fmtDate($app['checkout_date']); ?></span></div><?php endif; ?>
                        <?php if ($app['case_description']): ?><div class="info-item" style="grid-column: 1 / -1;"><span class="info-label">Case Description</span><span class="info-value"><?php echo nl2br(fmt($app['case_description'])); ?></span></div><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <h3>Uploaded Documents</h3>
                    <?php if (empty($documents)): ?>
                        <p class="info-value">No documents uploaded.</p>
                    <?php else: ?>
                        <div class="doc-list">
                            <?php foreach ($documents as $doc): ?>
                                <div class="doc-item">
                                    <div>
                                        <span class="doc-type"><?php echo docLabel($doc['document_type']); ?></span>
                                        <br>
                                        <a href="../public/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></a>
                                    </div>
                                    <span class="doc-type"><?php echo fmtDateTime($doc['created_at']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ((int) ($app['status_id'] ?? 0) === 1): ?>
                <div class="card action-card">
                    <h3>Admin Action</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="recommend">
                        <div class="field" style="margin-bottom:0.75rem">
                            <label for="comment" class="info-label" style="display:block;margin-bottom:0.35rem">Comment</label>
                            <textarea name="comment" id="comment" placeholder="Optional comment..."></textarea>
                        </div>
                        <button type="submit" class="btn-recommend">Recommended</button>
                    </form>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
