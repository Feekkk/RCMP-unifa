<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Staff';
$adminStaffId = (int) ($_SESSION['admin_id'] ?? 0);
$msg = $_GET['m'] ?? '';
$applications = [];
$dbError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    $appId = (int) $_POST['app_id'];
    $up = $_FILES['receipt'] ?? [];
    $fname = $up['name'] ?? '';
    $ferr = $up['error'] ?? UPLOAD_ERR_NO_FILE;
    $tmp = $up['tmp_name'] ?? '';
    if ($appId > 0 && $fname !== '' && $ferr === UPLOAD_ERR_OK && $tmp !== '') {
        try {
            $stmt = $pdo->prepare('SELECT status_id FROM applications WHERE id = ?');
            $stmt->execute([$appId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (int) $row['status_id'] === 3) {
                $ext = pathinfo($fname, PATHINFO_EXTENSION) ?: 'bin';
                $ext = in_array(strtolower($ext), ['pdf', 'jpg', 'jpeg', 'png']) ? strtolower($ext) : 'bin';
                $uploadDir = __DIR__ . '/../public/documents/' . date('Ym');
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'receipt_' . $appId . '_' . uniqid('', true) . '.' . $ext;
                $dest = $uploadDir . '/' . $filename;
                if (move_uploaded_file($tmp, $dest)) {
                    $relPath = 'documents/' . date('Ym') . '/' . $filename;
                    $pdo->beginTransaction();
                    $pdo->prepare('UPDATE applications SET status_id = 5, receipt_path = ?, receipt_uploaded_at = NOW(), receipt_uploaded_by = ? WHERE id = ?')
                        ->execute([$relPath, $adminStaffId ?: null, $appId]);
                    $pdo->prepare('INSERT INTO application_history (application_id, from_status_id, to_status_id, staff_id, action, notes) VALUES (?, 3, 5, ?, ?, NULL)')
                        ->execute([$appId, $adminStaffId ?: null, 'disburse']);
                    $pdo->commit();
                    $msg = 'success';
                } else {
                    $msg = 'upload_fail';
                }
            } else {
                $msg = 'invalid';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = 'error';
        }
        header('Location: receipt.php?m=' . $msg);
        exit;
    } else {
        $msg = 'nofile';
        header('Location: receipt.php?m=' . $msg);
        exit;
    }
}

try {
    $stmt = $pdo->query("
        SELECT a.id, a.user_id, a.category, a.subtype, a.amount_applied, a.created_at,
               COALESCE(u.full_name, 'Unknown') AS full_name
        FROM applications a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.status_id = 3
        ORDER BY a.created_at ASC
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Receipt — RCMP UniFa Admin</title>
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
        .user-menu a { color: #4b5563; text-decoration: none; font-size: 0.875rem; }
        .user-menu a:hover { color: #111827; }
        .page-desc { font-size: 0.9rem; color: #6b7280; margin-bottom: 1.5rem; }
        .table-wrap { border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        thead { background: #ecfdf5; }
        th, td { padding: 0.6rem 0.9rem; text-align: left; }
        th { font-weight: 600; color: #166534; border-bottom: 1px solid #bbf7d0; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #ecfdf5; }
        .empty-row { padding: 2rem; text-align: center; color: #6b7280; }
        .receipt-form { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .receipt-form input[type="file"] { font-size: 0.8rem; }
        .btn-upload { padding: 0.4rem 0.9rem; border-radius: 8px; background: #22c55e; color: #fff; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; }
        .btn-upload:hover { background: #16a34a; }
        .link-view { color: #4f46e5; text-decoration: none; font-weight: 500; }
        .link-view:hover { text-decoration: underline; }
        .flash { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.9rem; }
        .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .db-error { padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; margin-bottom: 1rem; color: #b91c1c; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1rem; font-size: 0.8rem; color: #9ca3af; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-footer { border-top: none; padding: 0; }
            .table-wrap { overflow-x: auto; }
            .receipt-form { flex-direction: column; align-items: flex-start; }
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
                <a href="application.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application</a>
                <a href="receipt.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Receipt</a>
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
                <h1 class="page-title">Upload receipt</h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <span style="font-size:0.875rem;color:#6b7280"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
            </header>

            <p class="page-desc">Applications approved by committee. Upload receipt to mark as disbursed. Student will be able to view the receipt.</p>

            <?php if ($msg === 'success'): ?><div class="flash flash-success">Receipt uploaded. Application marked as disbursed.</div><?php endif; ?>
            <?php if ($msg === 'error'): ?><div class="flash flash-error">Could not save. Please try again.</div><?php endif; ?>
            <?php if ($msg === 'nofile'): ?><div class="flash flash-error">Please select a file to upload.</div><?php endif; ?>
            <?php if ($msg === 'upload_fail'): ?><div class="flash flash-error">File upload failed.</div><?php endif; ?>
            <?php if ($dbError): ?><div class="db-error">Database error: <?php echo htmlspecialchars($dbError); ?></div><?php endif; ?>

            <div class="table-wrap">
                <?php if (empty($applications)): ?>
                    <div class="empty-row">No approved applications awaiting receipt.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Category</th>
                                <th>Subtype</th>
                                <th>Amount (RM)</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $row): ?>
                                <tr>
                                    <td><a href="viewApplication.php?id=<?php echo (int)$row['id']; ?>" class="link-view">#<?php echo (int)$row['id']; ?></a></td>
                                    <td><?php echo htmlspecialchars($row['full_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['category'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['subtype'] ?? ''))); ?></td>
                                    <td><?php echo $row['amount_applied'] !== null ? number_format((float)$row['amount_applied'], 2) : '—'; ?></td>
                                    <td><?php echo $row['created_at'] ? date('d M Y', strtotime($row['created_at'])) : '—'; ?></td>
                                    <td>
                                        <form method="post" enctype="multipart/form-data" class="receipt-form">
                                            <input type="hidden" name="app_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <button type="submit" class="btn-upload">Upload receipt</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
