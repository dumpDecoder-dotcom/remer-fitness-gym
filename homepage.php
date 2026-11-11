<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Remer Fitness Gym</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>

    <style>
      /* ===== Global + anchor behavior ===== */
      html { scroll-behavior: smooth; scroll-padding-top: 90px; }
      .section { padding-top: 4rem; padding-bottom: 4rem; }

      /* ===== PROMOS ===== */
      .promo-section {
        background: linear-gradient(135deg, #667eea 0%, #000000ff 100%);
        position: relative;
        overflow: hidden;
      }
      .promo-card {
        background: white; border-radius: 15px; padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        transition: transform .3s ease, box-shadow .3s ease; height: 100%;
      }
      .promo-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.3); }
      .promo-badge {
        background: linear-gradient(135deg,#f093fb 0%,#f5576c 100%);
        color:#fff; padding:.3rem 1rem; border-radius:20px; font-weight:700; font-size:.85rem; display:inline-block; margin-bottom:.5rem;
      }
      .promo-badge.hot{ background:linear-gradient(135deg,#fa709a 0%,#fee140 100%); }
      .promo-badge.expires{ background:linear-gradient(135deg,#ff9a56 0%,#ff6a00 100%); }
      .promo-discount{ font-size:2.5rem; font-weight:700; color:#667eea; line-height:1; }
      .promo-original-price{ text-decoration:line-through; color:#999; font-size:1rem; }
      .promo-new-price{ color:#f5576c; font-size:1.5rem; font-weight:700; }
      .promo-timer{ background:#fff3cd; border-left:4px solid #ff6a00; padding:.5rem 1rem; border-radius:5px; margin-top:1rem; }
      .search-promo{ max-width:600px; margin:0 auto 2rem; }
      .search-promo input{
        border-radius:25px; padding:.8rem 1.5rem; border:2px solid rgba(255,255,255,0.3); background:rgba(255,255,255,0.9);
      }
      .search-promo input:focus{ border-color:#fff; box-shadow:0 0 20px rgba(255,255,255,0.5); outline:none; }
      .search-promo select{
        border-radius:25px; background:rgba(255,255,255,0.9); border:2px solid rgba(255,255,255,0.3); padding:.5rem 1.5rem;
      }
      .search-promo select:focus{ border-color:#fff; box-shadow:0 0 20px rgba(255,255,255,0.5); outline:none; }

      /* ===== Seam under PROMOS ===== */
      .after-promos{
        background:#0a0d18; border-top-left-radius:24px; border-top-right-radius:24px;
        margin-top:-24px; position:relative; z-index:2; box-shadow:0 -8px 24px rgba(0,0,0,0.18);
      }

      /* ===== TRAINING (dark blue) ===== */
      #training{
        --grid-gap:1.25rem;
        position:relative;
        padding-top:4rem; padding-bottom:4rem;
        background-color:#0a0d18;
        background-image:
          radial-gradient(60rem 60rem at 110% -10%, rgba(37,99,235,0.18), transparent),
          radial-gradient(42rem 42rem at -20% 110%, rgba(2,132,199,0.14), transparent);
        background-repeat:no-repeat;
      }
      .train-filter .btn-filter{
        border:1px solid rgba(148,163,184,0.18);
        color:#dbe7ff; background:rgba(255,255,255,0.03);
        padding:.5rem 1rem; border-radius:999px; font-weight:600; backdrop-filter:blur(6px);
      }
      .train-filter .btn-filter.active{
        border-color:#60a5fa; color:#081226; background:linear-gradient(135deg,#3b82f6,#60a5fa);
      }
      .train-card{
        background:#0e1324; border:1px solid rgba(96,165,250,0.18); border-radius:18px; overflow:hidden;
        box-shadow:0 10px 30px rgba(0,0,0,0.35); transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
      }
      .train-card:hover{ transform:translateY(-6px); box-shadow:0 18px 40px rgba(0,0,0,0.45); border-color:rgba(96,165,250,0.38); }
      .train-media{ background:#0b1020; position:relative; aspect-ratio:16/10; overflow:hidden; }
      .train-media img{ width:100%; height:100%; object-fit:cover; transition:transform .35s ease, opacity .35s ease; }
      .train-card:hover .train-media img{ transform:scale(1.06); opacity:.95; }
      .badge-corner{
        position:absolute; top:.75rem; left:.75rem; z-index:2; padding:.35rem .7rem; font-weight:800; font-size:.78rem;
        color:#0b2447; border-radius:999px; background:linear-gradient(135deg,#a1c4fd,#c2e9fb); box-shadow:0 8px 18px rgba(161,196,253,0.35);
      }
      .train-title{ color:#e6efff; font-weight:800; font-size:1.15rem; line-height:1.2; margin:.25rem 0 .2rem; }
      .train-meta{ display:flex; gap:.75rem; flex-wrap:wrap; font-size:.9rem; color:#9fb3d9; }
      .train-chips{ display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.35rem; margin-bottom:.15rem; }
      .chip{ font-size:.78rem; font-weight:700; letter-spacing:.02em; color:#d1e4ff; border:1px solid rgba(96,165,250,0.35); background:rgba(96,165,250,0.10); padding:.22rem .55rem; border-radius:999px; }
      .train-foot{ margin-top:auto; display:flex; justify-content:space-between; align-items:center; gap:.75rem; }
      .price .now{ color:#9ec1ff; font-weight:800; font-size:1.05rem; }
      .price .sub{ color:#7e8ba7; font-size:.8rem; }
      .btn-book{ border:0; font-weight:800; border-radius:12px; background:linear-gradient(135deg,#6fb1fc,#4364f7); color:#0b1227; padding:.55rem .9rem; box-shadow:0 12px 24px rgba(79,129,255,0.28); }
      .btn-book:hover{ filter:brightness(1.05); transform:translateY(-1px); }

      .train-grid { display:grid; grid-template-columns:repeat(12,1fr); gap:var(--grid-gap); }
      @media (max-width:575.98px){ .train-grid{ grid-template-columns:repeat(4,1fr);} }
      @media (min-width:576px){ .train-grid{ grid-template-columns:repeat(6,1fr);} }
      @media (min-width:992px){ .train-grid{ grid-template-columns:repeat(12,1fr);} }
      .train-card{ grid-column:span 6; }
      @media (min-width:992px){ .train-card{ grid-column:span 4; } }
      .training-item.hidden{ display:none !important; }

      /* === GoGym-like footer (no quick links/resources/tagline) === */
      .gg2-footer{ background:#151515; color:#cfcfcf; font-size:14px; }
      .gg2-topline{ height:1px; background:#2b2b2b; opacity:.9; }
      .gg2-heading{ color:#ffffff; letter-spacing:.05em; font-weight:700; }
      .gg2-list li{ padding:.15rem 0; }
      .gg2-list i{ color:#9be870; margin-right:.5rem; }
      .gg2-social a{ font-size:28px; color:#cfcfcf; line-height:1; transition:transform .2s ease, color .2s ease; }
      .gg2-social a:hover{ transform:translateY(-2px); color:#ffffff; }
      .gg2-divider{ height:1px; background:#2b2b2b; opacity:.7; }
      .gg2-copy{ color:#b9b9b9; font-size:13px; }
      .gg2-copy a{ color:#b9b9b9; text-decoration:none; }
      .gg2-copy a:hover{ color:#fff; }
      @media (max-width:576px){
        .gg2-footer{ font-size:13px; }
        .gg2-social a{ font-size:26px; }
      }
    </style>
  </head>

  <body>
    <!-- NAV -->
    <nav class="navbar navbar-expand-lg">
      <div class="container-fluid px-md-4 px-lg-5 py-1">
        <a class="navbar-brand" href="index.php">
          <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
          <ul class="navbar-nav ms-2">
            <li class="nav-item"><a class="nav-link active" href="index.php">HOME</a></li>
            <li class="nav-item"><a class="nav-link" href="findthegym.php">FIND THE GYM</a></li>
            <li class="nav-item"><a class="nav-link" href="#training">TRAINING</a></li>
            <li class="nav-item"><a class="nav-link" href="#promos">PROMOS</a></li>
          </ul>

          <div class="d-flex align-items-center gap-3">
            <?php 
              $is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['id']);
              if ($is_logged_in): 
                $user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'User';
                $user_type = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'member';
            ?>
              <?php if (file_exists('notification_bell.php')) include 'notification_bell.php'; ?>
              <div class="dropdown">
                <button class="btn btn-link text-dark p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="ri-user-line fs-4"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li class="dropdown-header">
                    <strong><?= htmlspecialchars($user_name) ?></strong><br>
                    <small class="text-muted"><?= ucfirst($user_type) ?></small>
                  </li>
                  <li><hr class="dropdown-divider"></li>

                  <?php if ($user_type === 'admin'): ?>
                    <li><a class="dropdown-item" href="admin/dashboard.php"><i class="ri-dashboard-line"></i> Admin Dashboard</a></li>
                  <?php elseif ($user_type === 'staff' || $user_type === 'coach'): ?>
                    <li><a class="dropdown-item" href="staff/dashboard.php"><i class="ri-dashboard-line"></i> Staff Dashboard</a></li>
                  <?php elseif ($user_type === 'cashier'): ?>
                    <li><a class="dropdown-item" href="cashier/dashboard.php"><i class="ri-dashboard-line"></i> Cashier Dashboard</a></li>
                  <?php endif; ?>

                  <li><a class="dropdown-item" href="profile.php"><i class="ri-user-line"></i> My Profile</a></li>
                  <li><a class="dropdown-item" href="notifications.php"><i class="ri-notification-3-line"></i> Notifications</a></li>

                  <?php if ($user_type === 'member'): ?>
                    <li><a class="dropdown-item" href="my-classes.php"><i class="ri-calendar-line"></i> My Classes</a></li>
                    <li><a class="dropdown-item" href="membership.php"><i class="ri-vip-crown-line"></i> Membership</a></li>
                  <?php endif; ?>

                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="logout.php"><i class="ri-logout-box-line"></i> Logout</a></li>
                </ul>
              </div>
            <?php else: ?>
              <a href="login.php" class="btn join-now-btn">JOIN NOW</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>

    <!-- HERO -->
    <main class="flex-grow-1 bg-def">
      <div class="container-fluid py-large bg-main position-relative d-flex gap-4 flex-wrap">
        <div class="container d-flex gap-4 flex-wrap">
          <div class="d-flex flex-column">
            <div class="d-flex">
              <h1>
                <span class="text-black shadow-white f-gym">GYM</span>
                <span class="text-clr shadow-blur-black f-new">NEW</span>
              </h1>
            </div>
            <h1 class="text-clr shadow-blur-black f-class">CLASS</h1>
          </div>
          <div class="d-flex flex-column justify-content-end">
            <a href="login.php" class="btn btn-light rounded-5 fw-bold px-4 py-2 shadow mt-3 text-nowrap">TRY US FOR FREE</a>
          </div>
        </div>
        <img src="Assets/Graphics/Deadlift Image.png" alt="Gym Image" loading="lazy" class="position-absolute parallelogram-img">
      </div>

      <!-- PROMOS -->
      <section id="promos" class="promo-section py-5 section">
        <div class="container">
          <div class="text-center mb-4">
            <h1 class="display-3 fw-bold text-white mb-2"><i class="ri-fire-fill"></i> HOT DEALS & PROMOS</h1>
            <p class="text-white fs-5">Limited time offers! Don't miss out!</p>
          </div>

          <!-- Search & Sort -->
          <div class="search-promo">
            <div class="input-group mb-3">
              <input type="text" class="form-control" id="promoSearch" placeholder="🔍 Search promos, products, classes...">
            </div>
            <div class="d-flex justify-content-end">
              <select class="form-select w-auto" id="promoSort">
                <option value="featured">Sort by: Featured</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="discount">Highest Discount</option>
                <option value="expiry">Expiring Soon</option>
              </select>
            </div>
          </div>

          <div class="row g-4" id="promosContainer">
            <!-- Promo 1 -->
            <div class="col-md-6 col-lg-4 promo-item" data-price="900" data-discount="50" data-expiry="5">
              <div class="promo-card">
                <span class="promo-badge hot">🔥 HOT DEAL</span>
                <h3 class="fw-bold mt-2">New Member Special</h3>
                <div class="promo-discount">50% OFF</div>
                <p class="text-muted mb-2">First Month Membership</p>
                <div>
                  <span class="promo-original-price">₱1800</span>
                  <span class="promo-new-price">₱900</span>
                </div>
                <div class="promo-timer">
                  <small><i class="ri-time-line"></i> <strong>Expires in 5 days</strong></small>
                </div>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Claim Now</a>
              </div>
            </div>

            <!-- Promo 2 (conditional) -->
            <?php
              $productStatus = "Available"; // Example; replace with DB value
              if ($productStatus === "Available"):
            ?>
            <div class="col-md-6 col-lg-4 promo-item" data-price="98" data-discount="20" data-expiry="3">
              <div class="promo-card">
                <span class="promo-badge expires">⏰ EXPIRES SOON</span>
                <h3 class="fw-bold mt-2">Banana Whey Protein</h3>
                <div class="promo-discount">20% OFF</div>
                <p class="text-muted mb-2">Premium Quality Supplement</p>
                <div>
                  <span class="promo-original-price">₱123</span>
                  <span class="promo-new-price">₱98</span>
                </div>
                <div class="promo-timer">
                  <small><i class="ri-time-line"></i> <strong>Expires in 3 days</strong></small>
                </div>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Buy Now</a>
              </div>
            </div>
            <?php endif; ?>

            <!-- Promo 3 -->
            <div class="col-md-6 col-lg-4 promo-item" data-price="4000" data-discount="0" data-expiry="31">
              <div class="promo-card">
                <span class="promo-badge">🎁 SPECIAL OFFER</span>
                <h3 class="fw-bold mt-2">Yoga Class Package</h3>
                <div class="promo-discount">BUY 10 GET 2 FREE</div>
                <p class="text-muted mb-2">Perfect for beginners & pros</p>
                <div>
                  <span class="promo-new-price">₱4000</span>
                  <small class="text-muted d-block">for 12 sessions</small>
                </div>
                <div class="promo-timer">
                  <small><i class="ri-time-line"></i> <strong>Valid until Oct 31</strong></small>
                </div>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Book Now</a>
              </div>
            </div>

            <!-- Promo 4 -->
            <div class="col-md-6 col-lg-4 promo-item" data-price="100" data-discount="17" data-expiry="999">
              <div class="promo-card">
                <span class="promo-badge">🎓 STUDENT PROMO</span>
                <h3 class="fw-bold mt-2">Student Walk-In Rate</h3>
                <div class="promo-discount">₱100 ONLY</div>
                <p class="text-muted mb-2">Special rate for students</p>
                <div>
                  <span class="promo-original-price">₱120</span>
                  <span class="promo-new-price">₱100</span>
                </div>
                <p class="mt-3"><small class="text-muted">Valid Student ID Required</small></p>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Visit Us</a>
              </div>
            </div>

            <!-- Promo 5 -->
            <div class="col-md-6 col-lg-4 promo-item" data-price="500" data-discount="0" data-expiry="999">
              <div class="promo-card">
                <span class="promo-badge">👥 REFERRAL BONUS</span>
                <h3 class="fw-bold mt-2">Bring a Friend</h3>
                <div class="promo-discount">₱500 OFF</div>
                <p class="text-muted mb-2">For you & your friend!</p>
                <div><span class="promo-new-price">Each gets ₱500 discount</span></div>
                <p class="mt-3"><small class="text-muted">On next membership renewal</small></p>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Refer Now</a>
              </div>
            </div>

            <!-- Promo 6 -->
            <div class="col-md-6 col-lg-4 promo-item" data-price="3500" data-discount="30" data-expiry="10">
              <div class="promo-card">
                <span class="promo-badge hot">🔥 BUNDLE DEAL</span>
                <h3 class="fw-bold mt-2">Fitness Starter Pack</h3>
                <div class="promo-discount">30% OFF</div>
                <p class="text-muted mb-2">Membership + 5 Classes + Protein</p>
                <div>
                  <span class="promo-original-price">₱5000</span>
                  <span class="promo-new-price">₱3500</span>
                </div>
                <div class="promo-timer">
                  <small><i class="ri-time-line"></i> <strong>Limited slots available</strong></small>
                </div>
                <a href="login.php" class="btn btn-primary w-100 mt-3">Get Bundle</a>
              </div>
            </div>
          </div>

          <!-- No Results -->
          <div id="noResultsMessage" class="text-center py-5" style="display:none;">
            <i class="ri-search-line display-1 text-white-50"></i>
            <h3 class="text-white mt-3">No promos found</h3>
            <p class="text-white-50">Try adjusting your search terms</p>
          </div>

          <!-- View All Promos -->
          <div class="text-center mt-5">
            <a href="all-promos.php" class="btn btn-light btn-lg px-5 rounded-pill shadow">
              View All Promos <i class="ri-arrow-right-line"></i>
            </a>
          </div>
        </div>
      </section>

      <!-- TRAINING -->
      <section id="training" class="after-promos">
        <div class="container">
          <div class="text-center mb-4">
            <h2 class="display-5 fw-bold mb-2 text-white"><i class="ri-dumbbell-fill"></i> Training</h2>
            <p class="text-secondary mb-0">Curated classes led by certified coaches. Pick a style, pick a time—let’s move.</p>
          </div>

          <div class="train-filter mb-4">
            <button class="btn-filter active" data-cat="all">All</button>
            <button class="btn-filter" data-cat="cardio">Cardio</button>
            <button class="btn-filter" data-cat="strength">Strength</button>
            <button class="btn-filter" data-cat="mind">Mind & Mobility</button>
          </div>

          <div class="train-grid">
            <!-- Cards -->
            <article class="training-item train-card" data-cat="cardio">
              <div class="train-media">
                <span class="badge-corner">Zumba</span>
                <img src="Assets/Graphics/2.jpg" alt="Zumba Class">
              </div>
              <div class="train-body">
                <div class="train-title">Zumba Energy (45 min)</div>
                <div class="train-meta">
                  <span><i class="ri-user-voice-line"></i> Coach Mia</span>
                  <span><i class="ri-bar-chart-line"></i> Beginner</span>
                  <span><i class="ri-calendar-line"></i> Starts Nov 6</span>
                </div>
                <div class="train-chips">
                  <span class="chip">Cardio</span><span class="chip">Dance</span><span class="chip">Fat Burn</span>
                </div>
                <div class="train-foot">
                  <div class="price"><span class="now">₱250</span><span class="sub">per session</span></div>
                  <a href="login.php" class="btn btn-book">Book</a>
                </div>
              </div>
            </article>

            <article class="training-item train-card" data-cat="strength">
              <div class="train-media">
                <span class="badge-corner">Boxing</span>
                <img src="Assets/Graphics/boxing.jpg" alt="Boxing Class">
              </div>
              <div class="train-body">
                <div class="train-title">Boxing Fundamentals (55 min)</div>
                <div class="train-meta">
                  <span><i class="ri-user-voice-line"></i> Coach Ken</span>
                  <span><i class="ri-bar-chart-line"></i> Intermediate</span>
                  <span><i class="ri-calendar-line"></i> Starts Nov 5</span>
                </div>
                <div class="train-chips">
                  <span class="chip">Strength</span><span class="chip">Technique</span><span class="chip">Conditioning</span>
                </div>
                <div class="train-foot">
                  <div class="price"><span class="now">₱320</span><span class="sub">per session</span></div>
                  <a href="login.php" class="btn btn-book">Book</a>
                </div>
              </div>
            </article>

            <article class="training-item train-card" data-cat="mind">
              <div class="train-media">
                <span class="badge-corner">Yoga</span>
                <img src="Assets/Graphics/yoga.jpg" alt="Yoga Class">
              </div>
              <div class="train-body">
                <div class="train-title">Vinyasa Flow (55 min)</div>
                <div class="train-meta">
                  <span><i class="ri-user-voice-line"></i> Coach Aya</span>
                  <span><i class="ri-bar-chart-line"></i> Advanced</span>
                  <span><i class="ri-calendar-line"></i> Starts Nov 7</span>
                </div>
                <div class="train-chips">
                  <span class="chip">Mobility</span><span class="chip">Balance</span><span class="chip">Breathwork</span>
                </div>
                <div class="train-foot">
                  <div class="price"><span class="now">₱300</span><span class="sub">per session</span></div>
                  <a href="login.php" class="btn btn-book">Book</a>
                </div>
              </div>
            </article>

            <article class="training-item train-card" data-cat="strength">
              <div class="train-media">
                <span class="badge-corner">Strength</span>
                <img src="Assets/Graphics/Curls Image.png" alt="Strength 101">
              </div>
              <div class="train-body">
                <div class="train-title">Strength 101 (60 min)</div>
                <div class="train-meta">
                  <span><i class="ri-user-voice-line"></i> Coach Rex</span>
                  <span><i class="ri-bar-chart-line"></i> Beginner</span>
                  <span><i class="ri-calendar-line"></i> Starts Nov 8</span>
                </div>
                <div class="train-chips">
                  <span class="chip">Compound</span><span class="chip">Form</span><span class="chip">Progressive</span>
                </div>
                <div class="train-foot">
                  <div class="price"><span class="now">₱350</span><span class="sub">per session</span></div>
                  <a href="login.php" class="btn btn-book">Book</a>
                </div>
              </div>
            </article>

            <article class="training-item train-card" data-cat="cardio">
              <div class="train-media">
                <span class="badge-corner">HIIT</span>
                <img src="Assets/Graphics/Deadlift Image.png" alt="HIIT Burn">
              </div>
              <div class="train-body">
                <div class="train-title">HIIT Burn (40 min)</div>
                <div class="train-meta">
                  <span><i class="ri-user-voice-line"></i> Coach Lian</span>
                  <span><i class="ri-bar-chart-line"></i> All Levels</span>
                  <span><i class="ri-calendar-line"></i> Starts Nov 4</span>
                </div>
                <div class="train-chips">
                  <span class="chip">Intervals</span><span class="chip">Endurance</span><span class="chip">Sweat</span>
                </div>
                <div class="train-foot">
                  <div class="price"><span class="now">₱280</span><span class="sub">per session</span></div>
                  <a href="login.php" class="btn btn-book">Book</a>
                </div>
              </div>
            </article>

            <article class="training-item train-card" data-cat="mind">
              <div class="train-media">
                <span class="badge-corner">Mobility</span>
                <img src="Assets/Graphics/yoga.jpg" alt="Mobility Reset">
              </div>
              <div class="train-body">
                <div class="train-title">Mobility Reset (35 min)</div>
                <div class="train-meta">
                  <span><i class="ri-user-voice-line"></i> Coach Elle</span>
                  <span><i class="ri-bar-chart-line"></i> All Levels</span>
                  <span><i class="ri-calendar-line"></i> Starts Nov 9</span>
                </div>
                <div class="train-chips">
                  <span class="chip">Stretch</span><span class="chip">Recovery</span><span class="chip">Flow</span>
                </div>
                <div class="train-foot">
                  <div class="price"><span class="now">₱260</span><span class="sub">per session</span></div>
                  <a href="login.php" class="btn btn-book">Book</a>
                </div>
              </div>
            </article>
          </div>

          <div class="text-center mt-4">
            <a href="trainings.php" class="btn btn-outline-light btn-lg rounded-pill">
              View <i class="ri-arrow-right-line"></i>
            </a>
          </div>
        </div>
      </section>

      <!-- WELCOME / MEMBERSHIPS / FAQ -->
      <div class="container-fluid py-med px-0">
        <div class="container-fluid p-5 position-relative">
          <div class="container position-relative z-3">
            <h1 class="d-flex flex-column lh-1 bg-def p-2">
              <span class="f-place text-center">A PLACE WHERE EVERYONE FEELS</span>
              <span class="f-wel shadow-blur-blue text-center">WELCOME</span>
            </h1>
          </div>
          <div class="azure"></div>
        </div>
      </div>

      <div class="bg-black container-fluid px-4 py-large position-relative z-1">
        <img src="Assets/Graphics/Curls Image.png" alt="Curl Image" loading="lazy" class="curl-img">
        <div class="container d-flex flex-column gap-5">
          <div class="d-flex flex-column px-5">
            <h1 class="text-blue display-1 fw-bold lh-1">Memberships</h1>
            <h4 class="text-clr fs-1 lh-1">What we offer?</h4>
          </div>
          <div class="d-flex justify-content-start align-items-center flex-wrap column-gap-4 row-gap-3 px-4">
            <div class="mcard gradient d-flex flex-column gap-2 justify-content-between">
              <h4 class="fw-bold text-clr">Member Rates:</h4>
              <div class="d-flex flex-column gap-0">
                <p class="text-clr fw-bold fs-6 m-0 p-0 lh-1"><span class="mem-bold shadow-yellow">₱1800</span>/Mo. Membership</p>
                <p class="m-0 p-0 lh-1"><small class="text-clr fw-bold">Full access to all facilities!</small></p>
              </div>
              <div class="d-flex flex-column gap-2 mt-2">
                <h4 class="fw-bold text-clr">Walk-In Rates:</h4>
                <div class="d-flex flex-column gap-2 ps-3">
                  <p class="text-clr fw-bold fs-6 m-0 p-0 lh-1"><span class="mem-bold shadow-yellow">₱120</span><span> Regular</span></p>
                  <p class="text-clr fw-bold fs-6 m-0 p-0 lh-1"><span class="mem-bold shadow-yellow">₱100</span><span> Student</span></p>
                </div>
              </div>
              <div class="d-flex flex-nowrap justify-content-end align-items-center gap-3">
                <span class="fs-4 text-clr"><i class="ri-arrow-right-double-fill"></i></span>
                <a href="login.php" class="btn btn-join">JOIN NOW</a>
              </div>
            </div>
            <div class="mcard white d-flex flex-column gap-3">
              <h4 class="fw-bold text-black">BENEFITS:</h4>
              <div class="d-flex flex-column gap-2">
                <p class="text-black fw-bold m-0 p-0 lh-sm"><i class="ri-check-line"></i> Access to all equipment</p>
                <p class="text-black fw-bold m-0 p-0 lh-sm"><i class="ri-check-line"></i> Free fitness consultation</p>
                <p class="text-black fw-bold m-0 p-0 lh-sm"><i class="ri-check-line"></i> Locker room access</p>
                <p class="text-black fw-bold m-0 p-0 lh-sm"><i class="ri-check-line"></i> Special discounts on products</p>
                <p class="text-black fw-bold m-0 p-0 lh-sm"><i class="ri-check-line"></i> Priority class booking</p>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-center align-items-center">
            <img width="150" height="150" src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" loading="lazy" class="img-fluid logo-lg">
          </div>
        </div>
      </div>

      <div class="container bg-def d-flex flex-column gap-5 py-large">
        <div>
          <h1>
            <span class="text-black display-1 fw-bold lh-1"><i class="ri-arrow-right-double-line"></i></span>
            <span class="text-black display-1 fw-bold lh-1">MEMBERSHIP FAQ</span>
          </h1>
        </div>
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                What's the difference between Members and Classic memberships?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
              <div class="accordion-body faq-answer">
                Classic memberships include access to the gym facilities and standard equipment during staffed hours only. Members, on the other hand, enjoy 24/7 access, priority class booking, and exclusive perks such as discounts on merchandise and access to premium equipment zones.
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                How old do I have to be to get a RemerFitness Gym membership?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
              <div class="accordion-body faq-answer">
                You can join Remer Fitness Gym at 13+ with a parent/guardian's permission. Ages 13-14 must work out with a guardian. 
                Ages 15-17 (or 15-18 where 19 is the age of majority) need a signed waiver. 
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                Does Remer Fitness Gym Fitness offer classes or personal training?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
              <div class="accordion-body faq-answer">
                Yes! Remer Fitness Gym Fitness offers a wide variety of group fitness classes (e.g., Zumba, HIIT, Strength Training, Yoga) led by certified instructors. 
                We also have personal training sessions available, customized to help you reach your specific fitness goals.
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                What are my club's hours?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
              <div class="accordion-body faq-answer">
                Most Remer Fitness Gym Fitness locations are open 24/7 for members.
                Staffed hours may vary by location, typically from 8 AM to 10 PM, Monday to Saturday.
                Check with your local club for specific staffed schedules and holiday hours.
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- FOOTER (GoGym style, trimmed) -->
    <footer class="gg2-footer">
      <div class="container-xxl py-4">
        <div class="gg2-topline"></div>

        <div class="row gy-4 align-items-start py-4">
          <!-- Left: Logo only (no tagline) -->
          <div class="col-12 col-lg-4 d-flex align-items-center gap-3">
            <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Fitness Gym" width="56" height="56" class="rounded-circle bg-dark p-1">
            <h5 class="m-0 text-white fw-semibold">REMER FITNESS GYM</h5>
          </div>

          <!-- Right: Get in touch + Social -->
          <div class="col-12 col-lg-8">
            <div class="row gy-4">
              <div class="col-12 col-md-7">
                <h6 class="gg2-heading">GET IN TOUCH</h6>
                <ul class="list-unstyled m-0 gg2-list">
                  <li><i class="ri-map-pin-2-fill"></i> 5048 P Burgos, Poblacion, Makati, Metro Manila, Philippines</li>
                  <li><i class="ri-phone-fill"></i> +63 915 819 6967</li>
                  <li><i class="ri-mail-fill"></i> fitnessremer@gmail.com</li>
                </ul>
              </div>
              <div class="col-12 col-md-5">
                <h6 class="gg2-heading">FOLLOW US</h6>
                <div class="d-flex align-items-center gap-3 gg2-social">
                  <a href="#" aria-label="Facebook"><i class="ri-facebook-circle-fill"></i></a>
                  <a href="#" aria-label="Instagram"><i class="ri-instagram-fill"></i></a>
                  <a href="#" aria-label="TikTok"><i class="ri-tiktok-fill"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="gg2-divider my-3"></div>
        <p class="text-center m-0 gg2-copy">
          © 2025 Remer Fitness Gym. All rights reserved. <a href="#"></a>
        </p>
      </div>
    </footer>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script>
      // PROMOS: search & sort
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('promoSearch');
        const sortSelect = document.getElementById('promoSort');
        const promoContainer = document.getElementById('promosContainer');
        const promoCards = document.querySelectorAll('.promo-item');
        const noResultsMsg = document.getElementById('noResultsMessage');

        if (searchInput) {
          searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            promoCards.forEach(card => {
              const text = card.textContent.toLowerCase();
              if (text.includes(searchTerm)) { card.style.display = ''; visibleCount++; }
              else { card.style.display = 'none'; }
            });

            if (visibleCount === 0) { noResultsMsg.style.display = 'block'; promoContainer.style.display = 'none'; }
            else { noResultsMsg.style.display = 'none'; promoContainer.style.display = ''; }
          });
        }

        if (sortSelect) {
          sortSelect.addEventListener('change', function(e) {
            const sortValue = e.target.value;
            const promoArray = Array.from(promoCards);

            promoArray.sort((a, b) => {
              const priceA = parseInt(a.getAttribute('data-price')) || 0;
              const priceB = parseInt(b.getAttribute('data-price')) || 0;
              const discountA = parseInt(a.getAttribute('data-discount')) || 0;
              const discountB = parseInt(b.getAttribute('data-discount')) || 0;
              const expiryA = parseInt(a.getAttribute('data-expiry')) || 999;
              const expiryB = parseInt(b.getAttribute('data-expiry')) || 999;

              switch (sortValue) {
                case 'price-low': return priceA - priceB;
                case 'price-high': return priceB - priceA;
                case 'discount': return discountB - discountA;
                case 'expiry': return expiryA - expiryB;
                default: return 0;
              }
            });

            promoArray.forEach(card => promoContainer.appendChild(card));
          });
        }
      });

      // TRAINING: category filtering
      document.addEventListener('DOMContentLoaded', function () {
        const filterBtns = document.querySelectorAll('.btn-filter');
        const items = document.querySelectorAll('.training-item');

        function applyFilter(cat) {
          items.forEach(it => {
            const match = (cat === 'all') || (it.getAttribute('data-cat') === cat);
            it.classList.toggle('hidden', !match);
          });
        }

        filterBtns.forEach(btn => {
          btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilter(btn.dataset.cat);
          });
        });

        applyFilter('all');
      });
    </script>
  </body>
</html>
