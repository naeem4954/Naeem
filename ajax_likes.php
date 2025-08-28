<?php
session_start();
require_once('db.php');
if (isset($_GET['count']) && isset($_GET['video_id'])) {
    $vid = intval($_GET['video_id']);
    $count = $conn->query("SELECT COUNT(*) AS c FROM likes WHERE video_id=$vid")->fetch_assoc()['c'];
    $liked = false;
    if (isset($_SESSION['username'])) {
        $u = $conn->real_escape_string($_SESSION['username']);
        $liked = (bool)$conn->query("SELECT 1 FROM likes WHERE video_id=$vid AND username='$u' LIMIT 1")->fetch_assoc();
    }
    echo json_encode(['count'=>intval($count), 'liked'=>$liked]);
    exit;
}
if (!isset($_SESSION['username'])) { http_response_code(401); echo json_encode(['error'=>'login']); exit; }
$vid = intval($_POST['video_id']);
$user = $conn->real_escape_string($_SESSION['username']);
$check = $conn->query("SELECT id FROM likes WHERE video_id=$vid AND username='$user' LIMIT 1")->fetch_assoc();
if ($check) {
    $conn->query("DELETE FROM likes WHERE id=".$check['id']);
} else {
    $stmt = $conn->prepare('INSERT IGNORE INTO likes (video_id, username) VALUES (?,?)');
    $stmt->bind_param('is', $vid, $user);
    $stmt->execute();
}
$count = $conn->query("SELECT COUNT(*) AS c FROM likes WHERE video_id=$vid")->fetch_assoc()['c'];
echo json_encode(['count'=>intval($count)]);
?>