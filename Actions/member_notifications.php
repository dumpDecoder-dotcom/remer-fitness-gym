<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
  header("Location: login.php"); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Notifications | Remer Fitness Gym</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="ri-notification-4-line"></i> My Notifications</h3>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" id="markAll">Mark all read</button>
      <button class="btn btn-outline-danger  btn-sm" id="clearAll">Clear all</button>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white"><strong>Unread</strong></div>
        <ul class="list-group list-group-flush" id="unreadList">
          <li class="list-group-item text-muted">Loading…</li>
        </ul>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white"><strong>Read</strong></div>
        <ul class="list-group list-group-flush" id="readList">
          <li class="list-group-item text-muted">Loading…</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
function loadNotifs(){
  fetch('Actions/member_notifs_list.php?limit=100')
    .then(r=>r.json())
    .then(d=>{
      renderList('unreadList', d.unread || [], true);
      renderList('readList', d.read || [], false);
    });
}
function renderList(elId, items, unread){
  const ul = document.getElementById(elId);
  if (!items.length){
    ul.innerHTML = `<li class="list-group-item text-muted">No ${unread?'unread':'read'} notifications</li>`;
    return;
  }
  ul.innerHTML = items.map(n => `
    <li class="list-group-item ${unread?'list-group-item-warning':''}">
      <div class="d-flex justify-content-between">
        <div>
          <div class="fw-semibold">${escapeHtml(n.title)}</div>
          <div class="small text-muted">${escapeHtml(n.message)}</div>
          <div class="small text-secondary"><i class="ri-time-line"></i> ${new Date(n.created_at).toLocaleString()}</div>
        </div>
        ${unread ? `<button class="btn btn-sm btn-outline-success" onclick="markRead(${n.id})">Mark read</button>` : ''}
      </div>
    </li>
  `).join('');
}
function markRead(id){
  fetch('Actions/member_notifs_mark_read.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id})
  }).then(()=>loadNotifs());
}
document.getElementById('markAll').onclick = ()=>{
  fetch('Actions/member_notifs_mark_all_read.php', {method:'POST'}).then(()=>loadNotifs());
};
document.getElementById('clearAll').onclick = ()=>{
  if (confirm('Clear all notifications?')) {
    fetch('Actions/member_notifs_clear.php', {method:'POST'}).then(()=>loadNotifs());
  }
};
function escapeHtml(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }
loadNotifs();
</script>
</body>
</html>
