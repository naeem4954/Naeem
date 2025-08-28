<?php
require_once('db.php'); session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
$err=''; $msg='';
$role = $_SESSION['role'] ?? 'consumer';
$username = $_SESSION['username'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['video']) && $role==='creator') {
  $title = trim($_POST['title'] ?? 'Untitled');
  $publisher = trim($_POST['publisher'] ?? '');
  $producer = trim($_POST['producer'] ?? '');
  $genre = trim($_POST['genre'] ?? '');
  $age_rating = trim($_POST['age_rating'] ?? '');
  $file = $_FILES['video'];
  $filename = time().'_'.basename($file['name']);
  $target = __DIR__ . '/uploads/' . $filename;
  if (move_uploaded_file($file['tmp_name'], $target)) {
    $stmt = $conn->prepare('INSERT INTO videos (title, name, filename, username, publisher, producer, genre, age_rating) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('ssssssss', $title, $file['name'], $filename, $username, $publisher, $producer, $genre, $age_rating);
    if ($stmt->execute()) { $msg='Uploaded successfully'; } else { $err='DB error'; }
  } else { $err='Upload failed'; }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['comment']) && isset($_POST['video_id'])) {
  $v = intval($_POST['video_id']); $comment = trim($_POST['comment']);
  if ($comment !== '') {
    $ins = $conn->prepare('INSERT INTO comments (video_id, username, comment) VALUES (?,?,?)');
    $ins->bind_param('iss', $v, $username, $comment);
    $ins->execute();
    $msg = 'Comment posted';
  }
}

$res = $conn->query('SELECT id, title, filename, username, genre, age_rating, uploaded_at FROM videos ORDER BY uploaded_at DESC LIMIT 50');
$videos = $res->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">
<nav class="navbar navbar-dark" style="background:#7a0000"><div class="container-fluid"><span class="navbar-brand">RedTok Dashboard</span><div>Signed in as <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($role); ?>) &nbsp; <a class="text-white" href="logout.php">Logout</a></div></div></nav>
<div class="container py-4">
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; if($msg) echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; ?>
  <?php if($role==='creator'): ?>
    <h4>Upload a new video</h4>
    <form method="POST" enctype="multipart/form-data" class="mb-4">
      <div class="mb-2"><input name="title" class="form-control" placeholder="Title" required></div>
      <div class="row"><div class="col"><input name="publisher" class="form-control" placeholder="Publisher"></div><div class="col"><input name="producer" class="form-control" placeholder="Producer"></div></div>
      <div class="row mt-2"><div class="col"><input name="genre" class="form-control" placeholder="Genre"></div><div class="col"><input name="age_rating" class="form-control" placeholder="Age rating"></div></div>
      <div class="mb-2 mt-2"><input type="file" name="video" accept="video/mp4" class="form-control" required></div>
      <button class="btn btn-danger">Upload</button>
    </form>
  <?php else: ?>
    <h4>Welcome, <?php echo htmlspecialchars($username); ?> — browse latest videos</h4>
  <?php endif; ?>

  <h5 class="mt-3">Latest videos</h5>
  <?php foreach($videos as $v): ?>
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between"><div><strong><?php echo htmlspecialchars($v['title']); ?></strong><div class="small text-muted">@<?php echo htmlspecialchars($v['username']); ?> • <?php echo htmlspecialchars($v['genre']); ?> • <?php echo htmlspecialchars($v['age_rating']); ?></div></div>
      <div><a class="btn btn-sm btn-outline-secondary" href="/public/video.php?id=<?php echo $v['id']; ?>">View</a></div></div>
      <div class="mt-2"><video controls width="100%"><source src="/uploads/<?php echo htmlspecialchars($v['filename']); ?>" type="video/mp4"></video></div>
      <div class="mt-2">
        <form method="POST" class="d-flex">
          <input type="hidden" name="video_id" value="<?php echo $v['id']; ?>">
          <input name="comment" class="form-control me-2" placeholder="Add a comment...">
          <button class="btn btn-sm btn-primary">Post</button>
        </form>
      </div>
    </div></div>
  <?php endforeach; ?>
</div>
</body></html>