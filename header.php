<?php
// includes/header.php
if (!isset($_SESSION)) {
    session_start();
}

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Remer Fitness Gym' ?></title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        body { background: #212529; color: #f8f9fa; }
        .navbar a { color: #f8f9fa !important; }
        
        /* 🔔 NOTIFICATION BELL STYLES */
        .notification-container { position: relative; }
        .notification-bell {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            position: relative;
            color: #f8f9fa;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }
        .notification-bell:hover { color: #ffc107; transform: scale(1.1); }
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 4px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 380px;
            max-height: 500px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            z-index: 1000;
            margin-top: 10px;
            overflow: hidden;
        }
        .notification-dropdown.show {
            display: block;
            animation: dropdownSlide 0.3s ease-out;
        }
        @keyframes dropdownSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .notification-header h5 { margin: 0; font-size: 16px; font-weight: 600; }
        .mark-all-read-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .mark-all-read-btn:hover { background: rgba(255,255,255,0.3); }
        .notification-list { max-height: 400px; overflow-y: auto; }
        .notification-list::-webkit-scrollbar { width: 6px; }
        .notification-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
        .notification-item {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s;
            color: #333;
        }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread {
            background: linear-gradient(to right, #e7f3ff 0%, #f0f8ff 100%);
            border-left: 3px solid #0d6efd;
        }
        .notification-item .notif-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .notification-item .notif-content { flex: 1; }
        .notification-item .notif-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 14px;
            color: #212529;
        }
        .notification-item .notif-message {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.4;
        }
        .notification-item .notif-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .no-notifications { padding: 40px 20px; text-align: center; color: #999; }
        .no-notifications i { font-size: 48px; color: #ddd; margin-bottom: 12px; }
        .loading-notifications { padding: 20px; text-align: center; color: #999; }

        /* Notification Type Colors */
        .notif-icon.promo { background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%); color: #d63031; }
        .notif-icon.membership_expiry { background: linear-gradient(135deg, #fab1a0 0%, #e17055 100%); color: #d63031; }
        .notif-icon.class_schedule { background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); color: #2d3436; }
        .notif-icon.staff_welcome { background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: #2d3436; }
        .notif-icon.default { background: linear-gradient(135deg, #dfe6e9 0%, #b2bec3 100%); color: #2d3436; }
    </style>
</head>
<body>

<!-- NAVBAR WITH NOTIFICATION BELL -->
<nav class="navbar navbar-expand-lg bg-dark border-bottom border-secondary">
    <div class="container px-4 py-2">
        <a class="navbar-brand text-light" href="dashboard.php">
            <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" width="45" class="me-2">
            Remer Fitness Gym
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-3 align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'class-list.php' ? 'active' : '' ?>" href="class-list.php">Classes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'staff-list.php' ? 'active' : '' ?>" href="staff-list.php">Staffs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'member-list.php' ? 'active' : '' ?>" href="member-list.php">Members</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'progress.php' ? 'active' : '' ?>" href="progress.php">Progress</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'product-list.php' ? 'active' : '' ?>" href="product-list.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'product-list.php' ? 'active' : '' ?>" href="payment-verification.php">Payments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'my-profile.php' ? 'active' : '' ?>" href="my-profile.php">Profile</a>
                </li>
                
                <!-- 🔔 NOTIFICATION BELL -->
                <li class="nav-item">
                    <div class="notification-container">
                        <button class="notification-bell" id="notificationBell" aria-label="Notifications">
                            <i class="ri-notification-3-line"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </button>
                        
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h5><i class="ri-notification-2-fill"></i> Notifications</h5>
                                <button class="mark-all-read-btn" id="markAllRead">Mark all read</button>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="loading-notifications">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <p class="mt-2 mb-0">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
// 🔔 NOTIFICATION JAVASCRIPT
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 30000);
    
    document.getElementById('notificationBell')?.addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('notificationDropdown').classList.toggle('show');
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-container')) {
            document.getElementById('notificationDropdown')?.classList.remove('show');
        }
    });
    
    document.getElementById('markAllRead')?.addEventListener('click', markAllAsRead);
});

function loadNotifications() {
    fetch('ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) return;
            updateNotificationBadge(data.unread_count);
            displayNotifications(data.notifications);
        })
        .catch(error => console.error('Error:', error));
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationCount');
    if (badge) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

function getNotificationIcon(type) {
    const icons = {
        'promo': 'ri-gift-fill',
        'membership_expiry': 'ri-time-fill',
        'class_schedule': 'ri-calendar-event-fill',
        'staff_welcome': 'ri-user-add-fill',
        'default': 'ri-notification-3-fill'
    };
    return icons[type] || icons['default'];
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hrs ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    return date.toLocaleDateString();
}

function displayNotifications(notifications) {
    const list = document.getElementById('notificationList');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="no-notifications"><i class="ri-notification-off-line"></i><p>No notifications</p></div>';
        return;
    }
    
    list.innerHTML = notifications.map(n => `
        <div class="notification-item d-flex ${n.is_read == 0 ? 'unread' : ''}" onclick="markAsRead(${n.id})">
            <div class="notif-icon ${n.type}">
                <i class="${getNotificationIcon(n.type)}"></i>
            </div>
            <div class="notif-content">
                <div class="notif-title">${n.title}</div>
                <div class="notif-message">${n.message}</div>
                <div class="notif-time"><i class="ri-time-line"></i>${timeAgo(n.created_at)}</div>
            </div>
        </div>
    `).join('');
}

function markAsRead(id) {
    fetch('ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    }).then(() => loadNotifications());
}

function markAllAsRead() {
    fetch('ajax/mark_all_notifications_read.php', {
        method: 'POST'
    }).then(() => loadNotifications());
}
</script>