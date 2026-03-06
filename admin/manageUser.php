<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$perPage = 10;
$studentQ = trim($_GET['student_q'] ?? '');
$staffQ = trim($_GET['staff_q'] ?? '');
$studentPage = max(1, (int) ($_GET['student_page'] ?? 1));
$staffPage = max(1, (int) ($_GET['staff_page'] ?? 1));
$studentOffset = ($studentPage - 1) * $perPage;
$staffOffset = ($staffPage - 1) * $perPage;

$students = [];
$studentTotal = 0;
$staff = [];
$staffTotal = 0;

try {
    $sql = 'SELECT id, full_name, email, phone, created_at FROM users WHERE role = "student"';
    $params = [];
    if ($studentQ !== '') {
        $sql .= ' AND full_name LIKE ?';
        $params[] = '%' . $studentQ . '%';
    }
    $stmtCount = $pdo->prepare(str_replace('SELECT id, full_name, email, phone, created_at', 'SELECT COUNT(*)', $sql));
    $stmtCount->execute($params);
    $studentTotal = (int) $stmtCount->fetchColumn();

    $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $studentOffset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

try {
    $sql = 'SELECT id, staff_id, full_name, email, phone, created_at FROM admin WHERE 1=1';
    $params = [];
    if ($staffQ !== '') {
        $sql .= ' AND (staff_id LIKE ? OR full_name LIKE ?)';
        $params[] = '%' . $staffQ . '%';
        $params[] = '%' . $staffQ . '%';
    }
    $stmtCount = $pdo->prepare(str_replace('SELECT id, staff_id, full_name, email, phone, created_at', 'SELECT COUNT(*)', $sql));
    $stmtCount->execute($params);
    $staffTotal = (int) $stmtCount->fetchColumn();

    $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $staffOffset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$studentHasNext = ($studentOffset + count($students)) < $studentTotal;
$studentHasPrev = $studentPage > 1;
$staffHasNext = ($staffOffset + count($staff)) < $staffTotal;
$staffHasPrev = $staffPage > 1;

function formatDate($d) {
    return $d ? date('d M Y', strtotime($d)) : '—';
}

function qs($overrides = []) {
    $q = $_GET;
    foreach ($overrides as $k => $v) { $q[$k] = $v; }
    return http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User — RCMP UniFa</title>
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
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; }
        .user-menu { display: flex; align-items: center; gap: 0.75rem; }
        .user-menu a { color: #4b5563; text-decoration: none; font-size: 0.875rem; }
        .user-menu a:hover { color: #111827; }
        .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
        .section-card h2 { font-size: 1.1rem; font-weight: 600; color: #111827; padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
        .list-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .list-table th { text-align: left; padding: 0.75rem 1rem; background: #f9fafb; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; }
        .list-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; color: #4b5563; }
        .list-table tr:last-child td { border-bottom: 0; }
        .list-table tr:hover td { background: #fafafa; }
        .btn-view { display: inline-flex; align-items: center; padding: 0.4rem 0.85rem; border-radius: 8px; background: #4f46e5; color: #fff; font-size: 0.8rem; font-weight: 500; text-decoration: none; transition: background 0.15s; }
        .btn-view:hover { background: #4338ca; }
        .empty-row td { color: #9ca3af; font-style: italic; padding: 1.25rem; }
        .search-row { display: flex; gap: 0.5rem; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; flex-wrap: wrap; }
        .search-row input { border-radius: 8px; border: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; font-size: 0.9rem; width: 200px; }
        .search-row input:focus { outline: none; border-color: #4f46e5; }
        .search-row button { padding: 0.5rem 1rem; border-radius: 8px; background: #4f46e5; color: #fff; border: none; font-size: 0.85rem; font-weight: 500; cursor: pointer; }
        .search-row button:hover { background: #4338ca; }
        .pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb; background: #f9fafb; flex-wrap: wrap; gap: 0.5rem; }
        .pagination-info { font-size: 0.85rem; color: #6b7280; }
        .pagination-links { display: flex; gap: 0.35rem; }
        .pagination-links a, .pagination-links span { display: inline-flex; padding: 0.4rem 0.75rem; border-radius: 6px; font-size: 0.85rem; text-decoration: none; color: #374151; background: #fff; border: 1px solid #e5e7eb; }
        .pagination-links a:hover { background: #f3f4f6; color: #111827; }
        .pagination-links span.disabled { color: #9ca3af; cursor: not-allowed; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
            .list-table th, .list-table td { padding: 0.5rem 0.75rem; font-size: 0.85rem; }
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
                <a href="applications.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application</a>
                <a href="manageUser.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage user</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="page-header">
                <h1 class="page-title">Manage user</h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <a href="dashboard.php">Dashboard</a>
                </div>
            </header>

            <div class="section-card">
                <h2>Students</h2>
                <form method="get" action="" class="search-row">
                    <input type="hidden" name="student_page" value="1">
                    <input type="hidden" name="staff_page" value="<?php echo (int) $staffPage; ?>">
                    <input type="hidden" name="staff_q" value="<?php echo htmlspecialchars($staffQ); ?>">
                    <input type="search" name="student_q" placeholder="Search by full name" value="<?php echo htmlspecialchars($studentQ); ?>">
                    <button type="submit">Search</button>
                </form>
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr class="empty-row"><td colspan="5">No students found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $i => $row): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo formatDate($row['created_at']); ?></td>
                                    <td><a href="viewUser.php?type=student&amp;id=<?php echo (int) $row['id']; ?>" class="btn-view">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <span class="pagination-info"><?php echo $studentTotal; ?> student(s) · Page <?php echo $studentPage; ?></span>
                    <div class="pagination-links">
                        <?php if ($studentHasPrev): ?>
                            <a href="?<?php echo qs(['student_page' => $studentPage - 1]); ?>">Previous</a>
                        <?php else: ?>
                            <span class="disabled">Previous</span>
                        <?php endif; ?>
                        <?php if ($studentHasNext): ?>
                            <a href="?<?php echo qs(['student_page' => $studentPage + 1]); ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2>Staff</h2>
                <form method="get" action="" class="search-row">
                    <input type="hidden" name="staff_page" value="1">
                    <input type="hidden" name="student_page" value="<?php echo (int) $studentPage; ?>">
                    <input type="hidden" name="student_q" value="<?php echo htmlspecialchars($studentQ); ?>">
                    <input type="search" name="staff_q" placeholder="Search by staff ID or name" value="<?php echo htmlspecialchars($staffQ); ?>">
                    <button type="submit">Search</button>
                </form>
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staff)): ?>
                            <tr class="empty-row"><td colspan="6">No staff found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($staff as $row): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo formatDate($row['created_at']); ?></td>
                                    <td><a href="viewUser.php?type=staff&amp;id=<?php echo (int) $row['id']; ?>" class="btn-view">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <span class="pagination-info"><?php echo $staffTotal; ?> staff · Page <?php echo $staffPage; ?></span>
                    <div class="pagination-links">
                        <?php if ($staffHasPrev): ?>
                            <a href="?<?php echo qs(['staff_page' => $staffPage - 1]); ?>">Previous</a>
                        <?php else: ?>
                            <span class="disabled">Previous</span>
                        <?php endif; ?>
                        <?php if ($staffHasNext): ?>
                            <a href="?<?php echo qs(['staff_page' => $staffPage + 1]); ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
