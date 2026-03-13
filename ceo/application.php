<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['ceo_id']) && ($_SESSION['user_role'] ?? '') !== 'ceo') {
    header('Location: ../auth/login.php');
    exit;
}

$ceoName = $_SESSION['user_name'] ?? 'CEO';
$ceoId = (int) ($_SESSION['ceo_id'] ?? 0);
$search = trim((string) ($_GET['search'] ?? ''));
$perPage = 10;
$applications = [];
$total = 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$dbError = '';
$msg = $_GET['m'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $ids = $_POST['app_ids'] ?? [];
    if ($action === 'bulk_approve' && is_array($ids) && !empty($ids)) {
        $cleanIds = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) $cleanIds[$id] = true;
        }
        $cleanIds = array_keys($cleanIds);
        if (!empty($cleanIds)) {
            try {
                $pdo->beginTransaction();
                $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

                // Fetch which of the submitted IDs are actually still at status_id=6
                $stmt = $pdo->prepare("SELECT id FROM applications WHERE status_id = 6 AND id IN ($placeholders)");
                $stmt->execute($cleanIds);
                $okIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');

                if (!empty($okIds)) {
                    $updateStmt = $pdo->prepare('UPDATE applications SET status_id = 3 WHERE id = ? AND status_id = 6');
                    $histStmt   = $pdo->prepare("INSERT INTO application_history (application_id, from_status_id, to_status_id, staff_id, action, notes) VALUES (?, 6, 3, ?, 'ceo_approve', NULL)");
                    foreach ($okIds as $appId) {
                        $appId = (int) $appId;
                        $updateStmt->execute([$appId]);
                        $histStmt->execute([$appId, $ceoId ?: null]);
                    }
                }

                $pdo->commit();
                header('Location: application.php?m=approved');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['_bulk_err'] = $e->getMessage();
                header('Location: application.php?m=error');
                exit;
            }
        }
    }
    header('Location: application.php');
    exit;
}

try {
    $sql = "
        SELECT a.id, a.user_id, a.category, a.subtype, a.amount_applied, a.created_at,
               COALESCE(u.full_name, 'Unknown') AS full_name,
               COALESCE(u.course, '') AS course
        FROM applications a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.status_id = 6
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (u.full_name LIKE ? OR u.course LIKE ?" . (ctype_digit($search) ? " OR a.id = ?" : "") . ")";
        $params = ['%' . $search . '%', '%' . $search . '%'];
        if (ctype_digit($search)) $params[] = (int) $search;
    }
    $sql .= " ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($allApps);
    $offset = ($page - 1) * $perPage;
    $applications = array_slice($allApps, $offset, $perPage);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$maxPage = max(1, (int) ceil($total / $perPage));

function fmtCategory($c) {
    $map = ['bereavement' => 'Bereavement (Khairat)', 'illness' => 'Illness & Injuries', 'emergency' => 'Emergency'];
    return $map[$c] ?? ucfirst($c);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval — RCMP UniFa CEO</title>
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
        .search-bar { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .search-bar input { flex: 1; min-width: 200px; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.6rem 0.9rem; font-size: 0.9rem; outline: none; }
        .search-bar input:focus { border-color: #4f46e5; }
        .search-bar button { padding: 0.6rem 1.25rem; border-radius: 10px; background: #0f1419; color: #fff; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; }
        .search-bar .btn-clear { padding: 0.6rem 1.25rem; border-radius: 10px; font-size: 0.9rem; font-weight: 600; text-decoration: none; background: #e5e7eb; color: #374151; display: inline-flex; align-items: center; }
        .search-bar .btn-clear:hover { background: #d1d5db; }
        .bulk-bar { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; }
        .bulk-left { display: flex; align-items: center; gap: 0.75rem; color: #374151; font-size: 0.85rem; }
        .bulk-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .btn-approve { padding: 0.55rem 1rem; border-radius: 10px; background: #166534; color: #fff; font-size: 0.9rem; font-weight: 700; border: none; cursor: pointer; }
        .btn-approve:hover { background: #15803d; }
        .btn-approve:disabled { opacity: 0.55; cursor: not-allowed; }
        .btn-ghost { padding: 0.55rem 1rem; border-radius: 10px; background: #e5e7eb; color: #374151; font-size: 0.9rem; font-weight: 700; border: none; cursor: pointer; }
        .btn-ghost:hover { background: #d1d5db; }
        .check { width: 16px; height: 16px; accent-color: #4f46e5; }
        .selected-pill { font-size: 0.78rem; font-weight: 700; color: #4f46e5; background: rgba(79, 70, 229, 0.10); border: 1px solid rgba(79, 70, 229, 0.22); padding: 0.2rem 0.55rem; border-radius: 999px; }
        .flash { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: 0.9rem; }
        .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .table-wrap { border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        thead { background: #f9fafb; }
        th, td { padding: 0.6rem 0.9rem; text-align: left; white-space: nowrap; }
        th.col-check, td.col-check { width: 44px; padding-left: 0.75rem; padding-right: 0.5rem; }
        th { font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #eef2ff; }
        .col-student { min-width: 180px; }
        .col-category { min-width: 120px; }
        .col-amount { min-width: 90px; text-align: right; }
        .col-date { min-width: 140px; }
        .amount { font-variant-numeric: tabular-nums; }
        .empty-row { padding: 1.5rem; font-size: 0.9rem; color: #6b7280; text-align: center; }
        .btn-view { display: inline-block; padding: 0.35rem 0.9rem; border-radius: 8px; background: #4f46e5; color: #fff; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: background 0.15s; }
        .btn-view:hover { background: #4338ca; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #374151; background: #fff; border: 1px solid #e5e7eb; }
        .pagination a:hover { background: #f3f4f6; border-color: #d1d5db; }
        .pagination .active { background: #4f46e5; color: #fff; border-color: #4f46e5; pointer-events: none; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }
        .pagination-info { font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem; text-align: center; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1.5rem; font-size: 0.8rem; color: #9ca3af; }
        .badge-approved { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 500; background: #ecfdf5; color: #166534; }
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
                <a href="application.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Applications</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>
        <div class="main-content">
            <header class="page-header">
                <h1 class="page-title">Applications</h1>
                <div class="user-menu">
                    <span style="font-size:0.875rem;color:#6b7280"><?php echo htmlspecialchars($ceoName); ?></span>
                    <a href="../index.php">Home</a>
                </div>
            </header>

            <?php if ($dbError !== ''): ?>
                <div style="padding:1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:1.5rem;color:#b91c1c;font-size:0.9rem;">Database error: <?php echo htmlspecialchars($dbError); ?></div>
            <?php endif; ?>

            <?php if ($msg === 'approved'): ?><div class="flash flash-success">Selected applications approved successfully.</div><?php endif; ?>
            <?php if ($msg === 'error'):
                $bulkErr = $_SESSION['_bulk_err'] ?? '';
                unset($_SESSION['_bulk_err']);
            ?><div class="flash flash-error">Could not approve.<?php if ($bulkErr): ?> Error: <?php echo htmlspecialchars($bulkErr); ?><?php endif; ?></div><?php endif; ?>

            <form method="get" action="application.php" class="search-bar">
                <input type="hidden" name="page" value="1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by student name or course...">
                <button type="submit">Search</button>
                <?php if ($search !== ''): ?><a href="application.php" class="btn-clear">Clear</a><?php endif; ?>
            </form>

            <div class="table-wrap">
                <?php if (empty($applications)): ?>
                    <div class="empty-row"><?php echo $search !== '' ? 'No matching applications.' : 'No applications pending CEO approval.'; ?></div>
                <?php else: ?>
                    <form method="post" id="bulkApproveForm" onsubmit="return confirm('Approve selected applications?');">
                        <input type="hidden" name="action" value="bulk_approve">
                        <div>
                        <div class="bulk-bar" style="padding:0.85rem 0.9rem;border-bottom:1px solid #e5e7eb;background:linear-gradient(180deg,#ffffff 0%, #fafafa 100%);">
                            <div class="bulk-left">
                                <label style="display:inline-flex;align-items:center;gap:0.55rem;cursor:pointer;">
                                    <input type="checkbox" id="selectAll" class="check">
                                    Select all
                                </label>
                                <span id="selectedCount" class="selected-pill">0 selected</span>
                            </div>
                            <div class="bulk-actions">
                                <button type="button" class="btn-ghost" id="clearSelection">Clear</button>
                                <button type="submit" class="btn-approve" id="approveSelected" disabled>Approve selected</button>
                            </div>
                        </div>
                    <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th class="col-check"><input type="checkbox" class="check" id="selectAllTop" aria-label="Select all"></th>
                                <th>Ref</th>
                                <th class="col-student">Student</th>
                                <th>Course</th>
                                <th class="col-category">Category</th>
                                <th>Subtype</th>
                                <th class="col-amount" style="text-align:right">Amount (RM)</th>
                                <th class="col-date">Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td class="col-check"><input type="checkbox" class="rowCheck check" name="app_ids[]" value="<?php echo (int) $app['id']; ?>"></td>
                                    <td>#<?php echo (int) $app['id']; ?></td>
                                    <td class="col-student"><?php echo htmlspecialchars($app['full_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(trim($app['course'] ?? '') ?: '-'); ?></td>
                                    <td class="col-category"><?php echo htmlspecialchars(fmtCategory($app['category'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $app['subtype'] ?? ''))); ?></td>
                                    <td class="col-amount amount"><?php echo $app['amount_applied'] !== null ? number_format((float) $app['amount_applied'], 2) : '-'; ?></td>
                                    <td class="col-date"><?php echo $app['created_at'] ? date('d M Y, H:i', strtotime($app['created_at'])) : '-'; ?></td>
                                    <td><a href="viewApplication.php?id=<?php echo (int) $app['id']; ?>" class="btn-view">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    </div>
                    </form>
                    <?php if ($maxPage > 1): ?>
                        <div class="pagination-info">Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?></div>
                        <div class="pagination">
                            <?php if ($page > 1): $prev = ['page' => $page - 1]; if ($search !== '') $prev['search'] = $search; ?>
                                <a href="application.php?<?php echo http_build_query($prev); ?>">Prev</a>
                            <?php else: ?><span class="disabled">Prev</span><?php endif; ?>
                            <?php for ($i = 1; $i <= $maxPage; $i++): $q = ['page' => $i]; if ($search !== '') $q['search'] = $search; ?>
                                <?php if ($i === $page): ?><span class="active"><?php echo $i; ?></span>
                                <?php else: ?><a href="application.php?<?php echo http_build_query($q); ?>"><?php echo $i; ?></a><?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($page < $maxPage): $next = ['page' => $page + 1]; if ($search !== '') $next['search'] = $search; ?>
                                <a href="application.php?<?php echo http_build_query($next); ?>">Next</a>
                            <?php else: ?><span class="disabled">Next</span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
<script>
(function () {
    var selectAll = document.getElementById('selectAll');
    var selectAllTop = document.getElementById('selectAllTop');
    var checks = Array.prototype.slice.call(document.querySelectorAll('.rowCheck'));
    var selectedCount = document.getElementById('selectedCount');
    var approveBtn = document.getElementById('approveSelected');
    var clearBtn = document.getElementById('clearSelection');
    if (!selectAll || !selectAllTop || checks.length === 0 || !selectedCount || !approveBtn || !clearBtn) return;

    function refresh() {
        var selected = checks.filter(function (c) { return c.checked; }).length;
        selectedCount.textContent = selected + ' selected';
        approveBtn.disabled = selected === 0;
        selectAll.checked = selected > 0 && selected === checks.length;
        selectAll.indeterminate = selected > 0 && selected < checks.length;
        selectAllTop.checked = selectAll.checked;
        selectAllTop.indeterminate = selectAll.indeterminate;
    }

    function handleSelectAll(checked) {
        checks.forEach(function (c) { c.checked = checked; });
        refresh();
    }

    selectAll.addEventListener('change', function () { handleSelectAll(selectAll.checked); });
    selectAllTop.addEventListener('change', function () { handleSelectAll(selectAllTop.checked); });

    checks.forEach(function (c) { c.addEventListener('change', refresh); });

    clearBtn.addEventListener('click', function () {
        checks.forEach(function (c) { c.checked = false; });
        refresh();
    });

    refresh();
})();
</script>
</body>
</html>
