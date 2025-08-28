<?php
require_once('db.php');

$start = isset($_GET['start']) ? max(0, intval($_GET['start'])) : 0;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 30;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT id, title, name, filename, username, publisher, producer, genre, age_rating, uploaded_at
        FROM videos";
$params = [];
$types = '';

if ($q !== '') {
    $q_like = "%{$q}%";
    $sql .= " WHERE (title LIKE ? OR publisher LIKE ? OR producer LIKE ? OR genre LIKE ? OR age_rating LIKE ? OR username LIKE ?)";
    $params = [$q_like, $q_like, $q_like, $q_like, $q_like, $q_like];
    $types  = 'ssssss';
}

$sql .= " ORDER BY uploaded_at DESC LIMIT ?, ?";

$params[] = $start;
$params[] = $limit;
$types   .= 'ii';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'prepare_failed', 'details' => $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = $r;
}

header('Content-Type: application/json');
echo json_encode($out);
