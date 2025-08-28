<?php
session_start();
require_once('db.php');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>RedTok Feed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{margin:0;background:#1a0000;color:#fff;font-family:Arial,sans-serif;}
    .header{background:#2b0000;padding:12px;position:fixed;top:0;left:0;right:0;z-index:1000;display:flex;align-items:center;gap:12px;}
    .brand{color:#fff;font-weight:700;font-size:1.2rem;margin-left:8px;}
    .search-white{background:#fff;border-radius:999px;padding:6px 10px;display:flex;align-items:center;gap:8px;min-width:320px;}
    .search-white input{border:none;outline:none;background:transparent;color:#000;width:320px;}
    .container-feeds{padding-top:84px;max-width:900px;margin:0 auto;padding-left:12px;padding-right:12px;}
    .video-card{min-height:70vh;display:flex;flex-direction:column;justify-content:flex-start;align-items:center;border-bottom:1px solid #330000;padding:20px;gap:12px}
    .meta{width:100%;display:flex;flex-wrap:wrap;gap:12px;font-size:.9rem;color:#f0caca;}
    .actions{display:flex;gap:10px;align-items:center}
    .like-btn{cursor:pointer;border:1px solid #ff6b6b;background:transparent;color:#ff6b6b;border-radius:999px;padding:4px 10px}
    .comment-box{width:100%;background:#260101;border-radius:12px;padding:10px;margin-top:6px}
    .comment-list{display:flex;flex-direction:column;gap:6px;max-height:220px;overflow:auto;margin-top:8px}
    .comment-item{background:#2e0202;border-radius:8px;padding:6px 8px;font-size:.9rem}
    .loading{text-align:center;color:#ffb3b3;margin:20px 0}
  </style>
</head>
<body>
  <div class="header">
    <div class="brand">RedTok</div>
    <div class="search-white">
      <input id="search" type="text" placeholder="Search videos...">
      <button id="searchBtn" class="btn btn-sm btn-danger">Search</button>
    </div>
    <div class="ms-auto">
      <?php if(isset($_SESSION['username'])): ?>
        <span class="me-2">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
      <?php else: ?>
        <a href="auth.php" class="btn btn-outline-light btn-sm">Login / Sign up</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="container-feeds">
    <div id="feed"></div>
    <div id="loading" class="loading">Loading...</div>
  </div>

<script>
const feed = document.getElementById('feed');
const loadingEl = document.getElementById('loading');
const searchEl = document.getElementById('search');
const searchBtn = document.getElementById('searchBtn');

let start = 0;
let limit = 8;
let loading = false;
let noMore = false;

function videoSrc(file) {
  return 'uploads/' + encodeURIComponent(file);
}

function videoCardHTML(v) {
  const id = Number(v.id);
  const title = v.title ? v.title : v.name;
  const meta =
    `Publisher: ${v.publisher ?? ''} · Producer: ${v.producer ?? ''} · Genre: ${v.genre ?? ''} · Rated: ${v.age_rating ?? ''}`;
  return `
    <div class="video-card" id="video-${id}">
      <h5 class="w-100">${escapeHtml(title)}</h5>
      <video src="${videoSrc(v.filename)}" controls playsinline style="max-height:60vh;max-width:100%;border-radius:12px;background:#000"></video>
      <div class="meta">${escapeHtml(meta)}</div>
      <div class="actions">
        <button class="like-btn" data-vid="${id}">❤️ Like</button>
        <span id="like-count-${id}">0</span>
      </div>
      <div class="comment-box">
        <form data-vid="${id}" class="comment-form">
          <div class="input-group input-group-sm">
            <input type="text" class="form-control" name="comment" placeholder="Add a comment..." required>
            <button class="btn btn-danger" type="submit">Post</button>
          </div>
        </form>
        <div id="comments-${id}" class="comment-list"></div>
      </div>
    </div>
  `;
}

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

async function fetchBatch(reset=false) {
  if (loading || noMore) return;
  loading = true;
  loadingEl.textContent = 'Loading...';

  const q = searchEl.value.trim();
  const url = `fetch.php?start=${start}&limit=${limit}` + (q ? `&q=${encodeURIComponent(q)}` : '');
  try {
    const r = await fetch(url);
    if (!r.ok) throw new Error('fetch failed');
    const arr = await r.json();

    if (reset) feed.innerHTML = '';
    if (arr.length === 0) {
      if (start === 0) loadingEl.textContent = 'No videos found.';
      noMore = true;
    } else {
      const html = arr.map(videoCardHTML).join('');
      feed.insertAdjacentHTML('beforeend', html);
      arr.forEach(v => {
        const id = Number(v.id);
        document.querySelector(`#video-${id} .like-btn`).addEventListener('click', () => toggleLike(id));
        loadLike(id);
        loadComments(id);
      });
      start += arr.length;
      loadingEl.textContent = '';
    }

    feed.querySelectorAll('.comment-form').forEach(f => {
      f.addEventListener('submit', postComment);
    });

  } catch (e) {
    console.error(e);
    loadingEl.textContent = 'Error loading videos.';
  } finally {
    loading = false;
  }
}

async function loadLike(id) {
  try {
    const r = await fetch(`ajax_likes.php?count=1&video_id=${id}`);
    const data = await r.json();
    document.getElementById(`like-count-${id}`).textContent = data.count ?? 0;
  } catch(e) {
    console.error(e);
  }
}

async function toggleLike(id) {
  try {
    const fd = new FormData();
    fd.append('video_id', id);
    const r = await fetch('ajax_likes.php', { method: 'POST', body: fd });
    if (r.status === 401) { alert('Please login to like'); return; }
    const data = await r.json();
    document.getElementById(`like-count-${id}`).textContent = data.count ?? 0;
  } catch(e) {
    console.error(e);
  }
}

async function loadComments(id) {
  try {
    const r = await fetch(`ajax_comments.php?fetch=1&video_id=${id}`);
    const data = await r.json();
    renderComments(id, data);
  } catch(e) {
    console.error(e);
  }
}

function renderComments(id, list) {
  const container = document.getElementById(`comments-${id}`);
  if (!container) return;
  if (!Array.isArray(list) || list.length === 0) {
    container.innerHTML = '<div class="text-muted" style="font-size:.9rem">No comments yet.</div>';
    return;
  }
  container.innerHTML = list.map(c =>
    `<div class="comment-item"><strong>${escapeHtml(c.username)}:</strong> ${escapeHtml(c.comment)} <span class="text-muted" style="font-size:.8rem"> · ${escapeHtml(c.created_at)}</span></div>`
  ).join('');
}

async function postComment(e) {
  e.preventDefault();
  const form = e.currentTarget;
  const id = Number(form.getAttribute('data-vid'));
  const input = form.querySelector('input[name="comment"]');
  const text = input.value.trim();
  if (!text) return;

  const fd = new FormData();
  fd.append('video_id', id);
  fd.append('comment', text);

  try {
    const r = await fetch('ajax_comments.php', { method: 'POST', body: fd });
    if (r.status === 401) { alert('Please login to comment'); return; }
    const data = await r.json();
    input.value = '';
    renderComments(id, data);
  } catch(e) {
    console.error(e);
  }
}

function onScroll() {
  if (loading || noMore) return;
  const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 500);
  if (nearBottom) fetchBatch();
}
window.addEventListener('scroll', onScroll);

let timer = null;
searchEl.addEventListener('input', () => {
  clearTimeout(timer);
  timer = setTimeout(() => { start=0; noMore=false; fetchBatch(true); }, 350);
});
searchBtn.addEventListener('click', () => { start=0; noMore=false; fetchBatch(true); });

fetchBatch();
</script>
</body>
</html>
