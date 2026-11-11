<?php 
    session_start();
    include 'Actions/dbconnect.php';

    // Check if logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Member') {
        header("Location: login.php");
        exit;
    }

    if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        unset($_SESSION['success']); 
    endif;

    if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        unset($_SESSION['error']); 
    endif; 

    // Get the number of items in the cart for the current user
$user_id = $_SESSION['user_id'];
$cart_count = 0;

$cart_query = mysqli_query($conn, "SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = '$user_id'");
if ($cart_query && mysqli_num_rows($cart_query) > 0) {
    $cart_data = mysqli_fetch_assoc($cart_query);
    $cart_count = $cart_data['total_items'] ?? 0;
}

?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shop | Remer Fitness Gym</title>
        <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
        <style>
            .notification-bell {
                position: relative;
                cursor: pointer;
                font-size: 24px;
                color: var(--text-clr);
            }
            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: red;
                color: white;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 12px;
                font-weight: bold;
                display: none;
            }
            .product-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                border-radius: 15px;
            }
            .product-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            }
            .btn-success {
                border-radius: 8px;
                font-weight: 500;
            }
            .btn-primary {
                border-radius: 10px;
            }
            .badge {
                box-shadow: 0 0 5px rgba(0,0,0,0.2);
            }

        </style>
    </head>
    <body class="d-flex flex-column gap-3 bg-base pb-3">
        <!-- Navbar (unchanged) -->
        <nav class="navbar navbar-expand-lg bg-transparent">
            <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
                <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
                    <ul class="navbar-nav ms-2">
                        <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link active" href="member_shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
                        <li class="nav-item"><a class="nav-link" href="trackprogress.php">Track Progress</a></li>
                        <li class="nav-item"><a class="nav-link" href="member_payment.php">Payment</a></li>
                        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
                    </ul>
                    
                    <!-- Notification Bell -->
                    <div class="notification-bell me-3" onclick="toggleNotifications()">
                        <i class="ri-notification-3-line"></i>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Shop Container -->
        <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
            <div class="w-100">
                <a href="" onclick="event.preventDefault(); window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;">
                    <i class="ri-arrow-go-back-line"></i>
                </a>
            </div>

            <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
                <div class="rounded-5 bg-white p-4 d-flex flex-column gap-4 w-100" style="height: 690px;">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
                        <h2 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                            <i class="ri-shopping-bag-3-line text-primary"></i> Available Products
                        </h2>
                        <a href="view-cart.php" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm px-3 py-2 fw-semibold position-relative">
                        <i class="ri-shopping-cart-2-line fs-5"></i> View Cart
                        <span id="cartBadge"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 0.75rem; display: <?= $cart_count > 0 ? 'inline' : 'none' ?>;">
                            <?= $cart_count ?>
                        </span>
                        </a>


                    </div>

                    <div class="flex-grow-1 overflow-auto" style="max-height: 560px;">
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php
                            $sql = "
                                SELECT * FROM product 
                                WHERE status = 'Available' AND quantity > 0
                                ORDER BY 
                                    CASE 
                                        WHEN quantity <= 5 THEN 1 
                                        ELSE 2 
                                    END,
                                    quantity ASC
                            ";
                            $result = mysqli_query($conn, $sql);

                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <div class="col">
                                        <div class="card h-100 border-0 shadow-sm product-card overflow-hidden">
                                            <?php if(!empty($row['image'])): ?>
                                                <div class="position-relative" style="height: 180px; overflow:hidden;">
                                                    <img src="<?= htmlspecialchars($row['image']) ?>" 
                                                         alt="<?= htmlspecialchars($row['product_name']) ?>" 
                                                         class="card-img-top" 
                                                         style="object-fit: cover; height:100%; width:100%;">
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-secondary d-flex justify-content-center align-items-center" style="height: 180px; color: white;">
                                                    No Image
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body d-flex flex-column p-3">
                                                <h5 class="card-title fw-bold mb-1 text-dark"><?= htmlspecialchars($row['product_name']) ?></h5>
                                                <p class="text-muted small mb-2 text-truncate" title="<?= htmlspecialchars($row['description']) ?>">
                                                    <?= htmlspecialchars($row['description']) ?>
                                                </p>
                                                <p class="mb-1"><small class="text-secondary"><?= htmlspecialchars($row['category'] . " | " . $row['subcategory']) ?></small></p>
                                                <p class="fw-bold mb-1 fs-6 text-primary">₱<?= number_format($row['price'],2) ?></p>
                                                <p class="mb-3">
                                                    <?php
                                                    if ($row['quantity'] <= 5) {
                                                        echo "<span class='badge bg-danger'>Low Stock ({$row['quantity']} pcs)</span>";
                                                    } else {
                                                        echo "<span class='text-success'>{$row['quantity']} pcs available</span>";
                                                    }
                                                    ?>
                                                </p>
                                                <form action="Actions/action_addtocart.php" method="POST" class="mt-auto d-flex gap-2">
                                                    <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                                                    <input type="number" name="quantity" class="form-control form-control-sm text-center" 
                                                           min="1" max="<?= $row['quantity'] ?>" value="1" required style="width: 70px;">
                                                    <button type="submit" class="btn btn-success d-flex align-items-center justify-content-center gap-1 px-3">
                                                        <i class="ri-add-line"></i> Add
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<div class='col'><p class='text-center w-100 text-muted mt-4'>No products available right now.</p></div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>  
            </div>
        </div>

        <!-- Notification Modal -->
        <div id="notificationModal"></div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
        <script src="script.js"></script>
        <script>
            // Check for new notifications every 5 seconds
            setInterval(checkNotifications, 5000);
            document.addEventListener('DOMContentLoaded', checkNotifications);

            function checkNotifications() {
                fetch('Actions/get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_notifications > 0) {
                            showNotificationPopup(data.latest_notification);
                        }
                        updateNotificationBadge(data.total_unread);
                    })
                    .catch(error => console.error('Error:', error));
            }

            function showNotificationPopup(notif) {
                const existing = document.getElementById('notificationModal');
                if (existing) existing.remove();
                
                const modal = document.createElement('div');
                modal.id = 'notificationModal';
                modal.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.5);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 10000;
                    ">
                        <div style="
                            background: white;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                            max-width: 500px;
                            width: 90%;
                            animation: slideDown 0.3s ease;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                <h3 style="margin: 0; color: #333; font-size: 20px;">
                                    ${notif.title}
                                </h3>
                                <button onclick="closeNotificationModal()" style="
                                    background: none;
                                    border: none;
                                    font-size: 24px;
                                    cursor: pointer;
                                    color: #999;
                                ">&times;</button>
                            </div>
                            
                            <div style="
                                background: #f8f9fa;
                                padding: 20px;
                                border-radius: 8px;
                                margin-bottom: 20px;
                                border-left: 4px solid #007bff;
                            ">
                                <p style="margin: 0; color: #555; line-height: 1.6; font-size: 16px;">
                                    ${notif.message}
                                </p>
                            </div>
                            
                            <div style="text-align: right;">
                                <button onclick="markAsReadAndClose(${notif.id})" style="
                                    background: #007bff;
                                    color: white;
                                    border: none;
                                    padding: 10px 25px;
                                    border-radius: 5px;
                                    cursor: pointer;
                                    font-size: 16px;
                                ">
                                    OK, Got it!
                                </button>
                            </div>
                            
                            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px; text-align: right;">
                                ${new Date(notif.created_at).toLocaleString()}
                            </p>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            function closeNotificationModal() {
                const modal = document.getElementById('notificationModal');
                if (modal) modal.remove();
            }

            function markAsReadAndClose(notifId) {
                fetch('Actions/mark_notification_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notifId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeNotificationModal();
                        checkNotifications();
                    }
                });
            }

            function updateNotificationBadge(count) {
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'block' : 'none';
                }
            }

            function toggleNotifications() {
                alert('Notification list coming soon!');
            }

            document.addEventListener("DOMContentLoaded", () => {
    // Run once when page loads
    updateCartBadge();

    // Refresh every 3 seconds (you can change this)
    setInterval(updateCartBadge, 3000);
});

function updateCartBadge() {
    fetch('Actions/get_cart_count.php')  // ← make sure this file exists here
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('cartBadge');
            if (!badge) return;
            
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => console.error('Error updating cart badge:', error));
}

        </script>

    </body>
</html>
