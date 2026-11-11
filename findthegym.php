<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Remer Fitness Gym</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Dark (black + deep blue) overrides -->
    <style>
      :root{
        --night:#0a0d18;           /* near-black */
        --navy:#0e1324;            /* dark blue-black card */
        --edge:rgba(96,165,250,.18);
        --edge-strong:rgba(96,165,250,.36);
        --ink:#e6efff;             /* main text */
        --muted:#9fb3d9;           /* secondary text */
        --blue1:#3b82f6;           /* primary accent */
        --blue2:#2563eb;           /* primary accent dark */
      }

      html{ scroll-behavior:smooth; }
      body{
        background: var(--night);
        color: var(--ink);
      }

      /* NAVBAR (dark gradient, light links) */
      .navbar{
        background:
          radial-gradient(40rem 40rem at 120% -20%, rgba(37,99,235,.18), transparent),
          radial-gradient(36rem 36rem at -20% 120%, rgba(2,132,199,.12), transparent),
          linear-gradient(180deg, #0b0f1a, #0a0d18);
        border-bottom: 1px solid rgba(148,163,184,.12);
      }
      .navbar .nav-link{
        color:#dbe7ff !important;
        font-weight:600;
        letter-spacing:.02em;
        opacity:.9;
      }
      .navbar .nav-link:hover{ opacity:1; }
      .navbar .nav-link.active{
        color:#ffffff !important;
        position:relative;
      }
      .navbar .nav-link.active::after{
        content:""; position:absolute; left:.6rem; right:.6rem; bottom:-6px; height:2px;
        background: linear-gradient(90deg, var(--blue1), var(--blue2));
        border-radius:2px;
      }

      .join-now-btn{
        background: linear-gradient(135deg, var(--blue1), var(--blue2));
        color:#061024; font-weight:800; border:0; border-radius:12px;
        box-shadow:0 10px 22px rgba(37,99,235,.28);
        text-transform:uppercase;
      }
      .join-now-btn:hover{ filter:brightness(1.05); transform:translateY(-1px); }

      /* MAIN */
      main.bg-blue{ background: var(--night) !important; }
      .rem-bg{
        position:absolute; inset:auto; right:0; bottom:0; width:560px; max-width:40%;
        opacity:.06; filter:grayscale(1) contrast(110%);
        pointer-events:none; user-select:none;
      }

      /* Map card */
      .map{
        width:min(1100px, 92vw);
        height:60vh;
        border:0;
        border-radius:18px;
        outline:1px solid var(--edge);
        box-shadow:
          0 18px 40px rgba(0,0,0,.45),
          0 0 0 6px rgba(59,130,246,.06);
        background:#0b1020;
      }

      /* Info row */
      .text-clr{ color: var(--ink) !important; }
      .switch{
        display:flex; gap:1rem; align-items:center;
        color: var(--muted);
        background: var(--navy);
        border:1px solid var(--edge);
        border-radius:14px;
        padding:.6rem .9rem;
        box-shadow: 0 10px 26px rgba(0,0,0,.35);
      }
      .switch i{ color:#9ec1ff; margin-right:.25rem; }

      /* Footer */
      footer{
        background: linear-gradient(180deg, #0b0f1a, #070a12);
        border-top:1px solid rgba(148,163,184,.12);
      }
      footer .text-blue{ color:#9ec1ff !important; }
      footer .container-xxl p{ color: var(--muted); }

      /* Utility */
      .px-md-4 .logo-img{ filter:none; }
    </style>
  </head>

  <body>
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
            <li class="nav-item"><a class="nav-link active" href="findthegym.php">FIND THE GYM</a></li>
            <li class="nav-item"><a class="nav-link" href="trainings.php">TRAINING</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php#promos">PROMOS</a></li>
          </ul>

          <div class="d-flex align-items-center">
            <a href="login.php" class="btn join-now-btn">JOIN NOW</a>
          </div>
        </div>
      </div>
    </nav>

    <main class="flex-grow-1 bg-blue position-relative z-1">
      <img src="Assets/Graphics/Remer Logo BG.png" alt="Remer Logo" class="rem-bg">
      <div class="d-flex flex-column align-items-center justify-content-center gap-2 py-3">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4585.14891663899!2d120.86543635954337!3d14.901658512377887!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x339654ec12ee4555%3A0x595c742438e1c5ca!2sRobinsons%20Townville%20Pulilan!5e0!3m2!1sen!2sph!4v1753438995730!5m2!1sen!2sph"
          class="map" allowfullscreen="" referrerpolicy="no-referrer-when-downgrade">
        </iframe>

        <p class="text-clr fw-bold m-0">
          <i class="ri-map-pin-line"></i>
          <span>109 RMM Bldg 3flr Cut-cot nat. Rd, Pulilan, Philippines</span>
        </p>

        <div class="switch">
          <a href="login.php" class="btn btn-light rounded-5 fw-bold px-4 py-2 shadow text-nowrap m-0">
            JOIN NOW
          </a>
          <div class="px-3"><i class="ri-phone-line"></i>0922 490 0951</div>
        </div>
      </div>
    </main>

    <footer class="px-md-4 px-lg-5 py-2">
      <div class="container d-flex align-items-center gap-4 py-4" style="border-bottom:1px solid rgba(148,163,184,.12);">
        <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" loading="lazy" width="120" height="120">
        <h1 class="fst-italic text-blue fw-bold display-3">BEAST MODE</h1>
      </div>
      <div class="container-xxl py-2">
        <p class="mb-0 text-center">© 2025 Remer Fitness Gym. All rights reserved.</p>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="script.js"></script>
  </body>
</html>
