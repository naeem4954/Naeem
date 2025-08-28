<?php
session_start();
require_once('db.php');
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? 'consumer') != 'creator') { die('Not allowed'); }
$title = $_POST['title'] ?? '';
$publisher = $_POST['publisher'] ?? '';
$producer = $_POST['producer'] ?? '';
$genre = $_POST['genre'] ?? '';
$age = $_POST['age_rating'] ?? '';
if (!isset($_FILES['video'])) { die('No file'); }
$orig = $_FILES['video']['name'];
$filename = time() . '_' . basename($orig);
$target = __DIR__ . '/uploads/' . $filename;
if (move_uploaded_file($_FILES['video']['tmp_name'], $target)) {
    $u = $_SESSION['username'];
    $stmt = $conn->prepare('INSERT INTO videos (title, name, filename, username, publisher, producer, genre, age_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssssss', $title, $orig, $filename, $u, $publisher, $producer, $genre, $age);
    $stmt->execute();
    header('Location: dashboard.php');
    exit;
} else {
    echo 'Upload failed';
}
?>