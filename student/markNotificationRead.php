<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$redirect = $_GET['to'] ?? 'history.php';

if ($id > 0) {
    try {
        $stmt = $pdo->prepare('UPDATE notification SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, (int) $_SESSION['user_id']]);
    } catch (PDOException $e) {}
}

$allowed = ['history.php', 'dashboard.php', 'application.php'];
if (!in_array($redirect, $allowed, true)) $redirect = 'history.php';
header('Location: ' . $redirect);
exit;
