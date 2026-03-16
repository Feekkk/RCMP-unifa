<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Staff';
$search = trim((string) ($_GET['search'] ?? ''));
$perPage = 10;
$selectedStatus = (int) ($_GET['status'] ?? 0); // 0 = recent (all statuses)
$page = max(1, (int) ($_GET['page'] ?? 1));

$applications = [];
$total = 0;
$totalsByStatus = [];
$dbError = '';

$statusRows = [
    ['id' => 1, 'name' => 'pending'],
    ['id' => 2, 'name' => 'under_review'],
    ['id' => 6, 'name' => 'committee_approved'],
    ['id' => 3, 'name' => 'approved'],
    ['id' => 4, 'name' => 'rejected'],
    ['id' => 5, 'name' => 'disbursed'],
];

try {
    $counts = $pdo->query('SELECT status_id, COUNT(*) AS cnt FROM applications GROUP BY status_id')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $r) $totalsByStatus[(int) $r['status_id']] = (int) $r['cnt'];

    $where = [];
    $params = [];
    if ($selectedStatus > 0) {
        $where[] = 'a.status_id = ?';
        $params[] = $selectedStatus;
    }
    if ($search !== '') {
        $isId = ctype_digit($search);
        if ($isId) {
            $where[] = '(a.user_id = ? OR u.full_name LIKE ?)';
            $params[] = (int) $search;
            $params[] = '%' . $search . '%';
        } else {
            $where[] = 'u.full_name LIKE ?';
            $params[] = '%' . $search . '%';
        }
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countSql = "SELECT COUNT(*)
                 FROM applications a
                 LEFT JOIN users u ON u.id = a.user_id
                 $whereSql";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $listSql = "SELECT a.id, a.user_id, a.category, a.subtype, a.amount_applied, a.status_id, a.created_at,
                       COALESCE(u.full_name, 'Unknown') AS full_name,
                       COALESCE(u.course, '') AS course
                FROM applications a
                LEFT JOIN users u ON u.id = a.user_id
                $whereSql
                ORDER BY a.created_at DESC
                LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($listSql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $applications = [];
    $total = 0;
    $totalsByStatus = [];
    $dbError = $e->getMessage();
}

function status_label($name) {
    return ucwords(str_replace('_', ' ', (string) $name));
}

function build_list_url(int $status, int $page, string $search): string {
    $p = ['page' => $page];
    if ($status > 0) $p['status'] = $status;
    if ($search !== '') $p['search'] = $search;
    return 'application.php?' . http_build_query($p);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications — RCMP UniFa Admin</title>
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
        .user-menu { display: flex; align-items: center; gap: 0.75rem; }
        .user-menu a { color: #4b5563; text-decoration: none; font-size: 0.875rem; transition: color 0.15s; }
        .user-menu a:hover { color: #111827; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .status-card { border-radius: 18px; padding: 1.1rem 1.25rem; background: #fff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        a.status-card { text-decoration: none; color: inherit; transition: transform 0.08s, box-shadow 0.15s; }
        a.status-card:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        a.status-card.active { outline: 2px solid rgba(79, 70, 229, 0.35); }
        .status-card-title { font-size: 0.9rem; color: #4b5563; margin-bottom: 0.25rem; }
        .status-card-count { font-size: 1.6rem; font-weight: 700; color: #111827; }
        .status-pill { font-size: 0.75rem; padding: 0.15rem 0.6rem; border-radius: 999px; border: 1px solid #e5e7eb; color: #6b7280; background: #f9fafb; }
        .status-card--pending { border-color: #fbbf24; background: #fffbeb; }
        .status-card--under_review { border-color: #3b82f6; background: #eff6ff; }
        .status-card--committee_approved { border-color: #a855f7; background: #faf5ff; }
        .status-card--approved { border-color: #22c55e; background: #ecfdf5; }
        .status-card--rejected { border-color: #f97373; background: #fef2f2; }
        .status-card--disbursed { border-color: #6366f1; background: #eef2ff; }
        .status-section { margin-bottom: 2rem; }
        .status-section h2 { font-size: 1.1rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem; }
        .status-section-sub { font-size: 0.85rem; color: #6b7280; margin-bottom: 0.75rem; }
        .table-wrap { border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        thead { background: #f9fafb; }
        th, td { padding: 0.6rem 0.9rem; text-align: left; white-space: nowrap; }
        th { font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #eef2ff; }
        .col-student { min-width: 180px; }
        .col-category { min-width: 120px; }
        .col-amount { min-width: 90px; text-align: right; }
        .col-date { min-width: 140px; }
        .amount { font-variant-numeric: tabular-nums; }
        .badge-status { display: inline-flex; align-items: center; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; }
        .badge-status--pending { background: #fffbeb; color: #92400e; }
        .badge-status--under_review { background: #eff6ff; color: #1d4ed8; }
        .badge-status--committee_approved { background: #faf5ff; color: #6b21a8; }
        .badge-status--approved { background: #ecfdf5; color: #166534; }
        .badge-status--rejected { background: #fef2f2; color: #b91c1c; }
        .badge-status--disbursed { background: #eef2ff; color: #3730a3; }
        .empty-row { padding: 0.9rem 0.9rem; font-size: 0.85rem; color: #6b7280; }
        .search-bar { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .search-bar input { flex: 1; min-width: 200px; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.6rem 0.9rem; font-size: 0.9rem; outline: none; }
        .search-bar input:focus { border-color: #4f46e5; }
        .search-bar button { padding: 0.6rem 1.25rem; border-radius: 10px; background: #0f1419; color: #fff; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; }
        .search-bar button:hover { background: #1f2937; }
        .search-bar .btn-clear { background: #e5e7eb; color: #374151; }
        .search-bar .btn-clear:hover { background: #d1d5db; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #374151; background: #fff; border: 1px solid #e5e7eb; }
        .pagination a:hover { background: #f3f4f6; border-color: #d1d5db; }
        .pagination .active { background: #4f46e5; color: #fff; border-color: #4f46e5; pointer-events: none; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }
        .pagination-info { font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem; text-align: center; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1rem; font-size: 0.8rem; color: #9ca3af; }
        .btn-view { display: inline-block; padding: 0.35rem 0.9rem; border-radius: 8px; background: #4f46e5; color: #fff; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: background 0.15s; }
        .btn-view:hover { background: #4338ca; }
        @media (max-width: 900px) {
            .table-wrap { overflow-x: auto; }
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
                <h1 class="page-title">Student applications</h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <span style="font-size:0.875rem;color:#6b7280"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
            </header>

            <?php if ($dbError !== ''): ?>
                <div style="padding:1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:1.5rem;color:#b91c1c;font-size:0.9rem;">Database error: <?php echo htmlspecialchars($dbError); ?></div>
            <?php endif; ?>

            <section class="status-grid">
                <?php
                    $recentCount = 0;
                    foreach ($totalsByStatus as $c) $recentCount += (int) $c;
                    $recentActive = $selectedStatus <= 0;
                ?>
                <a class="status-card status-card--disbursed <?php echo $recentActive ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_list_url(0, 1, $search)); ?>">
                    <div>
                        <div class="status-card-title">Recent</div>
                        <div class="status-card-count"><?php echo $recentCount; ?></div>
                    </div>
                    <span class="status-pill"><?php echo $recentCount === 1 ? '1 application' : $recentCount . ' applications'; ?></span>
                </a>
                <?php foreach ($statusRows as $s): ?>
                    <?php
                        $sid = (int) ($s['id'] ?? 0);
                        $name = (string) ($s['name'] ?? '');
                        $cssKey = str_replace(' ', '_', $name);
                        $count = (int) ($totalsByStatus[$sid] ?? 0);
                        $active = $selectedStatus === $sid;
                    ?>
                    <a class="status-card <?php echo 'status-card--' . htmlspecialchars($cssKey); ?> <?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_list_url($sid, 1, $search)); ?>">
                        <div>
                            <div class="status-card-title"><?php echo htmlspecialchars(status_label($name)); ?></div>
                            <div class="status-card-count"><?php echo $count; ?></div>
                        </div>
                        <span class="status-pill"><?php echo $count === 1 ? '1 application' : $count . ' applications'; ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($statusRows)): ?>
                    <div class="status-card">
                        <div>
                            <div class="status-card-title">No status data</div>
                            <div class="status-card-count">0</div>
                        </div>
                        <span class="status-pill">No records</span>
                    </div>
                <?php endif; ?>
            </section>

            <form method="get" action="application.php" class="search-bar">
                <?php if ($selectedStatus > 0): ?>
                    <input type="hidden" name="status" value="<?php echo $selectedStatus; ?>">
                <?php endif; ?>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by student name or ID...">
                <button type="submit">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo htmlspecialchars(build_list_url($selectedStatus, 1, '')); ?>" class="btn-clear" style="padding:0.6rem 1.25rem;border-radius:10px;font-size:0.9rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">Clear</a>
                <?php endif; ?>
            </form>

            <?php
                $titleStatus = null;
                foreach ($statusRows as $x) {
                    if ((int)($x['id'] ?? 0) === $selectedStatus) {
                        $titleStatus = $x;
                        break;
                    }
                }
                $title = $selectedStatus <= 0 ? 'Recent applications' : status_label($titleStatus['name'] ?? 'Applications');
                $maxPage = max(1, (int) ceil($total / $perPage));
            ?>
            <section class="status-section">
                <h2><?php echo htmlspecialchars($title); ?></h2>
                <p class="status-section-sub">
                    <?php echo $total; ?> <?php echo $total === 1 ? 'application' : 'applications'; ?><?php echo $search !== '' ? ' (filtered)' : ''; ?>.
                </p>
                <div class="table-wrap">
                    <?php if (empty($applications)): ?>
                        <div class="empty-row"><?php echo $search !== '' ? 'No matching applications.' : 'No applications found.'; ?></div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>App ID</th>
                                    <th class="col-student">Student</th>
                                    <th>Matric / Course</th>
                                    <th class="col-category">Category</th>
                                    <th>Subtype</th>
                                    <th>Status</th>
                                    <th class="col-amount" style="text-align:right">Amount (RM)</th>
                                    <th class="col-date">Submitted at</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <?php
                                        $sid = (int) ($app['status_id'] ?? 0);
                                        $statusName = '';
                                        foreach ($statusRows as $sr) { if ((int) $sr['id'] === $sid) { $statusName = (string) ($sr['name'] ?? ''); break; } }
                                        $badgeKey = str_replace(' ', '_', $statusName);
                                    ?>
                                    <tr>
                                        <td>#<?php echo (int) ($app['id'] ?? 0); ?></td>
                                        <td class="col-student"><?php echo htmlspecialchars($app['full_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(trim((string) ($app['course'] ?? '')) ?: '-'); ?></td>
                                        <td class="col-category"><?php echo htmlspecialchars(ucwords((string) ($app['category'] ?? ''))); ?></td>
                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($app['subtype'] ?? '')))); ?></td>
                                        <td><span class="badge-status <?php echo $badgeKey !== '' ? 'badge-status--' . htmlspecialchars($badgeKey) : ''; ?>"><?php echo htmlspecialchars($statusName !== '' ? status_label($statusName) : '—'); ?></span></td>
                                        <td class="col-amount amount"><?php echo $app['amount_applied'] !== null ? number_format((float) $app['amount_applied'], 2) : '-'; ?></td>
                                        <td class="col-date"><?php echo $app['created_at'] ? htmlspecialchars(date('d M Y, H:i', strtotime($app['created_at']))) : '-'; ?></td>
                                        <td><a href="viewApplication.php?id=<?php echo (int) ($app['id'] ?? 0); ?>" class="btn-view">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($maxPage > 1): ?>
                            <div class="pagination-info">Showing <?php echo ($page - 1) * $perPage + 1; ?>–<?php echo min($page * $perPage, $total); ?> of <?php echo $total; ?></div>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo htmlspecialchars(build_list_url($selectedStatus, $page - 1, $search)); ?>">Prev</a>
                                <?php else: ?>
                                    <span class="disabled">Prev</span>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $maxPage; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars(build_list_url($selectedStatus, $i, $search)); ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($page < $maxPage): ?>
                                    <a href="<?php echo htmlspecialchars(build_list_url($selectedStatus, $page + 1, $search)); ?>">Next</a>
                                <?php else: ?>
                                    <span class="disabled">Next</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>

            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>

