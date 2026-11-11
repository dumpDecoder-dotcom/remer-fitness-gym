<?php
session_start();
include 'Actions/dbconnect.php';

// Check if logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Set page title
$page_title = "Dashboard | Remer Fitness Gym";

// Handle success/error alerts
$success_message = $_SESSION['success'] ?? null;
$error_message   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$filterType = $_GET['filterType'] ?? 'month';
$fromDate   = $_GET['fromDate'] ?? '';
$toDate     = $_GET['toDate'] ?? '';

if ($filterType === 'range' && $fromDate && $toDate) {
    $dateCondition = "date BETWEEN '$fromDate' AND '$toDate'";
} else {
    $dateCondition = "MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title ?? 'Administrator | Remer Fitness Gym') ?></title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
        body { background:#2b2b2b; color:#f8f9fa; }
        .card { background:#fff; border:none; border-radius:1rem; transition:.3s ease; }
        .card:hover { transform:translateY(-4px); box-shadow:0 6px 16px rgba(0,0,0,.15); }

        .navbar { background-color:transparent!important; }
        .navbar .container { border-bottom:1px solid var(--text-clr); }
        .navbar-brand { display:flex; align-items:center; }
        .logo-img { height:auto; max-height:50px; }
        .navbar-nav { margin-left:.5rem; }
        .btn-back { width:40px; height:40px; }
        .chart-card canvas { max-height:260px; }

        .promo-alert { background:linear-gradient(135deg,#fff3cd 0%,#ffe5a0 100%); border-left:5px solid #ff6a00; animation:slideDown .5s ease-out; }
        @keyframes slideDown { from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }
        .promo-item { background:rgba(255,255,255,.6); padding:.5rem .75rem; border-radius:8px; margin-bottom:.5rem; }
        .promo-item:last-child { margin-bottom:0; }

        /* 🔔 NOTIFICATION BELL STYLES */
        .notification-container { position:relative; }
        .notification-bell { background:none; border:none; font-size:24px; cursor:pointer; position:relative; color:#f8f9fa; padding:8px 12px; transition:all .3s ease; }
        .notification-bell:hover { color:#ffc107; transform:scale(1.1); }
        .notification-badge { position:absolute; top:2px; right:4px; background:#dc3545; color:#fff; border-radius:50%; padding:2px 6px; font-size:11px; font-weight:bold; min-width:18px; text-align:center; animation:pulse 2s infinite; }
        @keyframes pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.1);} }

        .notification-dropdown { display:none; position:absolute; top:100%; right:0; width:380px; max-height:520px; background:#fff; border:1px solid #ddd; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.2); z-index:1000; margin-top:10px; overflow:hidden; }
        .notification-dropdown.show { display:block; animation:dropdownSlide .3s ease-out; }
        @keyframes dropdownSlide { from{opacity:0; transform:translateY(-10px);} to{opacity:1; transform:translateY(0);} }

        .notification-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border-bottom:1px solid rgba(255,255,255,.2); }
        .notification-header h5 { margin:0; font-size:16px; font-weight:600; }
        .mark-all-read-btn { background:rgba(255,255,255,.2); border:none; color:#fff; padding:4px 12px; border-radius:6px; font-size:12px; cursor:pointer; transition:all .3s; }
        .mark-all-read-btn:hover { background:rgba(255,255,255,.3); }

        .notification-list { max-height:380px; overflow-y:auto; }
        .notification-list::-webkit-scrollbar { width:6px; }
        .notification-list::-webkit-scrollbar-thumb { background:#ccc; border-radius:3px; }

        .notification-item { padding:14px 20px; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:all .2s; color:#333; display:flex; }
        .notification-item:hover { background:#f8f9fa; }
        .notification-item.unread { background:linear-gradient(to right,#e7f3ff 0%,#f0f8ff 100%); border-left:3px solid #0d6efd; }
        .notification-item .notif-icon { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; margin-right:12px; flex-shrink:0; }
        .notification-item .notif-content { flex:1; }
        .notification-item .notif-title { font-weight:600; margin-bottom:4px; font-size:14px; color:#212529; }
        .notification-item .notif-message { font-size:13px; color:#6c757d; line-height:1.4; }
        .notification-item .notif-time { font-size:11px; color:#999; margin-top:4px; display:flex; align-items:center; gap:4px; }

        .no-notifications { padding:40px 20px; text-align:center; color:#999; }
        .no-notifications i { font-size:48px; color:#ddd; margin-bottom:12px; }
        .loading-notifications { padding:20px; text-align:center; color:#999; }

        /* Notification Type Colors */
        .notif-icon.payment { background:linear-gradient(135deg,#52c234 0%,#061700 100%); color:#fff; }
        .notif-icon.membership { background:linear-gradient(135deg,#fab1a0 0%,#e17055 100%); color:#fff; }
        .notif-icon.class_booking { background:linear-gradient(135deg,#a29bfe 0%,#6c5ce7 100%); color:#fff; }
        .notif-icon.attendance { background:linear-gradient(135deg,#74b9ff 0%,#0984e3 100%); color:#fff; }
        .notif-icon.system { background:linear-gradient(135deg,#ffeaa7 0%,#fdcb6e 100%); color:#2d3436; }
        .notif-icon.default { background:linear-gradient(135deg,#dfe6e9 0%,#b2bec3 100%); color:#2d3436; }

        /* Tabs look */
        .notification-dropdown .btn { background:transparent; color:#333; border-radius:0; }
        .notification-dropdown .btn:focus { box-shadow:none; }

        /* ✅ Pinned message */
        .notification-item.pinned { background:linear-gradient(135deg,#eef5ff 0%,#ffffff 100%); border-left:3px solid #0d6efd; cursor:default; }
        .notification-item.pinned .notif-title { color:#0d6efd; }
  </style>

  <?php
    // ✅ Pinned static message in the bell
    $staticNoticeTitle = "System Notice";
    $staticNoticeMsg   = "User login/logout pop-ups have been removed. You'll still receive payment, membership, class, promo, and other important alerts here.";
  ?>
  <script>
    const STATIC_DASHBOARD_NOTICE = {
      title: <?= json_encode($staticNoticeTitle) ?>,
      message: <?= json_encode($staticNoticeMsg) ?>,
      type: "system",
      created_at: (new Date()).toISOString()
    };
  </script>
</head>
<body class="d-flex flex-column gap-3 pb-3">

<!-- Success/Error Alerts -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
         style="z-index:1055;width:90%;max-width:500px;" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
         style="z-index:1055;width:90%;max-width:500px;" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVBAR -->
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
        <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>

      <!-- 🔔 Bell -->
      <div class="notification-container">
        <button class="notification-bell" id="notificationBell" aria-label="Notifications">
          <i class="ri-notification-3-line"></i>
          <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
        </button>

        <div class="notification-dropdown" id="notificationDropdown">
          <div class="notification-header">
            <h5 class="m-0"><i class="ri-notification-2-fill"></i> Notifications</h5>
            <div class="d-flex gap-2">
              <button class="mark-all-read-btn" id="markAllRead">Mark all</button>
              <button class="mark-all-read-btn" id="clearAll">Clear</button>
            </div>
          </div>

          <!-- Tabs -->
          <div class="d-flex border-bottom">
            <button class="btn w-50 py-2 rounded-0" id="tabUnread" style="border-bottom:2px solid #0d6efd;">
              Unread <span class="badge bg-primary ms-1" id="unreadCountTab">0</span>
            </button>
            <button class="btn w-50 py-2 rounded-0" id="tabRead">
              Read <span class="badge bg-secondary ms-1" id="readCountTab">0</span>
            </button>
          </div>

          <!-- Lists -->
          <div class="notification-list" id="notificationListUnread">
            <div class="loading-notifications">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 mb-0">Loading notifications...</p>
            </div>
          </div>

          <div class="notification-list" id="notificationListRead" style="display:none;">
            <div class="loading-notifications">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 mb-0">Loading notifications...</p>
            </div>
          </div>
        </div>
      </div>
      <!-- /Bell -->
    </div>
  </div>
</nav>

<!-- DASHBOARD CONTENT -->
<div class="container-fluid py-4">

    <!-- 🎁 PROMO EXPIRING SOON ALERT -->
    <?php
    $promoQuery = mysqli_query($conn, "
        SELECT promo_name, discount, end_date
        FROM promo
        WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY end_date ASC
    ");
    
    if ($promoQuery && mysqli_num_rows($promoQuery) > 0) { ?>
        <div class="alert alert-dismissible fade show shadow-sm mb-4 promo-alert" role="alert">
            <h5 class="mb-3 text-dark">
                <i class='ri-fire-fill text-danger'></i> 
                <strong>Promos Expiring Soon!</strong>
                <span class="badge bg-danger ms-2"><?= mysqli_num_rows($promoQuery) ?></span>
            </h5>
            <?php while($p = mysqli_fetch_assoc($promoQuery)) { ?>
                <div class="promo-item d-flex justify-content-between align-items-center">
                    <span class="text-dark">
                        <i class="ri-gift-line text-warning"></i>
                        <strong><?= htmlspecialchars($p['promo_name']) ?></strong> 
                        <span class="badge bg-danger"><?= htmlspecialchars($p['discount']) ?>% OFF</span>
                    </span>
                    <small class="text-muted">
                        <i class="ri-time-line"></i> 
                        Ends: <strong><?= date('M j, Y', strtotime($p['end_date'])) ?></strong>
                    </small>
                </div>
            <?php } ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <!-- HEADER & FILTER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="#" onclick="event.preventDefault(); window.history.back();" 
           class="btn btn-light rounded-circle btn-back shadow"><i class="ri-arrow-go-back-line"></i></a>

        <!-- FILTER FORM -->
        <form method="GET" class="d-flex align-items-center gap-2">
            <select name="filterType" class="form-select form-select-sm w-auto" onchange="toggleDateInputs(this.value)">
                <option value="month" <?= $filterType === 'month' ? 'selected' : '' ?>>This Month</option>
                <option value="range" <?= $filterType === 'range' ? 'selected' : '' ?>>Date Range</option>
            </select>

            <div id="rangeInputs" class="d-flex gap-2" style="<?= $filterType === 'range' ? '' : 'display:none;' ?>">
                <input type="date" name="fromDate" value="<?= htmlspecialchars($fromDate) ?>" class="form-control form-control-sm">
                <input type="date" name="toDate" value="<?= htmlspecialchars($toDate) ?>" class="form-control form-control-sm">
            </div>

            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line"></i> Apply</button>
        </form>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="row g-4">
        <!-- Total Members -->
        <div class="col-md-4">
            <div class="card text-center py-4">
                <h5 class="text-muted">Total Members</h5>
                <h2 class="fw-bold text-primary">
                    <?php
                    $res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM user WHERE role='Member' AND status='Active'");
                    echo $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['count'] : "0";
                    ?>
                </h2>
            </div>
        </div>

        <!-- Attendance Rate -->
        <div class="col-md-4">
            <div class="card text-center py-4">
                <h5 class="text-muted">Attendance Rate</h5>
                <h2 class="fw-bold text-success">
                    <?php
                    $sql = "SELECT ROUND(
                                (SELECT COUNT(DISTINCT a.user_id)
                                 FROM attendance a
                                 JOIN user u ON a.user_id = u.user_id
                                 WHERE u.role = 'Member' AND $dateCondition)
                                /
                                (SELECT COUNT(*) FROM user WHERE role = 'Member') * 100
                            ) AS percentage";
                    $res = mysqli_query($conn, $sql);
                    $row = mysqli_fetch_assoc($res);
                    echo $row['percentage'] ? $row['percentage']."%" : "0%";
                    ?>
                </h2>
            </div>
        </div>

        <!-- Revenue -->
        <div class="col-md-4">
            <div class="card text-center py-4">
                <h5 class="text-muted">Revenue</h5>
                <h2 class="fw-bold text-warning">
                    <?php
                    $sql = "SELECT IFNULL(SUM(total_amount),0) AS revenue FROM transactions WHERE $dateCondition";
                    $res = mysqli_query($conn, $sql);
                    $row = mysqli_fetch_assoc($res);
                    echo "₱ ".number_format($row['revenue'],2);
                    ?>
                </h2>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row g-4 mt-3">
        <!-- Attendance Chart -->
        <div class="col-lg-6">
            <div class="card p-4 chart-card">
                <h5 class="text-center mb-3 text-dark">Attendance Overview</h5>
                <canvas id="attendanceChart"></canvas>
                <?php
                $attendanceLabels = $attendanceData = [];
                $query = "SELECT date, COUNT(DISTINCT user_id) AS total FROM attendance WHERE $dateCondition GROUP BY date ORDER BY date";
                $res = mysqli_query($conn, $query);
                while ($r = mysqli_fetch_assoc($res)) { $attendanceLabels[] = $r['date']; $attendanceData[] = $r['total']; }
                ?>
                <script>
                    const attendanceLabels = <?= json_encode($attendanceLabels) ?>;
                    const attendanceData   = <?= json_encode($attendanceData) ?>;
                </script>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="col-lg-6">
            <div class="card p-4 chart-card">
                <h5 class="text-center mb-3 text-dark">Revenue Trend</h5>
                <canvas id="paymentChart"></canvas>
                <?php
                $paymentLabels = $paymentData = [];
                $query = "SELECT DATE(date) AS d, SUM(total_amount) AS rev FROM transactions WHERE $dateCondition GROUP BY DATE(date) ORDER BY DATE(date)";
                $res = mysqli_query($conn, $query);
                while ($r = mysqli_fetch_assoc($res)) { $paymentLabels[] = $r['d']; $paymentData[] = $r['rev']; }
                ?>
                <script>
                    const paymentLabels = <?= json_encode($paymentLabels) ?>;
                    const paymentData   = <?= json_encode($paymentData) ?>;
                </script>
            </div>
        </div>

        <!-- Member Status Chart -->
        <div class="col-lg-6 mx-auto">
            <div class="card p-4 chart-card">
                <h5 class="text-center mb-3 text-dark">Member Status</h5>
                <canvas id="memberChart"></canvas>
                <?php
                $memberLabels = $memberData = [];
                $res = mysqli_query($conn, "SELECT status, COUNT(*) AS total FROM user WHERE role='Member' GROUP BY status");
                while ($r = mysqli_fetch_assoc($res)) { $memberLabels[] = $r['status']; $memberData[] = $r['total']; }
                ?>
                <script>
                    const memberLabels = <?= json_encode($memberLabels) ?>;
                    const memberData   = <?= json_encode($memberData) ?>;
                </script>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleDateInputs(value) {
    document.getElementById('rangeInputs').style.display = value === 'range' ? 'flex' : 'none';
}

new Chart(document.getElementById('attendanceChart'), {
    type: 'line',
    data: { labels: attendanceLabels, datasets: [{
        label: 'Attendees', data: attendanceData, borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,0.2)', fill: true, tension: 0.3
    }]},
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('paymentChart'), {
    type: 'bar',
    data: { labels: paymentLabels, datasets: [{
        label: 'Revenue (₱)', data: paymentData,
        backgroundColor: 'rgba(40,167,69,0.6)', borderColor: '#28a745', borderWidth: 1
    }]},
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('memberChart'), {
    type: 'doughnut',
    data: { labels: memberLabels, datasets: [{ data: memberData, backgroundColor: ['#198754', '#dc3545', '#6c757d'] }] },
    options: { responsive: true }
});
</script>

<!-- 🔔 NOTIFICATION JAVASCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 30000);

    // Toggle dropdown
    document.getElementById('notificationBell')?.addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('notificationDropdown').classList.toggle('show');
    });
    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-container')) {
            document.getElementById('notificationDropdown')?.classList.remove('show');
        }
    });

    // Tabs
    document.getElementById('tabUnread')?.addEventListener('click', () => {
      document.getElementById('tabUnread').style.borderBottom = '2px solid #0d6efd';
      document.getElementById('tabRead').style.borderBottom   = 'none';
      document.getElementById('notificationListUnread').style.display = 'block';
      document.getElementById('notificationListRead').style.display   = 'none';
    });
    document.getElementById('tabRead')?.addEventListener('click', () => {
      document.getElementById('tabRead').style.borderBottom   = '2px solid #0d6efd';
      document.getElementById('tabUnread').style.borderBottom = 'none';
      document.getElementById('notificationListUnread').style.display = 'none';
      document.getElementById('notificationListRead').style.display   = 'block';
    });

    // Buttons
    document.getElementById('markAllRead')?.addEventListener('click', markAllAsRead);
    document.getElementById('clearAll')?.addEventListener('click', clearAllNotifications);
});

function loadNotifications() {
    fetch('Actions/get_notifications.php?mode=list')
        .then(r => r.json())
        .then(data => {
            if (data.error) { console.error('Error:', data.error); return; }
            const all = Array.isArray(data.notifications) ? data.notifications : [];
            const unread = all.filter(n => Number(n.is_read) === 0);
            const read   = all.filter(n => Number(n.is_read) === 1);

            updateNotificationBadge(unread.length);
            document.getElementById('unreadCountTab').textContent = unread.length;
            document.getElementById('readCountTab').textContent   = read.length;

            renderList('notificationListUnread', unread, /*withPinned*/ true);
            renderList('notificationListRead',   read,   /*withPinned*/ false);
        })
        .catch(err => {
            console.error('Fetch error:', err);
            renderEmpty('notificationListUnread', 'Failed to load notifications');
            renderEmpty('notificationListRead',   'Failed to load notifications');
        });
}

function renderEmpty(targetId, text) {
    const el = document.getElementById(targetId);
    if (!el) return;
    el.innerHTML = `
      <div class="no-notifications">
        <i class="ri-notification-off-line"></i>
        <p>${text}</p>
      </div>`;
}

function renderList(targetId, notifications, withPinned) {
    const list = document.getElementById(targetId);
    if (!list) return;

    let itemsHtml = '';
    if (Array.isArray(notifications) && notifications.length > 0) {
        itemsHtml = notifications.map(notif => `
            <div class="notification-item ${Number(notif.is_read) === 0 ? 'unread' : ''}" 
                 onclick="markAsRead(${notif.id})">
                <div class="notif-icon ${notif.type || 'default'}">
                    <i class="${getNotificationIcon(notif.type)}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">${notif.title || ''}</div>
                    <div class="notif-message">
                        ${(notif.message || '').substring(0, 100)}${(notif.message || '').length > 100 ? '...' : ''}
                    </div>
                    <div class="notif-time"><i class="ri-time-line"></i> ${timeAgo(notif.created_at)}</div>
                </div>
            </div>
        `).join('');
    } else {
        itemsHtml = `
            <div class="no-notifications">
                <i class="ri-notification-off-line"></i>
                <p>No notifications yet</p>
            </div>`;
    }

    const pinned = withPinned ? `
        <div class="notification-item pinned">
            <div class="notif-icon ${STATIC_DASHBOARD_NOTICE.type}">
                <i class="${getNotificationIcon(STATIC_DASHBOARD_NOTICE.type)}"></i>
            </div>
            <div class="notif-content">
                <div class="notif-title">${STATIC_DASHBOARD_NOTICE.title}</div>
                <div class="notif-message">${STATIC_DASHBOARD_NOTICE.message}</div>
                <div class="notif-time"><i class="ri-pushpin-2-fill"></i> pinned</div>
            </div>
        </div>
    ` : '';

    list.innerHTML = pinned + itemsHtml;
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationCount');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

function getNotificationIcon(type) {
    const icons = {
        payment: 'ri-money-dollar-circle-fill',
        membership: 'ri-vip-crown-fill',
        class_booking: 'ri-calendar-check-fill',
        attendance: 'ri-user-follow-fill',
        system: 'ri-notification-3-fill',
        default: 'ri-notification-3-fill'
    };
    return icons[type] || icons['default'];
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now  = new Date();
    const seconds = Math.floor((now - date) / 1000);
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds/60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds/3600) + ' hrs ago';
    if (seconds < 604800) return Math.floor(seconds/86400) + ' days ago';
    return date.toLocaleDateString();
}

function markAsRead(notificationId) {
    fetch('Actions/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(r => r.json())
    .then(data => { if (data.success) loadNotifications(); })
    .catch(err => console.error('Error:', err));
}

function markAllAsRead() {
    fetch('Actions/mark_all_notifications_read.php', { method: 'POST' })
      .then(r => r.json())
      .then(data => { if (data.success) loadNotifications(); })
      .catch(err => console.error('Error:', err));
}

function clearAllNotifications() {
    if (!confirm('Clear all notifications? This cannot be undone.')) return;
    fetch('Actions/clear_notifications.php', { method: 'POST' })
      .then(r => r.json())
      .then(data => { if (data.success) loadNotifications(); })
      .catch(err => console.error('Error:', err));
}
</script>

<!-- Keep if you still use popups for non-login items (it won't show login/logout) -->
<script src="js/notification-popup.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
