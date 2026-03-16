<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$perPage = 10;
$tab = $_GET['tab'] ?? 'students'; // students, staff, admins
$search = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$students = [];
$staff = [];
$admins = [];
$countStudents = 0;
$countStaff = 0;
$countAdmins = 0;

try {
    $countStudents = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
} catch (PDOException $e) {}

try {
    $countStaff = (int) $pdo->query('SELECT COUNT(*) FROM staff WHERE role IN (1,3)')->fetchColumn();
} catch (PDOException $e) {}

try {
    $countAdmins = (int) $pdo->query('SELECT COUNT(*) FROM staff WHERE role = 2')->fetchColumn();
} catch (PDOException $e) {}

try {
    if ($tab === 'students') {
        $sql = 'SELECT id, full_name, email, phone, course, created_at FROM users WHERE role = "student"';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (full_name LIKE ? OR email LIKE ? OR course LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $stmtCount = $pdo->prepare(str_replace('SELECT id, full_name, email, phone, course, created_at', 'SELECT COUNT(*)', $sql));
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($tab === 'staff') {
        $sql = 'SELECT id, staff_id, full_name, email, phone, created_at FROM staff WHERE role IN (1,3)';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (staff_id LIKE ? OR full_name LIKE ? OR email LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $stmtCount = $pdo->prepare(str_replace('SELECT id, staff_id, full_name, email, phone, created_at', 'SELECT COUNT(*)', $sql));
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = 'SELECT id, staff_id, full_name, email, phone, role, created_at FROM staff WHERE role = 2';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (staff_id LIKE ? OR full_name LIKE ? OR email LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $stmtCount = $pdo->prepare(str_replace('SELECT id, staff_id, full_name, email, phone, role, created_at', 'SELECT COUNT(*)', $sql));
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $students = $staff = $admins = [];
    $total = 0;
}

$maxPage = max(1, (int) ceil(($total ?? 0) / $perPage));

function formatDate($d) {
    return $d ? date('d M Y', strtotime($d)) : '—';
}

function buildUserUrl(string $tab, int $page, string $search): string {
    $q = ['tab' => $tab, 'page' => $page];
    if ($search !== '') $q['search'] = $search;
    return 'manageUser.php?' . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User — RCMP UniFa</title>
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
        .user-menu a { color: #4b5563; text-decoration: none; font-size: 0.875rem; }
        .user-menu a:hover { color: #111827; }
        .tabs { display: inline-flex; gap: 0.25rem; padding: 0.25rem; border-radius: 999px; background: #e5e7eb; }
        .tabs a { padding: 0.3rem 0.9rem; border-radius: 999px; font-size: 0.8rem; text-decoration: none; color: #4b5563; }
        .tabs a.active { background: #111827; color: #fff; }
        .pill-row { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; font-size: 0.8rem; color: #6b7280; }
        .pill { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.15rem 0.6rem; border-radius: 999px; background: #f3f4f6; border: 1px solid #e5e7eb; }
        .pill strong { font-weight: 600; color: #111827; }
        .search-bar { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .search-bar input { flex: 1; min-width: 220px; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.55rem 0.85rem; font-size: 0.9rem; outline: none; }
        .search-bar input:focus { border-color: #4f46e5; }
        .search-bar button { padding: 0.55rem 1.2rem; border-radius: 10px; background: #0f1419; color: #fff; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; }
        .search-bar button:hover { background: #111827; }
        .search-bar .btn-clear { background: #e5e7eb; color: #374151; text-decoration: none; display: inline-flex; align-items: center; }
        .table-wrap { border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        thead { background: #f9fafb; }
        th, td { padding: 0.6rem 0.9rem; text-align: left; white-space: nowrap; }
        th { font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #eef2ff; }
        .col-name { min-width: 180px; }
        .col-email { min-width: 180px; }
        .col-course { min-width: 140px; }
        .col-role { min-width: 120px; }
        .empty-row { padding: 0.9rem 0.9rem; font-size: 0.85rem; color: #6b7280; }
        .btn-view { display: inline-block; padding: 0.35rem 0.9rem; border-radius: 8px; background: #4f46e5; color: #fff; font-size: 0.8rem; font-weight: 500; text-decoration: none; }
        .btn-view:hover { background: #4338ca; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #374151; background: #fff; border: 1px solid #e5e7eb; }
        .pagination a:hover { background: #f3f4f6; border-color: #d1d5db; }
        .pagination .active { background: #4f46e5; color: #fff; border-color: #4f46e5; pointer-events: none; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }
        .pagination-info { font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem; text-align: center; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 1.5rem; font-size: 0.8rem; color: #9ca3af; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
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
                <a href="manageUser.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage user</a>
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
                <h1 class="page-title">Manage users</h1>
                <div class="user-menu">
                    <a href="../index.php">Home</a>
                    <span style="color:#d1d5db">|</span>
                    <a href="dashboard.php">Dashboard</a>
                </div>
            </header>

            <?php
                $tabLabel = $tab === 'staff' ? 'Campus staff' : ($tab === 'admins' ? 'Admins & committee' : 'Students');
                $tabTotal = $tab === 'staff' ? $countStaff : ($tab === 'admins' ? $countAdmins : $countStudents);
            ?>

            <div class="pill-row">
                <div class="tabs">
                    <a href="<?php echo htmlspecialchars(buildUserUrl('students', 1, $search)); ?>" class="<?php echo $tab === 'students' ? 'active' : ''; ?>">Students</a>
                    <a href="<?php echo htmlspecialchars(buildUserUrl('staff', 1, $search)); ?>" class="<?php echo $tab === 'staff' ? 'active' : ''; ?>">Staff</a>
                    <a href="<?php echo htmlspecialchars(buildUserUrl('admins', 1, $search)); ?>" class="<?php echo $tab === 'admins' ? 'active' : ''; ?>">Admins</a>
                </div>
                <span class="pill"><strong>Total students:</strong> <?php echo $countStudents; ?></span>
                <span class="pill"><strong>Total staff:</strong> <?php echo $countStaff; ?></span>
                <span class="pill"><strong>Total admins:</strong> <?php echo $countAdmins; ?></span>
            </div>

            <form method="get" action="manageUser.php" class="search-bar">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, course or staff ID...">
                <button type="submit">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo htmlspecialchars(buildUserUrl($tab, 1, '')); ?>" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-wrap">
                <?php if ($tab === 'students'): ?>
                    <?php if (empty($students)): ?>
                        <div class="empty-row"><?php echo $search !== '' ? 'No matching students found.' : 'No students found.'; ?></div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-name">Name</th>
                                    <th class="col-email">Email</th>
                                    <th class="col-course">Course</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $row): ?>
                                    <tr>
                                        <td><?php echo (int) $row['id']; ?></td>
                                        <td class="col-name"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="col-email"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="col-course"><?php echo htmlspecialchars($row['course'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                        <td><?php echo formatDate($row['created_at']); ?></td>
                                        <td><a href="viewUser.php?type=student&amp;id=<?php echo (int) $row['id']; ?>" class="btn-view">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php elseif ($tab === 'staff'): ?>
                    <?php if (empty($staff)): ?>
                        <div class="empty-row"><?php echo $search !== '' ? 'No matching staff found.' : 'No staff found.'; ?></div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Staff ID</th>
                                    <th class="col-name">Name</th>
                                    <th class="col-email">Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $row): ?>
                                    <tr>
                                        <td><?php echo (int) $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
                                        <td class="col-name"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="col-email"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                        <td><?php echo formatDate($row['created_at']); ?></td>
                                        <td><a href="viewUser.php?type=staff&amp;id=<?php echo (int) $row['id']; ?>" class="btn-view">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (empty($admins)): ?>
                        <div class="empty-row"><?php echo $search !== '' ? 'No matching admins found.' : 'No admins found.'; ?></div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Staff ID</th>
                                    <th class="col-name">Name</th>
                                    <th class="col-email">Email</th>
                                    <th class="col-role">Role</th>
                                    <th>Phone</th>
                                    <th>Added</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $row): ?>
                                    <tr>
                                        <td><?php echo (int) $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
                                        <td class="col-name"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="col-email"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="col-role">
                                            <?php
                                                $r = (int) ($row['role'] ?? 0);
                                                echo $r === 3 ? 'CEO' : 'Committee';
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                        <td><?php echo formatDate($row['created_at']); ?></td>
                                        <td><a href="viewUser.php?type=staff&amp;id=<?php echo (int) $row['id']; ?>" class="btn-view">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($maxPage > 1): ?>
                <div class="pagination-info">
                    Showing <?php echo ($page - 1) * $perPage + 1; ?>–<?php echo min($page * $perPage, $total); ?>
                    of <?php echo $total; ?> in <?php echo strtolower($tabLabel); ?>.
                </div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo htmlspecialchars(buildUserUrl($tab, $page - 1, $search)); ?>">Prev</a>
                    <?php else: ?>
                        <span class="disabled">Prev</span>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $maxPage; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars(buildUserUrl($tab, $i, $search)); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $maxPage): ?>
                        <a href="<?php echo htmlspecialchars(buildUserUrl($tab, $page + 1, $search)); ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
</html>
