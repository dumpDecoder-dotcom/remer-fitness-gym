<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Remer Fitness Gym — Training</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>

    <style>
      html { scroll-behavior:smooth; scroll-padding-top:90px; }
      body { background:#0a0d18; color:#e6efff; }

      /* Page header */
      .page-hero{
        background:
          radial-gradient(48rem 48rem at 110% -10%, rgba(37,99,235,.18), transparent),
          radial-gradient(36rem 36rem at -20% 110%, rgba(2,132,199,.14), transparent);
        padding:3.5rem 0 2rem;
        border-bottom:1px solid rgba(148,163,184,0.12);
      }
      .breadcrumb-lite a{ color:#9fb3d9; text-decoration:none; }
      .breadcrumb-lite a:hover{ color:#cfe1ff; }

      /* ===== TRAINING grid (dark blue theme) ===== */
      .section{ padding-top:2.5rem; padding-bottom:2.5rem; }
      #training{
        --grid-gap:1.25rem;
        position:relative; padding-top:2rem; padding-bottom:2rem;
        background:
          radial-gradient(60rem 60rem at 110% -10%, rgba(37,99,235,0.18), transparent),
          radial-gradient(42rem 42rem at -20% 110%, rgba(2,132,199,0.14), transparent);
      }
      .train-filter{ display:flex; flex-wrap:wrap; gap:.5rem; justify-content:center; }
      .train-filter .btn-filter{
        border:1px solid rgba(148,163,184,0.18);
        color:#dbe7ff; background:rgba(255,255,255,0.03);
        padding:.5rem 1rem; border-radius:999px; font-weight:600; backdrop-filter:blur(6px);
      }
      .train-filter .btn-filter.active{
        border-color:#60a5fa; color:#081226; background:linear-gradient(135deg,#3b82f6,#60a5fa);
      }

      .train-grid{ display:grid; grid-template-columns:repeat(12,1fr); gap:var(--grid-gap); }
      @media (max-width:575.98px){ .train-grid{ grid-template-columns:repeat(4,1fr);} }
      @media (min-width:576px){ .train-grid{ grid-template-columns:repeat(6,1fr);} }
      @media (min-width:992px){ .train-grid{ grid-template-columns:repeat(12,1fr);} }

      .train-card{
        grid-column:span 6; /* 2-up small/medium */
        background:#0e1324; border:1px solid rgba(96,165,250,0.18);
        border-radius:18px; overflow:hidden; display:flex; flex-direction:column; height:100%;
        box-shadow:0 10px 30px rgba(0,0,0,0.35);
        transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
      }
      @media (min-width:992px){ .train-card{ grid-column:span 4; } } /* 3-up large */
      .train-card:hover{ transform:translateY(-6px); box-shadow:0 18px 40px rgba(0,0,0,0.45); border-color:rgba(96,165,250,0.38); }

      .train-media{ position:relative; aspect-ratio:16/10; overflow:hidden; background:#0b1020; }
      .train-media img{ width:100%; height:100%; object-fit:cover; transition:transform .35s ease, opacity .35s ease; }
      .train-card:hover .train-media img{ transform:scale(1.06); opacity:.95; }

      .badge-corner{
        position:absolute; top:.75rem; left:.75rem; z-index:2;
        padding:.35rem .7rem; font-weight:800; font-size:.78rem; letter-spacing:.02em;
        color:#081226; border-radius:999px;
        background:linear-gradient(135deg,#60a5fa,#93c5fd);
        box-shadow:0 8px 18px rgba(96,165,250,0.35);
      }

      .train-body{ padding:1rem 1rem 1.1rem; display:flex; flex-direction:column; gap:.35rem; flex:1; }
      .train-title{ color:#e6efff; font-weight:800; font-size:1.15rem; line-height:1.2; margin:.25rem 0 .2rem; }
      .train-meta{ display:flex; gap:.75rem; flex-wrap:wrap; font-size:.9rem; color:#9fb3d9; }
      .train-meta i{ opacity:.8; margin-right:.25rem; }

      .train-chips{ display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.35rem; margin-bottom:.15rem; }
      .chip{
        font-size:.78rem; font-weight:700; letter-spacing:.02em; color:#d1e4ff;
        border:1px solid rgba(96,165,250,0.35);
        background:rgba(96,165,250,0.10); padding:.22rem .55rem; border-radius:999px;
      }

      .train-foot{ margin-top:auto; display:flex; justify-content:space-between; align-items:center; gap:.75rem; }
      .price{ display:flex; flex-direction:column; line-height:1; }
      .price .now{ color:#9ec1ff; font-weight:800; font-size:1.05rem; }
      .price .sub{ color:#7e8ba7; font-size:.8rem; }
      .btn-book{
        border:0; font-weight:800; border-radius:12px;
        background:linear-gradient(135deg,#3b82f6,#2563eb);
        color:#061024; padding:.55rem .9rem;
        box-shadow:0 12px 24px rgba(37,99,235,0.32);
      }
      .btn-book:hover{ filter:brightness(1.05); transform:translateY(-1px); }

      .training-item.hidden{ display:none !important; }
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
            <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
            <li class="nav-item"><a class="nav-link" href="findthegym.php">FIND THE GYM</a></li>
            <li class="nav-item"><a class="nav-link active" href="trainings.php">TRAINING</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php#promos">PROMOS</a></li>
          </ul>

          <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['user_id']) || isset($_SESSION['id'])): ?>
              <a href="profile.php" class="btn btn-outline-light">My Profile</a>
            <?php else: ?>
              <a href="login.php" class="btn join-now-btn">JOIN NOW</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>

    <!-- PAGE HERO -->
    <header class="page-hero">
      <div class="container">
        <nav class="breadcrumb-lite mb-2">
          <a href="index.php"><i class="ri-home-5-line"></i> Home</a>
          <span class="text-secondary"> / Training</span>
        </nav>
        <h1 class="display-5 fw-bold mb-1">Training</h1>
        <p class="text-secondary m-0">Browse classes by category. Log in to reserve a slot.</p>
      </div>
    </header>

    <!-- TRAINING GRID -->
    <section id="training" class="section">
      <div class="container">

        <!-- Filters -->
        <div class="train-filter mb-4">
          <button class="btn-filter active" data-cat="all">All</button>
          <button class="btn-filter" data-cat="cardio">Cardio</button>
          <button class="btn-filter" data-cat="strength">Strength</button>
          <button class="btn-filter" data-cat="mind">Mind & Mobility</button>
        </div>

        <!-- Grid -->
        <div class="train-grid">
          <!-- Zumba (no coach name) -->
          <article class="training-item train-card" data-cat="cardio">
            <div class="train-media">
              <span class="badge-corner">Zumba</span>
              <img src="Assets/Graphics/2.jpg" alt="Zumba Class">
            </div>
            <div class="train-body">
              <div class="train-title">Zumba Energy (45 min)</div>
              <div class="train-meta">
                <span><i class="ri-bar-chart-line"></i> Beginner</span>
                <span><i class="ri-calendar-line"></i> Tue & Thu</span>
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

          <!-- Boxing (no coach name) -->
          <article class="training-item train-card" data-cat="strength">
            <div class="train-media">
              <span class="badge-corner">Boxing</span>
              <img src="Assets/Graphics/boxing.jpg" alt="Boxing Class">
            </div>
            <div class="train-body">
              <div class="train-title">Boxing Fundamentals (55 min)</div>
              <div class="train-meta">
                <span><i class="ri-bar-chart-line"></i> Intermediate</span>
                <span><i class="ri-calendar-line"></i> Mon / Wed / Sat</span>
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

          <!-- Yoga (no coach name) -->
          <article class="training-item train-card" data-cat="mind">
            <div class="train-media">
              <span class="badge-corner">Yoga</span>
              <img src="Assets/Graphics/yoga.jpg" alt="Yoga Class">
            </div>
            <div class="train-body">
              <div class="train-title">Vinyasa Flow (55 min)</div>
              <div class="train-meta">
                <span><i class="ri-bar-chart-line"></i> Advanced</span>
                <span><i class="ri-calendar-line"></i> Fri & Sun</span>
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

          <!-- Strength 101 (no coach name) -->
          <article class="training-item train-card" data-cat="strength">
            <div class="train-media">
              <span class="badge-corner">Strength</span>
              <img src="Assets/Graphics/Curls Image.png" alt="Strength 101">
            </div>
            <div class="train-body">
              <div class="train-title">Strength 101 (60 min)</div>
              <div class="train-meta">
                <span><i class="ri-bar-chart-line"></i> Beginner</span>
                <span><i class="ri-calendar-line"></i> Tue / Thu</span>
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

          <!-- HIIT (no coach name) -->
          <article class="training-item train-card" data-cat="cardio">
            <div class="train-media">
              <span class="badge-corner">HIIT</span>
              <img src="Assets/Graphics/Deadlift Image.png" alt="HIIT Burn">
            </div>
            <div class="train-body">
              <div class="train-title">HIIT Burn (40 min)</div>
              <div class="train-meta">
                <span><i class="ri-bar-chart-line"></i> All Levels</span>
                <span><i class="ri-calendar-line"></i> Daily</span>
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

          <!-- Mobility (no coach name) -->
          <article class="training-item train-card" data-cat="mind">
            <div class="train-media">
              <span class="badge-corner">Mobility</span>
              <img src="Assets/Graphics/yoga.jpg" alt="Mobility Reset">
            </div>
            <div class="train-body">
              <div class="train-title">Mobility Reset (35 min)</div>
              <div class="train-meta">
                <span><i class="ri-bar-chart-line"></i> All Levels</span>
                <span><i class="ri-calendar-line"></i> Mon / Wed / Fri</span>
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
      </div>
    </section>

    <!-- FOOTER (simple) -->
    <footer class="py-3">
      <div class="container text-center text-secondary">© 2025 Remer Fitness Gym</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
      // Category filtering
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
