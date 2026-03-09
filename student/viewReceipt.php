<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$appId = (int) ($_GET['id'] ?? 0);

if ($appId <= 0) {
    header('Location: history.php');
    exit;
}

$row = null;
try {
    $stmt = $pdo->prepare('SELECT id, user_id, receipt_path FROM applications WHERE id = ? AND user_id = ?');
    $stmt->execute([$appId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: history.php');
    exit;
}

if (!$row || empty($row['receipt_path'])) {
    header('Location: history.php');
    exit;
}

$filePath = __DIR__ . '/../public/' . $row['receipt_path'];
if (!is_file($filePath)) {
    header('Location: history.php');
    exit;
}

$ext = strtolower(pathinfo($row['receipt_path'], PATHINFO_EXTENSION));
$mime = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
][$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="receipt_' . $appId . '.' . $ext . '"');
readfile($filePath);
exit;
