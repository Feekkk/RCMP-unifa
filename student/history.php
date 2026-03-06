<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$applications = [];
try {
    $stmt = $pdo->prepare('SELECT id, category, subtype, amount_applied, bank_name, bank_account, status, created_at FROM applications WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // table may not exist
}

function formatCategory($c) {
    $map = ['bereavement' => 'Bereavement (Khairat)', 'illness' => 'Illness & Injuries', 'emergency' => 'Emergency'];
    return $map[$c] ?? ucfirst($c);
}
function formatSubtype($s) {
    return ucfirst(str_replace('_', ' ', $s));
}
function formatDate($d) {
    return $d ? date('d M Y', strtotime($d)) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View History — RCMP UniFa</title>
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
        .main-content { flex: 1; min-width: 0; padding: 1.5rem 2rem 2rem; overflow-x: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; margin: 0; }
        .btn-home { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 10px; background: #fff; color: #4b5563; font-size: 0.875rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .btn-home:hover { background: #f3f4f6; color: #111827; }
        .btn-home svg { width: 18px; height: 18px; }
        .history-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .history-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .history-table th { text-align: left; padding: 0.85rem 1rem; background: #f9fafb; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; }
        .history-table td { padding: 0.85rem 1rem; border-bottom: 1px solid #f3f4f6; color: #4b5563; }
        .history-table tr:last-child td { border-bottom: 0; }
        .history-table tr:hover td { background: #fafafa; }
        .status { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 500; text-transform: capitalize; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-under_review { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-disbursed { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .empty-state { text-align: center; padding: 3rem 2rem; color: #6b7280; font-size: 0.95rem; }
        .empty-state a { color: #4f46e5; text-decoration: none; font-weight: 500; }
        .empty-state a:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
            .history-table th, .history-table td { padding: 0.6rem 0.75rem; font-size: 0.85rem; }
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
                <a href="application.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application form</a>
                <a href="appInformation.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Application info</a>
                <a href="history.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>View history</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">View history</h1>
                <a href="dashboard.php" class="btn-home"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home</a>
            </div>

            <div class="history-card">
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <p>You have not submitted any applications yet.</p>
                        <p style="margin-top:0.5rem"><a href="application.php">Submit your first application →</a></p>
                    </div>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Ref</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Amount (RM)</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $row): ?>
                                <?php
                                $statusClass = 'status-pending';
                                if (isset($row['status'])) {
                                    $s = $row['status'];
                                    if ($s === 'approved' || $s === 'disbursed') $statusClass = 'status-approved';
                                    elseif ($s === 'under_review') $statusClass = 'status-under_review';
                                    elseif ($s === 'rejected') $statusClass = 'status-rejected';
                                }
                                ?>
                                <tr>
                                    <td>#<?php echo (int) $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars(formatCategory($row['category'])); ?></td>
                                    <td><?php echo htmlspecialchars(formatSubtype($row['subtype'])); ?></td>
                                    <td><?php echo $row['amount_applied'] !== null ? number_format((float) $row['amount_applied'], 2) : '—'; ?></td>
                                    <td><span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status'] ?? 'pending'); ?></span></td>
                                    <td><?php echo formatDate($row['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
