<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Staff';
$search = trim((string) ($_GET['search'] ?? ''));
$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$history = [];
$total = 0;
$dbError = '';

try {
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = ' AND (u.full_name LIKE ? OR h.application_id = ?)';
        $params = ['%' . $search . '%', ctype_digit($search) ? (int) $search : 0];
    }
    $countSql = "SELECT COUNT(*) FROM application_history h
        LEFT JOIN applications a ON a.id = h.application_id
        LEFT JOIN users u ON u.id = a.user_id
        WHERE 1=1" . $where;
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $sql = "SELECT h.id, h.application_id, h.from_status_id, h.to_status_id, h.action, h.notes, h.created_at,
                   u.full_name AS student_name,
                   s1.name AS from_status_name,
                   s2.name AS to_status_name,
                   st.full_name AS staff_name
            FROM application_history h
            LEFT JOIN applications a ON a.id = h.application_id
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN status s1 ON s1.id = h.from_status_id
            LEFT JOIN status s2 ON s2.id = h.to_status_id
            LEFT JOIN staff st ON st.id = h.staff_id
            WHERE 1=1" . $where . "
            ORDER BY h.created_at DESC
            LIMIT " . $perPage . " OFFSET " . $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$maxPage = max(1, (int) ceil($total / $perPage));
function actionLabel($a) { return ucfirst((string) ($a ?: '—')); }
function statusLabel($n) { return $n ? ucwords(str_replace('_', ' ', $n)) : '—'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application History — RCMP UniFa Admin</title>
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
        .search-bar { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .search-bar input { flex: 1; min-width: 200px; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.6rem 0.9rem; font-size: 0.9rem; }
        .search-bar button { padding: 0.6rem 1.25rem; border-radius: 10px; background: #0f1419; color: #fff; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; }
        .table-wrap { border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        thead { background: #f9fafb; }
        th, td { padding: 0.6rem 0.9rem; text-align: left; }
        th { font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #eef2ff; }
        .empty-row { padding: 1.5rem; text-align: center; color: #6b7280; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #374151; background: #fff; border: 1px solid #e5e7eb; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active { background: #4f46e5; color: #fff; border-color: #4f46e5; pointer-events: none; }
        .link-app { color: #4f46e5; text-decoration: none; font-weight: 500; }
        .link-app:hover { text-decoration: underline; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1rem; font-size: 0.8rem; color: #9ca3af; }
        .db-error { padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; margin-bottom: 1.5rem; color: #b91c1c; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-footer { border-top: none; padding: 0; }
            .table-wrap { overflow-x: auto; }
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
                <a href="receipt.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Receipt</a>
                <a href="manageUser.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage user</a>
                <a href="history.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
                <a href="announcement.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>Announcements</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="page-header">
                <h1 class="page-title">Application History</h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <span style="font-size:0.875rem;color:#6b7280"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
            </header>

            <?php if ($dbError): ?><div class="db-error">Database error: <?php echo htmlspecialchars($dbError); ?></div><?php endif; ?>

            <form method="get" class="search-bar">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by student name or application ID...">
                <button type="submit">Search</button>
            </form>

            <div class="table-wrap">
                <?php if (empty($history)): ?>
                    <div class="empty-row">No application history found<?php echo $search ? '.' : ' yet.'; ?></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>App #</th>
                                <th>Student</th>
                                <th>Action</th>
                                <th>From → To</th>
                                <th>By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))); ?></td>
                                    <td>
                                        <a href="viewApplication.php?id=<?php echo (int)$row['application_id']; ?>" class="link-app">#<?php echo (int)$row['application_id']; ?></a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['student_name'] ?? '—'); ?></td>
                                    <td><?php echo actionLabel($row['action']); ?></td>
                                    <td><?php echo statusLabel($row['from_status_name']); ?> → <?php echo statusLabel($row['to_status_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_name'] ?? 'Student'); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($row['notes'] ?? '', 0, 60)); ?><?php echo mb_strlen($row['notes'] ?? '') > 60 ? '…' : ''; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($maxPage > 1): ?>
                        <div class="pagination" style="padding:0.75rem 0.9rem">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Prev</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $maxPage; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($page < $maxPage): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:center;font-size:0.8rem;color:#6b7280;padding-bottom:0.5rem"><?php echo $total; ?> records · Page <?php echo $page; ?>/<?php echo $maxPage; ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
