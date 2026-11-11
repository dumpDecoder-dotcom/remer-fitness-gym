<?php
/**
 * Notification Bell Component - Multi-User Support
 * Displays notification bell with dropdown in header
 */

// Ensure session is started (safe even if already started elsewhere)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Include database connection FIRST
require_once 'Actions/dbconnect.php';
require_once 'notification.php';

// Hide if no logged-in user
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    return; // Don't show bell if not logged in
}

// Resolve user identity/role
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_type       = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'member';

if (!$current_user_id) {
    error_log("Notification Bell Error: User ID not found in session");
    return;
}

// DB guard
if (!isset($conn) || $conn === null) {
    error_log("Notification Bell Error: Database connection (\$conn) is not defined");
    echo "<!-- Notification Bell Error: Database connection not available -->";
    return;
}

// TYPES TO EXCLUDE (login/logout style events)
$EXCLUDED_TYPES = ['login','logout','user_login','user_logout'];

try {
    $notif = new Notification($conn);

    // Fetch all (unfiltered) so we can exclude locally
    $unread_count_raw      = (int)$notif->getUnreadCount($current_user_id, $user_type);
    $recent_notifications  = $notif->getUserNotifications($current_user_id, 5, true, $user_type);

    // Filter OUT login/logout notifications from the dropdown
    $recent_notifications = array_values(array_filter($recent_notifications ?? [], function($n) use ($EXCLUDED_TYPES) {
        $t = strtolower($n['type'] ?? '');
        return !in_array($t, $EXCLUDED_TYPES, true);
    }));

    // Recompute unread count for BADGE without login/logout types
    // If you have many, you can do a single COUNT(*) query with NOT IN,
    // but this keeps it self-contained.
    $unread_filtered = 0;
    // Option A: If Notification class can fetch unread list, use that.
    // Otherwise, approximate using the recent list + a quick query as fallback.
    // Quick, safe fallback that matches the same targeting logic as Notification class:
    $placeholders = "'" . implode("','", array_map('strtolower', $EXCLUDED_TYPES)) . "'";
    $sql_badge = "
        SELECT COUNT(*) AS c
        FROM notifications
        WHERE user_id = ?
          AND is_read = 0
          AND LOWER(type) NOT IN ($placeholders)
    ";
    if ($stmtBadge = $conn->prepare($sql_badge)) {
        $stmtBadge->bind_param('i', $current_user_id);
        $stmtBadge->execute();
        $resB = $stmtBadge->get_result();
        $rowB = $resB ? $resB->fetch_assoc() : ['c' => 0];
        $unread_filtered = (int)($rowB['c'] ?? 0);
    } else {
        // If prepare fails for any reason, just zero-out login/logout from raw count
        $unread_filtered = max(0, $unread_count_raw - 0); // best-effort fallback
    }

    $unread_count = $unread_filtered;

} catch (Exception $e) {
    error_log("Notification Bell Error: " . $e->getMessage());
    $unread_count = 0;
    $recent_notifications = [];
}
?>

<style>
.notification-bell-btn {
    position: relative;
    background: none;
    border: none;
    color: #333;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    transition: all 0.3s;
}
.notification-bell-btn:hover { color: #667eea; transform: scale(1.1); }
.notification-bell-btn:hover .ri-notification-3-line { animation: ring 0.5s ease-in-out; }
@keyframes ring {
    0%, 100% { transform: rotate(0deg); }
    10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
    20%, 40%, 60%, 80% { transform: rotate(10deg); }
}
.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
    color: white;
    border-radius: 50%;
    padding: 0.2rem 0.5rem;
    font-size: 0.7rem;
    font-weight: bold;
    min-width: 20px;
    height: 20px;
    display: flex; align-items: center; justify-content: center;
    animation: pulse 2s infinite;
    box-shadow: 0 2px 5px rgba(255, 68, 68, 0.5);
}
@keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15); box-shadow:0 4px 10px rgba(255,68,68,.8)} }
.notification-dropdown { width:380px; max-height:500px; overflow-y:auto; box-shadow:0 10px 30px rgba(0,0,0,.2); border-radius:10px; border:none; }
.notification-dropdown::-webkit-scrollbar { width:6px; }
.notification-dropdown::-webkit-scrollbar-track { background:#f1f1f1; border-radius:3px; }
.notification-dropdown::-webkit-scrollbar-thumb { background:#888; border-radius:3px; }
.notification-dropdown::-webkit-scrollbar-thumb:hover { background:#555; }
.notification-dropdown-item { padding:.75rem 1rem; border-bottom:1px solid #eee; transition:.2s; text-decoration:none; display:block; color:inherit; }
.notification-dropdown-item:hover { background:#f8f9fa; transform:translateX(3px); text-decoration:none; }
.notification-dropdown-item.unread { background:linear-gradient(90deg,#e7f3ff 0%,#ffffff 100%); border-left:3px solid #667eea; }
.notification-icon-wrapper { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.user-type-badge { font-size:.65rem; padding:.15rem .5rem; border-radius:10px; font-weight:bold; text-transform:uppercase; }
.badge-admin{background:#dc3545;color:#fff}.badge-staff{background:#0dcaf0;color:#fff}.badge-coach{background:#6610f2;color:#fff}.badge-cashier{background:#198754;color:#fff}.badge-member{background:#6c757d;color:#fff}
.dropdown-header-custom { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:1rem 1.25rem; border-radius:10px 10px 0 0; border-bottom:none; }
.dropdown-header-custom a { color:#fff; text-decoration:none; transition:.3s; }
.dropdown-header-custom a:hover { text-decoration:underline; }
.empty-notifications { padding:2rem 1.5rem; text-align:center; }
.view-all-btn { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; transition:.3s; }
.view-all-btn:hover { background:linear-gradient(135deg,#764ba2 0%,#667eea 100%); color:#fff; transform:translateY(-2px); box-shadow:0 4px 12px rgba(102,126,234,.4); }
.notification-time { font-size:.7rem; color:#6c757d; }
.new-badge { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; font-size:.65rem; padding:.15rem .5rem; border-radius:10px; font-weight:bold; }
</style>

<div class="dropdown notification-bell">
    <button class="notification-bell-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
        <i class="ri-notification-3-line"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notification-badge">
                <?= $unread_count > 99 ? '99+' : $unread_count ?>
            </span>
        <?php endif; ?>
    </button>
    
    <ul class="dropdown-menu dropdown-menu-end notification-dropdown p-0">
        <li class="dropdown-header dropdown-header-custom d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="ri-notification-4-line"></i> Notifications</strong>
                <span class="user-type-badge badge-<?= strtolower($user_type) ?> ms-2">
                    <?= ucfirst($user_type) ?>
                </span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-light text-dark ms-1"><?= $unread_count ?></span>
                <?php endif; ?>
            </div>
            <a href="notifications.php" class="small fw-bold">
                View All <i class="ri-arrow-right-line"></i>
            </a>
        </li>
        
        <?php if (empty($recent_notifications)): ?>
            <li class="empty-notifications">
                <i class="ri-notification-off-line fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-1 fw-bold">No new notifications</p>
                <small class="text-muted">You're all caught up! 🎉</small>
            </li>
        <?php else: ?>
            <?php foreach ($recent_notifications as $notification): ?>
                <?php
                    // (Extra guard — skip if somehow a login/logout slipped through)
                    if (in_array(strtolower($notification['type'] ?? ''), $EXCLUDED_TYPES, true)) { continue; }

                    $icons = [
                        'membership_expiry' => ['icon' => 'ri-calendar-close-line',  'bg' => '#ffe0e0', 'color' => '#dc3545'],
                        'payment_due'       => ['icon' => 'ri-money-dollar-circle-line','bg' => '#fff3cd', 'color' => '#ffc107'],
                        'class_booking'     => ['icon' => 'ri-checkbox-circle-line',  'bg' => '#d4edda', 'color' => '#28a745'],
                        'class_reminder'    => ['icon' => 'ri-alarm-line',            'bg' => '#d1ecf1', 'color' => '#17a2b8'],
                        'promo'             => ['icon' => 'ri-gift-line',              'bg' => '#f8d7da', 'color' => '#e83e8c'],
                        'staff_welcome'     => ['icon' => 'ri-user-smile-line',        'bg' => '#d4edda', 'color' => '#28a745'],
                        'class_schedule'    => ['icon' => 'ri-calendar-event-line',    'bg' => '#d1ecf1', 'color' => '#17a2b8'],
                        'payment_success'   => ['icon' => 'ri-check-double-line',      'bg' => '#d4edda', 'color' => '#28a745'],
                        'class_cancelled'   => ['icon' => 'ri-close-circle-line',      'bg' => '#ffe0e0', 'color' => '#dc3545'],
                        'announcement'      => ['icon' => 'ri-megaphone-line',         'bg' => '#e7e3ff', 'color' => '#6610f2'],
                        'system'            => ['icon' => 'ri-settings-3-line',        'bg' => '#e9ecef', 'color' => '#6c757d']
                    ];
                    $notif_style = $icons[$notification['type']] ?? ['icon' => 'ri-notification-3-line', 'bg' => '#e9ecef', 'color' => '#6c757d'];
                ?>
                <li>
                    <a href="notifications.php?id=<?= (int)$notification['id'] ?>" class="notification-dropdown-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="notification-icon-wrapper" style="background: <?= $notif_style['bg'] ?>;">
                                <i class="<?= $notif_style['icon'] ?>" style="color: <?= $notif_style['color'] ?>; font-size: 1.3rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong style="font-size: 0.9rem;">
                                        <?= htmlspecialchars(mb_strimwidth($notification['title'] ?? '', 0, 40, '...', 'UTF-8')) ?>
                                    </strong>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="new-badge">New</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted d-block mb-2">
                                    <?= htmlspecialchars(mb_strimwidth($notification['message'] ?? '', 0, 60, '...', 'UTF-8')) ?>
                                </small>
                                <div class="notification-time d-flex align-items-center gap-1">
                                    <i class="ri-time-line"></i> 
                                    <span><?= htmlspecialchars(date('M j, g:i A', strtotime($notification['created_at']))) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider m-0"></li>
            <li class="p-2">
                <a href="notifications.php" class="btn view-all-btn w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="ri-notification-4-line"></i>
                    <span>View All Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-light text-dark"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>
