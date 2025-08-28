<?php
session_start();
require_once('db.php');

header('Content-Type: application/json');

// Fetch comments for a video
if (isset($_GET['fetch']) && isset($_GET['video_id'])) {
    $vid = intval($_GET['video_id']);
    $cstmt = $conn->prepare('SELECT comment, username, created_at FROM comments WHERE video_id = ? ORDER BY id DESC LIMIT 200');
    $cstmt->bind_param('i', $vid);
    $cstmt->execute();
    $res = $cstmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    echo json_encode($out);
    exit;
}

// Insert a new comment
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'login']);
    exit;
}

$vid = isset($_POST['video_id']) ? intval($_POST['video_id']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($vid <= 0 || $comment === '') {
    echo json_encode(['error' => 'invalid']);
    exit;
}

$user = $_SESSION['username'];

$stmt = $conn->prepare('INSERT INTO comments (video_id, username, comment) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $vid, $user, $comment);
$stmt->execute();

// Return the updated list
$cstmt = $conn->prepare('SELECT comment, username, created_at FROM comments WHERE video_id = ? ORDER BY id DESC LIMIT 200');
$cstmt->bind_param('i', $vid);
$cstmt->execute();
$res = $cstmt->get_result();
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;

echo json_encode($out);
