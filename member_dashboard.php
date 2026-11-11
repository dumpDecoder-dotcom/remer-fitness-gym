<?php 
session_start();
include 'Actions/dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
    header("Location: login.php"); exit;
}

$user_id = (int)$_SESSION['user_id'];

/* base path helper so links work at / or /subfolder */
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
function url_to($path){
  global $basePath;
  return ($basePath === '/' ? '' : $basePath) . '/' . ltrim($path, '/');
}

/* --- helpers for promos/percent --- */
function normalize_percent($v){
  $v = (float)$v;
  if ($v < 0) $v = 0;
  return ($v <= 1.0) ? ($v * 100.0) : $v;
}
function format_percent_text($v){
  $v = normalize_percent($v);
  $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
  return $s . '% OFF';
}
function sanitize_money($v){
  $v = (float)$v;
  if ($v < 0) $v = 0;
  return $v;
}
function promoBadgeClass($t){ $t=strtolower((string)$t); return $t==='membership'?'badge-membership':($t==='class'?'badge-class':'badge-product'); }
function expiryText($end){
  if(!$end) return 'No expiry';
  $today=strtotime(date('Y-m-d')); $e=strtotime($end); $d=(int)ceil(($e-$today)/86400);
  if($d<0) return 'Expired'; if($d===0) return 'Expires today'; if($d<=7) return 'Expires in '.$d.' day'.($d>1?'s':'');
  return 'Valid until '.date('F j, Y',$e);
}

/* --- DB helpers --- */
function table_exists(mysqli $conn, string $table): bool {
  $sql = "SELECT 1 
          FROM information_schema.tables 
          WHERE table_schema = DATABASE() AND table_name = ?";
  if ($st = $conn->prepare($sql)) {
    $st->bind_param('s', $table);
    $st->execute();
    $res = $st->get_result();
    $ok  = ($res && $res->num_rows > 0);
    $st->close();
    return $ok;
  }
  return false;
}

/* --- Try to find a product to auto-add for a promo --- */
function find_product_for_promo(mysqli $conn, array $p): int {
  // 1) mapping table (optional)
  if (!empty($p['promo_id']) && table_exists($conn, 'promos_products')) {
    if ($stmt = $conn->prepare("SELECT product_id FROM promos_products WHERE promo_id = ? LIMIT 1")) {
      $stmt->bind_param('i', $p['promo_id']);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
          $pid = (int)$row['product_id'];
          $stmt->close();
          if ($pid > 0) return $pid;
        }
      }
      $stmt->close();
    }
  }

  // 2) fallback: fuzzy match product by promo title keywords (optional)
  $title = trim((string)($p['title'] ?? ''));
  if ($title !== '' && ($stmt = $conn->prepare("
      SELECT product_id 
      FROM product 
      WHERE (status IS NULL OR status NOT IN ('discontinued')) 
        AND product_name LIKE CONCAT('%', ?, '%')
      ORDER BY product_id DESC LIMIT 1
    "))) {
    $stmt->bind_param('s', $title);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($res && $row = $res->fetch_assoc()) {
        $pid = (int)$row['product_id'];
        $stmt->close();
        if ($pid > 0) return $pid;
      }
    }
    $stmt->close();
  }

  // 3) nothing found
  return 0;
}

/* build URL to the APPLY endpoint with THIS promo (+ product if found) */
function promoUrl(mysqli $conn, array $p, int $defaultQty = 1){
  $dtype = strtolower($p['discount_type'] ?? 'percent');
  $dval  = $p['discount_value'] ?? 0;
  if ($dtype === 'percent') {
    $dval = normalize_percent($dval); // endpoint expects 50 for 50%
  } else {
    $dval = sanitize_money($dval);
  }
  $q = [
    'promo_id'=>$p['promo_id']??0,
    'title'=>$p['title']??'',
    'type'=>$p['promo_type']??'product',
    'dtype'=>$dtype,
    'dval'=>$dval,
    'end'=>$p['end_date']??'',
  ];
  if(isset($p['original_price']) && $p['original_price']!==null) $q['op']=sanitize_money($p['original_price']);
  if(isset($p['new_price'])      && $p['new_price']!==null)      $q['np']=sanitize_money($p['new_price']);

  // try to attach a product for this promo
  $pid = find_product_for_promo($conn, $p);
  if ($pid > 0) {
    $q['product_id'] = $pid;
    $q['qty']        = max(1, (int)$defaultQty);
  }
  // point to the action that applies promo + adds product then redirects to view-cart
  global $basePath;
  $endpoint = ($basePath === '/' ? '' : $basePath) . '/Actions/apply_promo_and_add.php';
  return $endpoint . '?' . http_build_query($q);
}

/* member name */
$fname = 'Member';
if ($stmt = $conn->prepare("SELECT fname FROM user WHERE user_id = ?")) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) $fname = htmlspecialchars($row['fname'] ?: 'Member');
    $stmt->close();
}

/* promos (new table with fallback) */
$promos = [];
$today = date('Y-m-d');
$try_new_ok = false;

if ($stmt = $conn->prepare("
  SELECT promo_id, title, promo_type, discount_type, discount_value,
         original_price, new_price, start_date, end_date, status, description
  FROM promos
  WHERE status='active'
    AND (start_date IS NULL OR start_date <= ?)
    AND (end_date   IS NULL OR end_date   >= ?)
  ORDER BY (end_date IS NULL), end_date ASC, promo_id DESC
")) {
  $stmt->bind_param('ss', $today, $today);
  if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $promos[] = [
      'promo_id'=>(int)$r['promo_id'],
      'title'=>$r['title'],
      'promo_type'=>$r['promo_type'],
      'discount_type'=>$r['discount_type'],
      'discount_value'=>(float)$r['discount_value'],
      'original_price'=>$r['original_price']!==null?(float)$r['original_price']:null,
      'new_price'=>$r['new_price']!==null?(float)$r['new_price']:null,
      'start_date'=>$r['start_date'],
      'end_date'=>$r['end_date'],
      'description'=>$r['description'] ?? ''
    ];
    $try_new_ok = true;
  }
  $stmt->close();
}

if (!$try_new_ok || empty($promos)) {
  $legacy = @mysqli_query($conn,"SELECT promo_name, discount, end_date FROM promo WHERE end_date >= CURDATE() ORDER BY end_date ASC");
  if ($legacy) while ($r = mysqli_fetch_assoc($legacy)) $promos[] = [
    'promo_id'=>0,'title'=>$r['promo_name'],'promo_type'=>'product',
    'discount_type'=>'percent','discount_value'=>(float)$r['discount'],
    'original_price'=>null,'new_price'=>null,'start_date'=>null,
    'end_date'=>$r['end_date'],'description'=>''
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Remer Fitness Gym</title>
<link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
<style>
  body{
    background:linear-gradient(rgba(0,0,0,.75),rgba(0,0,0,.85)),url('Assets/Graphics/Deadlift Image.png') no-repeat center/cover fixed;
    color:#fff;min-height:100vh
  }
  .navbar{backdrop-filter:blur(10px);background:rgba(0,0,0,.5)!important}
  .navbar .nav-link{color:#fff!important}.navbar .nav-link:hover{color:#ffc107!important}
  .hero-section{text-align:center;padding:120px 20px 60px;text-shadow:0 3px 8px rgba(0,0,0,.7)}
  .dashboard-section{background:rgba(0,0,0,.7);border-radius:20px;padding:40px}

  /* ===== Notification bell & dropdown (same as profile) ===== */
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
  /* ===== end notifications ===== */

  /* Promo Card UI */
  .promo-card{
    position:relative;
    background:rgba(255,255,255,.94);
    border-left:6px solid #ffc107;
    border-radius:16px;
    box-shadow:0 4px 16px rgba(0,0,0,.25);
    padding:20px; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    cursor:pointer;
    overflow:hidden;
  }
  .promo-card:hover{
    transform:translateY(-6px);
    box-shadow:0 14px 32px rgba(0,0,0,.35);
    border-color:#ff6a00;
  }
  .promo-card::after{
    content:"";
    position:absolute; inset:-2px;
    background:radial-gradient(120px 60px at var(--mx,110%) var(--my,-10%), rgba(255,106,0,.22), transparent 60%);
    transition:opacity .2s ease;
    pointer-events:none; opacity:0;
  }
  .promo-card:hover::after{opacity:1}

  .promo-title{font-weight:800;font-size:1.15rem;color:#000;margin-bottom:6px}
  .promo-meta{display:flex;align-items:center;gap:8px;margin-bottom:10px}
  .promo-discount{font-size:1.05rem;color:#c1121f;font-weight:700}
  .badge-product{background:#e8f0ff;color:#1b4db1;border-radius:999px;padding:.2rem .6rem;font-weight:700}
  .badge-membership{background:#ffe8f1;color:#b11258;border-radius:999px;padding:.2rem .6rem;font-weight:700}
  .badge-class{background:#e9fff3;color:#0f7a45;border-radius:999px;padding:.2rem .6rem;font-weight:700}
  .promo-end{color:#444;font-size:.9rem}
  .promo-desc{color:#333;font-size:.95rem;margin-top:6px}

  /* Buttons */
  .claim-btn{
    display:inline-flex; align-items:center; justify-content:center;
    gap:.4rem; width:100%;
    background:linear-gradient(135deg,#667eea 0%, #764ba2 100%);
    color:#fff; border:none; border-radius:12px;
    padding:.6rem 1rem; font-weight:700;
    transition:transform .15s ease, box-shadow .2s ease, filter .15s ease;
  }
  .claim-btn:hover{transform:translateY(-2px); box-shadow:0 8px 20px rgba(102,126,234,.4); color:#fff; filter:brightness(1.05)}
  .claim-btn i{font-size:1.15rem}
</style>
</head>
<body class="d-flex flex-column gap-3 pb-3">

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container px-md-4 px-lg-5 py-1">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" class="logo-img" style="height:50px;"></a>
    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link active" href="member_dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="member_shop.php">Shop</a></li>
        <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="trackprogress.php">Track Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="member_payment.php">Payment</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>

      <!-- 🔔 Notification Bell (same as profile) -->
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

<section class="hero-section mt-5">
  <h1>Welcome, <?= $fname ?> 👋</h1>
  <p>Your journey to strength and balance begins today!</p>
</section>

<div class="container promo-section">
  <h2 class="text-center mb-4 fw-bold text-warning">🔥 Current Promos</h2>
  <div class="row g-4">
    <?php if (!empty($promos)): foreach ($promos as $p):
      $title  = htmlspecialchars($p['title']);
      $badge  = promoBadgeClass($p['promo_type']);
      $expiry = expiryText($p['end_date']);
      $dtype  = strtolower($p['discount_type']);
      $href   = promoUrl($conn, $p, 1); // adds 1 item by default if a product is found
    ?>
      <div class="col-md-4">
        <div class="promo-card h-100" data-href="<?= htmlspecialchars($href) ?>">
          <div class="promo-title"><?= $title ?></div>
          <div class="promo-meta">
            <span class="<?= $badge ?>"><?= strtoupper(htmlspecialchars($p['promo_type'] ?: 'PRODUCT')) ?></span>
            <span class="promo-discount">
              <?php if ($dtype === 'percent'): ?>
                💸 <?= htmlspecialchars(format_percent_text($p['discount_value'])) ?>
              <?php else: ?>
                💸 <?= '₱'.number_format(sanitize_money($p['discount_value']), 0) . ' OFF' ?>
              <?php endif; ?>
            </span>
          </div>
          <?php if (!empty($p['description'])): ?>
            <div class="promo-desc"><?= htmlspecialchars($p['description']) ?></div>
          <?php endif; ?>
          <div class="promo-end mt-2">Valid: <strong><?= htmlspecialchars($expiry) ?></strong></div>

          <!-- Button links to endpoint that applies promo + adds product, then redirects to view-cart -->
          <a href="<?= htmlspecialchars($href) ?>" class="btn claim-btn mt-3">
            <i class="ri-gift-fill"></i> Claim &amp; View Cart
          </a>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="col-12 text-center text-light">
        <p>No active promos right now. Check back soon!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="container dashboard-section mt-3">
  <h2 class="text-warning fw-bold mb-4 text-center">Member Dashboard</h2>
  <div class="row mt-3 g-4">
    <div class="col-md-4">
      <div class="card shadow border-0 p-4 text-center">
        <i class="ri-calendar-check-line display-4 text-primary mb-3"></i>
        <h5>Book Your Classes</h5>
        <p class="text-muted">Reserve your next workout session easily.</p>
        <a href="member_bookclass.php" class="btn btn-primary">Book Now</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow border-0 p-4 text-center">
        <i class="ri-bar-chart-box-line display-4 text-success mb-3"></i>
        <h5>Track Your Progress</h5>
        <p class="text-muted">View your fitness stats and improvements.</p>
        <a href="trackprogress.php" class="btn btn-success">View Progress</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow border-0 p-4 text-center">
        <i class="ri-shopping-bag-3-line display-4 text-warning mb-3"></i>
        <h5>Shop Fitness Products</h5>
        <p class="text-muted">Get gym essentials and branded merch.</p>
        <a href="member_shop.php" class="btn btn-warning text-white">Visit Shop</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Make entire promo card clickable (uses the same per-card URL).
document.querySelectorAll('.promo-card').forEach(card => {
  const link = card.getAttribute('data-href');
  if (!link) return;

  card.addEventListener('click', (e) => {
    const isButton = e.target.closest('a,button');
    if (isButton) return;
    window.location.href = link;
  });

  // Hover glow follows mouse
  card.addEventListener('mousemove', (e) => {
    const r = card.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * 100;
    const y = ((e.clientY - r.top) / r.height) * 100;
    card.style.setProperty('--mx', x + '%');
    card.style.setProperty('--my', y + '%');
  });
});
</script>

<!-- ===== Notifications JS (same behavior as profile) ===== -->
<script>
(function(){
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

  bell.addEventListener('click', (e)=>{ e.stopPropagation(); drop.classList.toggle('show'); });
  document.addEventListener('click', (e)=>{ if (!e.target.closest('.notification-container')) drop.classList.remove('show'); });

  tabUnread?.addEventListener('click', ()=>{ tabUnread.classList.add('active'); tabRead?.classList.remove('active'); listUnread.style.display='block'; listRead.style.display='none';});
  tabRead  ?.addEventListener('click', ()=>{ tabRead  .classList.add('active'); tabUnread.classList.remove('active'); listUnread.style.display='none';  listRead.style.display='block';});

  const api = {
    list   : 'Actions/notifs_list_split.php',
    markOne: 'Actions/notifs_mark_read.php',
    markAll: 'Actions/notifs_mark_all.php',
    clear  : 'Actions/notifs_clear_read.php' // delete READ only
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
    if (!confirm('Clear read notifications?')) return;
    fetch(api.clear, {method:'POST'}).then(()=>loadNotifs());
  });

  loadNotifs();
  setInterval(loadNotifs, 30000);
})();
</script>
<!-- ===== end notifications ===== -->

</body>
</html>
