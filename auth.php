<?php
session_start();
require_once('db.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = ($_POST['role'] ?? 'consumer') === 'creator' ? 'creator' : 'consumer';
  if ($action === 'signup') {
    if ($username && $password) {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
      $stmt->bind_param('sss', $username, $hash, $role);
      if ($stmt->execute()) {
        $msg = 'Account created. Please login.';
      } else {
        $err = 'Signup failed (username may exist).';
      }
    } else { $err = 'Fill all fields.'; }
  } elseif ($action === 'login') {
    if ($username && $password) {
      $stmt = $conn->prepare('SELECT id, password, role FROM users WHERE username=? LIMIT 1');
      $stmt->bind_param('s', $username);
      $stmt->execute();
      $res = $stmt->get_result()->fetch_assoc();
      if ($res && password_verify($password, $res['password'])) {
        $_SESSION['user_id'] = $res['id'];
        $_SESSION['username'] = $username; // IMPORTANT: store username string for existing code
        $_SESSION['role'] = $res['role'];
        header('Location: dashboard.php'); exit;
      } else {
        $err = 'Invalid credentials.';
      }
    } else { $err = 'Fill all fields.'; }
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Auth</title></head><body class="bg-light">
<div class="container py-5" style="max-width:720px;">
  <h2 class="mb-4">Sign up or Login</h2>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <?php if(isset($msg)) echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; ?>
  <div class="row">
    <div class="col-md-6">
      <h4>Create Account</h4>
      <form method="post">
        <input type="hidden" name="action" value="signup">
        <div class="mb-2"><input name="username" class="form-control" placeholder="username" required></div>
        <div class="mb-2"><input name="password" type="password" class="form-control" placeholder="password" required></div>
        <div class="mb-2">
          <label class="form-label">Sign up as</label>
          <select name="role" class="form-select">
            <option value="consumer" selected>Consumer</option>
            <option value="creator">Creator</option>
          </select>
        </div>
        <button class="btn btn-danger w-100">Sign up</button>
      </form>
    </div>
    <div class="col-md-6">
      <h4>Login</h4>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <div class="mb-2"><input name="username" class="form-control" placeholder="username" required></div>
        <div class="mb-2"><input name="password" type="password" class="form-control" placeholder="password" required></div>
        <button class="btn btn-primary w-100">Login</button>
      </form>
    </div>
  </div>
  <p class="mt-3"><a href="index.php">Back to feed</a></p>
</div>
</body></html>
