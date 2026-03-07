<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Staff';

$statusRows = [];
$applicationsByStatus = [];

try {
    $stmt = $pdo->query('
        SELECT s.id, s.name, s.display_order, COUNT(a.id) AS total
        FROM status s
        LEFT JOIN applications a ON a.status_id = s.id
        GROUP BY s.id, s.name, s.display_order
        ORDER BY s.display_order
    ');
    $statusRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statusRows = [];
}

try {
    $stmt = $pdo->query('
        SELECT a.*, u.full_name, u.course, u.year, s.name AS status_name, s.display_order
        FROM applications a
        JOIN users u ON u.id = a.user_id
        JOIN status s ON s.id = a.status_id
        ORDER BY s.display_order, a.created_at DESC
    ');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sid = (int) $row['status_id'];
        if (!isset($applicationsByStatus[$sid])) {
            $applicationsByStatus[$sid] = [];
        }
        $applicationsByStatus[$sid][] = $row;
    }
} catch (PDOException $e) {
    $applicationsByStatus = [];
}

function status_label(string $name): string {
    return ucwords(str_replace('_', ' ', $name));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications — RCMP UniFa Admin</title>
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
        .status-card-title { font-size: 0.9rem; color: #4b5563; margin-bottom: 0.25rem; }
        .status-card-count { font-size: 1.6rem; font-weight: 700; color: #111827; }
        .status-pill { font-size: 0.75rem; padding: 0.15rem 0.6rem; border-radius: 999px; border: 1px solid #e5e7eb; color: #6b7280; background: #f9fafb; }
        .status-card--pending { border-color: #fbbf24; background: #fffbeb; }
        .status-card--under_review { border-color: #3b82f6; background: #eff6ff; }
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
        .badge-status--approved { background: #ecfdf5; color: #166534; }
        .badge-status--rejected { background: #fef2f2; color: #b91c1c; }
        .badge-status--disbursed { background: #eef2ff; color: #3730a3; }
        .empty-row { padding: 0.9rem 0.9rem; font-size: 0.85rem; color: #6b7280; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1rem; font-size: 0.8rem; color: #9ca3af; }
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
                <a href="manageUser.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage user</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
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

            <section class="status-grid">
                <?php foreach ($statusRows as $s): ?>
                    <?php
                        $name = (string) ($s['name'] ?? '');
                        $cssKey = str_replace(' ', '_', $name);
                        $count = (int) ($s['total'] ?? 0);
                    ?>
                    <div class="status-card <?php echo 'status-card--' . htmlspecialchars($cssKey); ?>">
                        <div>
                            <div class="status-card-title"><?php echo htmlspecialchars(status_label($name)); ?></div>
                            <div class="status-card-count"><?php echo $count; ?></div>
                        </div>
                        <span class="status-pill"><?php echo $count === 1 ? '1 application' : $count . ' applications'; ?></span>
                    </div>
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

            <?php foreach ($statusRows as $s): ?>
                <?php
                    $sid = (int) $s['id'];
                    $name = (string) ($s['name'] ?? '');
                    $apps = $applicationsByStatus[$sid] ?? [];
                    $badgeKey = str_replace(' ', '_', $name);
                ?>
                <section class="status-section">
                    <h2><?php echo htmlspecialchars(status_label($name)); ?></h2>
                    <p class="status-section-sub">
                        <?php echo count($apps); ?> student <?php echo count($apps) === 1 ? 'application' : 'applications'; ?> in this status.
                    </p>
                    <div class="table-wrap">
                        <?php if (empty($apps)): ?>
                            <div class="empty-row">No applications in this status yet.</div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th class="col-student">Student</th>
                                        <th>Matric / Course</th>
                                        <th class="col-category">Category</th>
                                        <th>Subtype</th>
                                        <th class="col-amount" style="text-align:right">Amount (RM)</th>
                                        <th class="col-date">Submitted at</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($apps as $app): ?>
                                        <tr>
                                            <td class="col-student">
                                                <?php echo htmlspecialchars($app['full_name'] ?? ''); ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $course = trim((string) ($app['course'] ?? ''));
                                                    $year = trim((string) ($app['year'] ?? ''));
                                                    $meta = trim($course . ($year !== '' ? ' · Year ' . $year : ''));
                                                    echo $meta !== '' ? htmlspecialchars($meta) : '-';
                                                ?>
                                            </td>
                                            <td class="col-category">
                                                <?php echo htmlspecialchars(ucwords((string) ($app['category'] ?? ''))); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($app['subtype'] ?? '')))); ?>
                                            </td>
                                            <td class="col-amount amount">
                                                <?php
                                                    $amt = $app['amount_applied'];
                                                    echo $amt !== null ? number_format((float) $amt, 2) : '-';
                                                ?>
                                            </td>
                                            <td class="col-date">
                                                <?php
                                                    $dt = $app['created_at'] ?? null;
                                                    echo $dt ? htmlspecialchars(date('d M Y, H:i', strtotime($dt))) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge-status <?php echo 'badge-status--' . htmlspecialchars($badgeKey); ?>">
                                                    <?php echo htmlspecialchars(status_label($name)); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>

