<?php
session_start();
include 'Actions/dbconnect.php';

// ---- Session/role guard ----
$role = $_SESSION['role'] ?? '';
$uid  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($uid <= 0) {
  header("Location: login.php");
  exit;
}

// ---- Flash messages ----
$success_message = $_SESSION['success'] ?? null;
$error_message   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ---- Fetch user row ----
$q = $conn->prepare("SELECT * FROM user WHERE user_id = ?");
$q->bind_param('i', $uid);
$q->execute();
$res = $q->get_result();
if (!$res || $res->num_rows === 0) {
  $_SESSION['error'] = "An error occurred when retrieving your info. Please log in again.";
  header("Location: login.php"); exit;
}
$row = $res->fetch_assoc();

// ---- Membership status logic ----
$startRaw = trim($row['membership_start'] ?? '');
$endRaw   = trim($row['membership_end']   ?? '');
$startDT  = $startRaw ? date_create($startRaw) : null;
$endDT    = $endRaw   ? date_create($endRaw)   : null;
$today    = new DateTime('today');

$membershipBadgeText = 'Not set';
$membershipNote      = '';
$badgeClass          = 'neutral';
$daysRemaining       = null;

if ($startDT && $endDT) {
  if ($today > $endDT) {
    $membershipBadgeText = 'Expired';
    $badgeClass = 'expired';
    $d = $endDT->diff($today)->days;
    $membershipNote = "expired {$d} day" . ($d==1?'':'s') . " ago";
  } elseif ($today < $startDT) {
    $membershipBadgeText = 'Pending';
    $badgeClass = 'pending';
    $d = $today->diff($startDT)->days;
    $membershipNote = "activates in {$d} day" . ($d==1?'':'s');
  } else {
    $daysRemaining = $today->diff($endDT)->days;
    $membershipBadgeText = ($daysRemaining <= 7) ? 'Expiring soon' : 'Active';
    $badgeClass = ($daysRemaining <= 7) ? 'expiring' : 'active';
    $membershipNote = "{$daysRemaining} day" . ($daysRemaining==1?'':'s') . " remaining";
  }
} elseif ($startDT || $endDT) {
  if ($startDT && $today < $startDT) {
    $membershipBadgeText = 'Pending';
    $badgeClass = 'pending';
    $d = $today->diff($startDT)->days;
    $membershipNote = "activates in {$d} day" . ($d==1?'':'s');
  } elseif ($endDT && $today > $endDT) {
    $membershipBadgeText = 'Expired';
    $badgeClass = 'expired';
  } else {
    $membershipBadgeText = 'Active';
    $badgeClass = 'active';
  }
}

// Show membership type only if Member and within active window
$rawType = trim($row['membership_type'] ?? '');
$isActiveWindow = ($role === 'Member' && $startDT && $endDT && $today >= $startDT && $today <= $endDT);
$membershipTypeDisplay = $isActiveWindow && $rawType !== '' ? $rawType : '-';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>
    <?php
      if ($role === "Admin")      { echo "Administrator"; }
      elseif ($role === "Staff")  { echo "Staff"; }
      elseif ($role === "Member") { echo "Member"; }
      else                        { echo "User"; }
    ?> | Remer Fitness Gym
  </title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>

  <style>
    /* Notification Dropdown */
    .notification-container{position:relative}
    .notification-bell{background:none;border:none;font-size:24px;cursor:pointer;position:relative;color:#f8f9fa;padding:8px 12px;transition:.3s}
    .notification-bell:hover{color:#ffc107;transform:scale(1.1)}
    .notification-badge{position:absolute;top:2px;right:4px;background:#dc3545;color:#fff;border-radius:50%;padding:2px 6px;font-size:11px;font-weight:bold;min-width:18px;text-align:center}
    .notification-dropdown{display:none;position:absolute;top:100%;right:0;width:380px;max-height:520px;background:#fff;border:1px solid #ddd;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.2);z-index:1000;margin-top:10px;overflow:hidden}
    .notification-dropdown.show{display:block}
    .notification-header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff}
    .notification-tabs{display:flex;border-bottom:1px solid #000000ff}
    .notification-tab{flex:1;text-align:center;padding:8px 10px;cursor:pointer;font-weight:600;font-size:13px}
    .notification-tab.active{background:#f8f9fa}
    .notification-actions{display:flex;gap:8px}
    .notification-actions button{background:rgba(255,255,255,.2);border:none;color:#fff;padding:4px 10px;border-radius:6px;font-size:12px;cursor:pointer}
    .notification-list{max-height:400px;overflow-y:auto}
    .notification-item{padding:12px 14px;border-bottom:1px solid #000000ff;cursor:pointer;color:#333;display:flex}
    .notification-item:hover{background:#f8f9fa}
    .notification-item.unread{background:linear-gradient(to right,#e7f3ff 0%,#f0f8ff 100%);border-left:3px solid #0d6efd}
    .notif-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;margin-right:12px;flex-shrink:0;background:#eef1f7}
    .notif-content{flex:1}
    .notif-title{font-weight:600;margin-bottom:4px;font-size:14px;color:#212529}
    .notif-message{font-size:13px;color:#6c757d;line-height:1.4}
    .notif-time{font-size:11px;color:#999;margin-top:4px;display:flex;align-items:center;gap:4px}
    .no-notifications{padding:32px 16px;text-align:center;color:#999}
    .no-notifications i{font-size:42px;color:#ddd;margin-bottom:10px}
    .loading-notifications{padding:20px;text-align:center;color:#999}

    /* === Make all text inside the white profile card BLACK === */
.rounded-5.bg-white,
.rounded-5.bg-white * {
  color: #000 !important;
}

/* Keep these items readable (they should stay white on colored backgrounds) */
.rounded-5.bg-white .btn-primary,
.rounded-5.bg-white .btn-danger,
.rounded-5.bg-white .ms-badge {
  color: #fff !important;
}

/* Keep your blue highlight for values like the membership type and numbers */
.rounded-5.bg-white .text-blue {
  color: #0b6bdc !important;
}

/* Labels inside the Profile tab (Age:, Phone:, Email:, Address:) */
#profileContent p { 
  color: #000 !important; 
}
#profileContent p span { 
  /* value stays blue per your design */
  color: #0b6bdc !important; 
}

/* Optional: remove the faint 'ghost' look on the big name if needed */
.shadow-blur-blue { 
  text-shadow: none !important; 
}

/* Notification dropdown text (in case any part still looked washed-out) */
.notification-dropdown,
.notification-dropdown * {
  color: #000 !important;
}

    /* Membership status strip */
    .membership-strip{
      border-radius:14px;
      background:linear-gradient(180deg, rgba(0,119,192,.08), rgba(199,238,255,.10));
      border:1px solid rgba(0, 0, 0, 0.35);
      color:#0b2a3a;
      padding:.6rem .9rem;
      display:flex; align-items:center; gap:.8rem; flex-wrap:wrap;
    }
    .ms-badge{
      font-weight:800; letter-spacing:.02em;
      padding:.28rem .6rem; border-radius:999px; color:#fff;
    }
    .ms-badge.active{background:#28a745;}
    .ms-badge.expiring{background:#fd7e14;}
    .ms-badge.expired{background:#dc3545;}
    .ms-badge.pending{background:#6f42c1;}
    .ms-badge.neutral{background:#6c757d;}

    .ms-item{display:flex; align-items:center; gap:.35rem; color:#0b2a3a; font-weight:700;}
    .ms-item i{color:#0b5e8a;}

    .text-blue{color:#0077C0;}
    .shadow-blur-blue{ text-shadow:0 2px 12px rgba(0,119,192,.28); }

    .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:26px;}
    @media (min-width:768px){ .actions{ margin-top:34px; } }

    /* Basic dark bg to match your theme */
    body { background:#2b2b2b; color:#f8f9fa; }
    .navbar { background-color:transparent !important; }
    .navbar .container { border-bottom:1px solid var(--text-clr); }
    .logo-img { max-height:50px; height:auto; }
  </style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">


<!-- Flash -->
<?php if ($success_message): ?>
  <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
    <?= htmlspecialchars($success_message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($error_message): ?>
  <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
    <?= htmlspecialchars($error_message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-transparent">
  <div class="container px-md-4 px-lg-5 py-1" style="border-bottom:1px solid var(--text-clr);">
    <a class="navbar-brand" href="#">
      <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <?php if ($role === 'Admin'): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
          <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
          <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
          <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
          <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
          <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
          <li class="nav-item"><a class="nav-link active" href="my-profile.php">Profile</a></li>
        <?php elseif ($role === 'Staff'): ?>
          <li class="nav-item"><a class="nav-link" href="staff_class.php">Class</a></li>
          <li class="nav-item"><a class="nav-link" href="staff_attendance.php">Attendance</a></li>
          <li class="nav-item"><a class="nav-link" href="staff_schedule.php">Schedule</a></li>
          <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
          <li class="nav-item"><a class="nav-link" href="staff_payment_verification.php">Transaction</a></li>
          <li class="nav-item"><a class="nav-link active" href="my-profile.php">Profile</a></li>
        <?php elseif ($role === 'Member'): ?>
          <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="member_shop.php">Shop</a></li>
          <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
          <li class="nav-item"><a class="nav-link" href="trackprogress.php">Track Progress</a></li>
          <li class="nav-item"><a class="nav-link" href="member_payment.php">Payment</a></li>
          <li class="nav-item"><a class="nav-link active" href="my-profile.php">Profile</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link active" href="my-profile.php">Profile</a></li>
        <?php endif; ?>
      </ul>

      <!-- 🔔 Notification Bell (ALL roles) -->
      <div class="notification-container">
        <button class="notification-bell" id="notificationBell" aria-label="Notifications">
          <i class="ri-notification-3-line"></i>
          <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
        </button>

        <div class="notification-dropdown" id="notificationDropdown">
          <div class="notification-header">
            <strong><i class="ri-notification-2-fill"></i>&nbsp;Notifications</strong>
            <div class="notification-actions">
              <button id="markAllReadBtn">Mark all</button>
              <button id="clearAllBtn">Clear</button>
            </div>
          </div>

          <div class="notification-tabs">
            <div class="notification-tab active" id="tabUnread">Unread</div>
            <div class="notification-tab" id="tabRead">Read</div>
          </div>

          <div class="notification-list" id="listUnread">
            <div class="loading-notifications">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 mb-0">Loading notifications…</p>
            </div>
          </div>

          <div class="notification-list" id="listRead" style="display:none;">
            <div class="loading-notifications">
              <div class="spinner-border spinner-border-sm text-secondary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 mb-0">Loading notifications…</p>
            </div>
          </div>
        </div>
      </div>
      <!-- /bell -->
    </div>
  </div>
</nav>

<!-- Body -->
<div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
  <div class="w-100">
    <a href="#" onclick="event.preventDefault(); window.history.back();" class="btn btn-light rounded-circle fw-bold text-nowrap" style="width: 40px; height: 40px;">
      <i class="ri-arrow-go-back-line"></i>
    </a>
  </div>

  <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
    <div class="rounded-5 bg-white py-5 px-4 d-flex flex-column gap-4" style="min-height: 700px; width: 540px;">

      <!-- Name + Role + Membership Type -->
      <div class="w-100 d-flex flex-column">
        <h1 class="lh-2 m-0 p-0 display-6 fw-bold">
          <span class="text-blue fw-bold display-5 shadow-blur-blue"><?= htmlspecialchars($row['lname'] ?? '') ?></span>,
          <?= htmlspecialchars($row['fname'] ?? '') ?>
        </h1>

        <?php if($role === "Admin") { ?>
          <p class="lh-2 m-0 p-0 fs-6">Administrator</p>
        <?php } elseif($role === "Staff") { ?>
          <p class="lh-2 m-0 p-0 fs-6">Position: <span class="text-blue fw-bold fs-5"><?= htmlspecialchars($row['staff_position'] ?? '') ?></span></p>
        <?php } elseif($role === "Member") { ?>
          <!-- Membership type shows on the next line -->
        <?php } else { ?>
          <p class="lh-2 m-0 p-0 fs-6">User</p>
        <?php } ?>

        <p class="lh-2 m-0 p-0 fs-6">Membership Type:
          <span class="text-blue fw-bold fs-5"><?= htmlspecialchars($membershipTypeDisplay) ?></span>
        </p>
      </div>

      <!-- Membership Status Strip (Members only) -->
      <?php if ($role === "Member"): ?>
      <div class="membership-strip">
        <span class="ms-badge <?= htmlspecialchars($badgeClass) ?>"><i class="ri-vip-crown-line"></i> <?= htmlspecialchars($membershipBadgeText) ?></span>
        <?php if ($membershipNote): ?>
          <span class="ms-item"><i class="ri-timer-2-line"></i> <?= htmlspecialchars($membershipNote) ?></span>
        <?php endif; ?>
        <span class="ms-item"><i class="ri-calendar-event-line"></i> Start: <?= $startDT ? htmlspecialchars($startDT->format('M d, Y')) : '—' ?></span>
        <span class="ms-item"><i class="ri-calendar-check-line"></i> End: <?= $endDT ? htmlspecialchars($endDT->format('M d, Y')) : '—' ?></span>
      </div>
      <?php endif; ?>

      <!-- Content -->
      <div class="d-flex flex-column flex-grow-1">
        <div class="btn-group switch-btn-group w-100" role="group" aria-label="Profile QR switch">
          <button type="button" class="btn btn-outline-primary active" id="profileBtn">Profile</button>
          <button type="button" class="btn btn-outline-primary" id="qrBtn">QR Code</button>
        </div>

        <div class="mt-4 d-flex justify-content-center align-items-center w-100 h-100">
          <div id="profileContent" class="d-flex justify-content-start flex-column w-100 h-100 gap-3 pt-3 ps-1">
            <p class="lh-2 m-0 p-0 fs-6">Age: <span class="text-blue fw-bold fs-5"><?= htmlspecialchars($row['age'] ?? '') ?></span></p>
            <p class="lh-2 m-0 p-0 fs-6">Phone: <span class="text-blue fw-bold fs-5"><?= !empty($row['phone']) ? htmlspecialchars($row['phone']) : "Not given" ?></span></p>
            <p class="lh-2 m-0 p-0 fs-6">Email: <span class="text-blue fw-bold fs-5"><?= htmlspecialchars($row['email'] ?? '') ?></span></p>
            <p class="lh-2 m-0 p-0 fs-6">Address: <span class="text-blue fw-bold fs-5"><?= htmlspecialchars(($row['street'] ?? '') . ", " . ($row['city'] ?? '') . ", " . ($row['province'] ?? '')) ?></span></p>

            <div class="w-100 flex-grow-1 d-flex justify-content-end align-items-end">
              <div class="actions mt-4 mt-md-5">
                <a href="edit-profile.php" class="btn btn-primary px-4">Edit Profile</a>
                <a href="Actions/action_logout.php" class="btn btn-danger px-4">Log out</a>
              </div>
            </div>
          </div>

          <div id="qrContent" class="d-none d-flex justify-content-center align-items-center w-100 h-100">
            <div id="qrcode" class="p-2" style="border-radius: 16px; border: 8px solid black;"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<!-- Notifications (unified API for ALL roles) -->
<script>
(function(){
  const ROLE = <?= json_encode($role) ?>;

  const bell       = document.getElementById('notificationBell');
  const drop       = document.getElementById('notificationDropdown');
  const badge      = document.getElementById('notificationCount');
  const tabUnread  = document.getElementById('tabUnread');
  const tabRead    = document.getElementById('tabRead');
  const listUnread = document.getElementById('listUnread');
  const listRead   = document.getElementById('listRead');
  const btnMarkAll = document.getElementById('markAllReadBtn');
  const btnClearAll= document.getElementById('clearAllBtn');

  if (!bell) return;

  bell.addEventListener('click', (e)=>{
    e.stopPropagation();
    drop.classList.toggle('show');
  });
  document.addEventListener('click', (e)=>{
    if (!e.target.closest('.notification-container')) drop.classList.remove('show');
  });

  // Tabs
  tabUnread?.addEventListener('click', ()=>{
    tabUnread.classList.add('active');
    tabRead?.classList.remove('active');
    listUnread.style.display = 'block';
    listRead.style.display = 'none';
  });
  tabRead?.addEventListener('click', ()=>{
    tabRead.classList.add('active');
    tabUnread.classList.remove('active');
    listUnread.style.display = 'none';
    listRead.style.display = 'block';
  });

  const api = {
    list   : 'Actions/notifs_list_split.php',
    markOne: 'Actions/notifs_mark_read.php',
    markAll: 'Actions/notifs_mark_all.php',
    clear  : 'Actions/notifs_clear.php'
  };

  function loadNotifs(){
    listUnread.innerHTML = `<div class="loading-notifications">
      <div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      <p class="mt-2 mb-0">Loading notifications…</p>
    </div>`;
    listRead.innerHTML = '';

    fetch(api.list).then(r=>r.json()).then(d=>{
      const unread = d.unread || [];
      const read   = d.read   || [];
      renderList(listUnread, unread, true);
      renderList(listRead, read, false);
      setBadge(d.unread_count || unread.length);
    }).catch(()=>{
      listUnread.innerHTML = `<div class="no-notifications"><i class="ri-error-warning-line"></i><p>Failed to load notifications</p></div>`;
      listRead.innerHTML = '';
      setBadge(0);
    });
  }

  function setBadge(n){
    if (!badge) return;
    if (n>0){ badge.textContent = n>99?'99+':n; badge.style.display='block'; }
    else { badge.style.display='none'; }
  }

  function renderList(container, items, canClickToRead){
    if (!items.length){
      container.innerHTML = `<div class="no-notifications"><i class="ri-notification-off-line"></i><p>No notifications</p></div>`;
      return;
    }
    container.innerHTML = items.map(n=>{
      const unread = (+n.is_read===0);
      const icon = pickIcon(n.type);
      const time = timeAgo(n.created_at);
      const title = escapeHtml(n.title||'');
      const msgFull = n.message || '';
      const msg   = escapeHtml(msgFull.length>120 ? msgFull.slice(0,120)+'…' : msgFull);
      const clickable = canClickToRead ? `data-id="${(n.id||'')}"` : '';
      return `
        <div class="notification-item ${unread?'unread':''}" ${clickable}>
          <div class="notif-icon"><i class="${icon}"></i></div>
          <div class="notif-content">
            <div class="notif-title">${title}</div>
            <div class="notif-message">${msg}</div>
            <div class="notif-time"><i class="ri-time-line"></i>${time}</div>
          </div>
        </div>`;
    }).join('');

    if (canClickToRead){
      container.querySelectorAll('.notification-item[data-id]').forEach(el=>{
        el.addEventListener('click', ()=>{
          const id = parseInt(el.getAttribute('data-id')||'0',10);
          if (!id) return;
          markOne(id);
        });
      });
    }
  }

  function pickIcon(type){
    const map = {
      renewal_confirmation:'ri-check-double-line',
      membership_expiry:'ri-calendar-close-line',
      payment_due:'ri-money-dollar-circle-line',
      expired:'ri-close-circle-line',
      class_booking:'ri-calendar-check-fill',
      promo:'ri-gift-fill',
      system:'ri-notification-3-fill',
      payment:'ri-money-dollar-circle-fill',
      default:'ri-notification-3-fill'
    };
    return map[type] || map.default;
  }

  function timeAgo(s){
    const d = new Date(s), n = new Date(), secs = Math.floor((n - d)/1000);
    if (isNaN(secs)) return '';
    if (secs < 60) return 'Just now';
    if (secs < 3600) return Math.floor(secs/60) + ' min ago';
    if (secs < 86400) return Math.floor(secs/3600) + ' hrs ago';
    if (secs < 604800) return Math.floor(secs/86400) + ' days ago';
    return d.toLocaleDateString();
  }
  function escapeHtml(t){ const div = document.createElement('div'); div.textContent = t ?? ''; return div.innerHTML; }

  function markOne(id){
    fetch(api.markOne, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id })
    }).then(()=>loadNotifs());
  }

  btnMarkAll?.addEventListener('click', ()=>{
    fetch(api.markAll, {method:'POST'}).then(()=>loadNotifs());
  });

  btnClearAll?.addEventListener('click', ()=>{
    if (!confirm('Clear your notifications?')) return;
    fetch(api.clear, {method:'POST'}).then(()=>loadNotifs());
  });

  loadNotifs();
  setInterval(loadNotifs, 30000);
})();
</script>

<script>
  // QR switcher
  const profileBtn = document.getElementById('profileBtn');
  const qrBtn = document.getElementById('qrBtn');
  const profileContent = document.getElementById('profileContent');
  const qrContent = document.getElementById('qrContent');

  profileBtn?.addEventListener('click', () => {
      profileBtn.classList.add('active');
      qrBtn.classList.remove('active');
      profileContent.classList.remove('d-none');
      qrContent.classList.add('d-none');
  });

  qrBtn?.addEventListener('click', () => {
      qrBtn.classList.add('active');
      profileBtn.classList.remove('active');
      qrContent.classList.remove('d-none');
      profileContent.classList.add('d-none');
  });

  <?php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $path = rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/\\');
    $qr_url = $protocol . $host . $path . "/Actions/action_qr_attendance.php?user_id=" . ($uid ?: 0);
  ?>
  const qrUrl = "<?= $qr_url ?>";
  const qrCodeContainer = document.getElementById("qrcode");
  if (qrCodeContainer) {
    new QRCode(qrCodeContainer, { text: qrUrl, width: 256, height: 256 });
  }
</script>

</body>
</html>
