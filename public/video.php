<?php
// Video page with AJAX comments & rating (keeps design)
session_start();
require_once('../db.php');
$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM videos WHERE id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();
if (!$video) { echo 'Not found'; exit; }
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($video['title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#1a0000;color:#fff;font-family:Arial;} .container{padding:20px;} .btn-like{background:transparent;border:1px solid rgba(255,255,255,0.12);color:#fff;padding:6px 10px;border-radius:8px;} .btn-like.liked{background:#e60000;color:#fff;border-color:#e60000;}</style>
</head><body>
<div class="container">
  <h3><?php echo htmlspecialchars($video['title']); ?></h3>
  <div class="small text-muted">@<?php echo htmlspecialchars($video['username']); ?> | <?php echo htmlspecialchars($video['genre']); ?> | Age: <?php echo htmlspecialchars($video['age_rating']); ?></div>
  <div class="my-3"><video controls width="100%"><source src="/uploads/<?php echo htmlspecialchars($video['filename']); ?>" type="video/mp4"></video></div>

  <div class="mb-3">
    <button id="likeBtn" class="btn-like">❤️ <span id="likeCount">...</span></button>
    <button class="btn btn-outline-light" onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied')">🔗 Share</button>
  </div>

  <h5>Comments</h5>
  <?php if(isset($_SESSION['username'])): ?>
    <form id="commentForm" class="mb-3" onsubmit="postComment(event)">
      <input type="hidden" id="vid" value="<?php echo $id; ?>">
      <textarea id="commentText" class="form-control mb-2" required></textarea>
      <button class="btn btn-danger">Post comment</button>
    </form>
  <?php else: ?>
    <div>Please <a href="/auth.php">login</a> to comment.</div>
  <?php endif; ?>

  <div id="comments"></div>
</div>
<script>
const vid = <?php echo $id; ?>;
async function loadLike(){ const res=await fetch('/ajax_likes.php?count=1&video_id='+vid); const j=await res.json(); document.getElementById('likeCount').innerText=j.count; if(j.liked) document.getElementById('likeBtn').classList.add('liked'); }
document.getElementById('likeBtn').addEventListener('click', async ()=>{ const fd=new FormData(); fd.append('video_id', vid); const res=await fetch('/ajax_likes.php', {method:'POST', body:fd}); if(res.status===401){ alert('Please login'); return;} const j=await res.json(); document.getElementById('likeCount').innerText=j.count; document.getElementById('likeBtn').classList.toggle('liked'); });
async function loadComments(){ const res=await fetch('/ajax_comments.php?fetch=1&video_id='+vid); const arr=await res.json(); const c=document.getElementById('comments'); c.innerHTML=''; arr.forEach(e=>{ const d=document.createElement('div'); d.innerHTML='<strong>@'+e.username+'</strong> <small>'+e.created_at+'</small><div>'+e.comment+'</div>'; c.appendChild(d); }); }
async function postComment(e){ e.preventDefault(); const text=document.getElementById('commentText').value.trim(); if(!text) return; const fd=new FormData(); fd.append('video_id', vid); fd.append('comment', text); const res=await fetch('/ajax_comments.php', {method:'POST', body:fd}); if(res.status===401){ alert('Please login'); return;} const arr=await res.json(); document.getElementById('commentText').value=''; loadComments(); }
loadLike(); loadComments();
</script>
</body></html>
