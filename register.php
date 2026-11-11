<?php 
session_start();
include 'Actions/dbconnect.php';
?>
<?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
       style="z-index:1055;width:90%;max-width:500px;" role="alert">
    <?= htmlspecialchars($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
       style="z-index:1055;width:90%;max-width:500px;" role="alert">
    <?= htmlspecialchars($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Remer Fitness Gym — Sign Up</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css">

  <style>
    /* ===== Palette ===== */
    :root{
      --c-white:#FAFAFA;
      --c-ice:#C7EEFF;
      --c-pri:#0077C0;
      --c-base:#1D242B;

      --edge:rgba(199,238,255,.45);
      --glass:rgba(255,255,255,.06);
      --ink-1:#E8F6FF;
      --ink-2:#BFDDED;
      --ring:rgba(0,119,192,.28);
    }

    /* Base + BG */
    html, body { height: 100dvh; }
    body{
      margin:0; color:var(--c-white);
      background:
        radial-gradient(60rem 60rem at 120% -10%, rgba(0,119,192,.16), transparent),
        radial-gradient(42rem 42rem at -20% 110%, rgba(199,238,255,.14), transparent),
        linear-gradient(135deg, #131920 0%, #1A2230 100%);
      overflow: hidden; /* page doesn't scroll; inner panels handle it */
    }

    /* ambient lights */
    .orb{position:fixed; filter:blur(34px); opacity:.35; z-index:0; pointer-events:none;}
    .orb.o1{width:360px;height:360px;background:#0077C0; top:-80px; right:-60px; animation:float 11s ease-in-out infinite;}
    .orb.o2{width:300px;height:300px;background:#C7EEFF; bottom:-60px; left:-40px; animation:float 13s ease-in-out infinite reverse;}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(16px)}}

    .wrap{
      position:relative; z-index:1; min-height:100dvh; display:grid; place-items:center;
      padding:8px; /* tighter outer padding so the frame can grow */
    }

    /* Frame fills screen neatly */
    .frame{
      width: min(1200px, 100vw - 8px);
      height: calc(100dvh - 8px);
      display:flex; flex-direction:row !important; /* always landscape */
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.04));
      border: 1px solid var(--edge);
      border-radius: 18px;
      overflow: hidden; /* clip inner scroll areas */
      backdrop-filter: blur(10px);
      box-shadow: 0 18px 48px rgba(0,0,0,.45);
    }

    /* LEFT: brand & perks */
    .left{
      position:relative; padding:28px; /* tighter padding */
      width:50%; max-width:50%;
      border-right:1px solid var(--edge);
      background:
        linear-gradient(180deg, rgba(199,238,255,.08), rgba(255,255,255,0)),
        radial-gradient(60rem 60rem at -20% 110%, rgba(0,119,192,.18), transparent);
      display:flex; flex-direction:column; gap:12px; overflow:auto; /* left can scroll if super short screens */
    }
    .brand{display:flex; align-items:center; gap:12px;}
    .brand img{ width:56px; height:56px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,.35);}
    .brand h1{ font-size:1.2rem; margin:0; color:var(--c-ice); letter-spacing:.3px;}

    .left h2{
      font-weight:800; line-height:1.08; letter-spacing:.4px;
      font-size: clamp(1.6rem, 2.6vw, 2rem);
      text-shadow:0 2px 14px rgba(199,238,255,.25);
      margin:4px 0 0;
    }
    .left p{ color:var(--ink-2); margin:0 0 2px; }

    .perk{display:flex; gap:8px; color:var(--ink-1); font-size:.95rem;}
    .perk i{ color:var(--c-ice); margin-top:2px; }

    /* RIGHT: form panel (flex column; body scrolls INSIDE) */
    .right{
      position:relative; padding:20px 20px 14px;
      width:50%; max-width:50%;
      background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
      display:flex; flex-direction:column; min-width:0;
    }
    .right::before{
      content:""; position:absolute; inset:12px; border-radius:14px;
      background: conic-gradient(from 120deg, rgba(0,119,192,.0), rgba(0,119,192,.40), rgba(199,238,255,.22), rgba(0,119,192,.0) 70%);
      filter: blur(14px); opacity:.22; z-index:-1; animation:ring 8s linear infinite;
    }
    @keyframes ring{ to{ transform: rotate(1turn);} }

    .head{ display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px; }
    .btn-back{
      width:38px;height:38px;border-radius:999px; display:inline-grid; place-items:center;
      background:rgba(0,0,0,.25); border:1px solid var(--edge); color:var(--c-white);
    }
    .btn-back:hover{ background:rgba(0,0,0,.35); }

    .titlebox{text-align:center; margin:4px 0 2px;}
    .titlebox h2{ margin:0; font-weight:900; letter-spacing:.4px; font-size: clamp(1.45rem, 2.6vw, 1.8rem); }

    .hr-soft{ border:0;height:1px;width:100%;
      background:linear-gradient(90deg, transparent, var(--edge), transparent);
      margin:10px 0 12px;
    }

    /* Scroll body inside right panel */
    .right .container{
      width: min(96%, 760px);
      flex: 1 1 auto;
      overflow: auto;  /* inner scroll only if needed */
      padding-right: 4px; /* room for scrollbar */
    }

    /* form controls (compact) */
    .form-label{ color:var(--ink-1); text-transform:uppercase; font-weight:700; font-size:.78rem; letter-spacing:.04em; margin-bottom:.15rem;}
    .form-label.small{ text-transform:none; font-size:.76rem; font-weight:700; color:var(--c-ice); opacity:.95; margin-bottom:.25rem; }

    .form-control{
      background:#24323D; color:#f2fbff; border:1px solid var(--edge);
      padding:.65rem .85rem; border-radius:12px; height:auto; line-height:1.15;
    }
    .form-control::placeholder{ color:#cfe7f6; }
    .form-control:focus{
      background:#2A3A47; border-color:var(--c-pri); box-shadow:0 0 0 3px var(--ring);
    }

    /* tighten gutters/rows globally inside the form */
    .right .row{ --bs-gutter-x: .5rem; --bs-gutter-y: .35rem; }

    .btn-primary{
      border:0; border-radius:12px; font-weight:800;
      background:linear-gradient(135deg, var(--c-pri), #0b6aa3);
      color:#041723; padding:.8rem 1rem; box-shadow: 0 12px 26px rgba(0,119,192,.26);
    }
    .btn-primary:hover{ filter:brightness(1.06); transform:translateY(-1px); }

    .helper{ color:var(--ink-2); font-size:.9rem; }

    /* Landscape fit rules */
    @media (orientation: portrait){
      body { overflow-x: hidden; overflow-y: auto; }
      .frame{ height: auto; flex-wrap: wrap; }
      .left, .right{ width:100%; max-width:100%; border-right:none; }
      .left{ border-bottom:1px solid var(--edge); }
      .right .container{ max-height:none; overflow:visible; }
    }

    /* Compact mode for short landscape heights */
    @media (max-height: 560px) and (orientation: landscape){
      .orb{ display:none; }
      .frame{ border-radius:14px; }
      .left{ padding:18px; }
      .right{ padding:14px 14px 10px; }
      .brand img{ width:46px; height:46px; }
      .brand h1{ font-size:1rem; }
      .left h2{ font-size: clamp(1.3rem, 2.1vw, 1.5rem); }
      .left p, .helper{ display:none; } /* save vertical space */
      .form-control{ padding:.58rem .78rem; }
      .btn-primary{ padding:.7rem .9rem; }
      .titlebox h2{ font-size: clamp(1.2rem, 2vw, 1.45rem); }
      .right .row{ --bs-gutter-y: .25rem; }
    }

    /* Nice thin scrollbars for inner panel (WebKit) */
    .right .container::-webkit-scrollbar{ width: 8px; }
    .right .container::-webkit-scrollbar-thumb{ background: rgba(255,255,255,.18); border-radius: 8px; }
    .right .container::-webkit-scrollbar-track{ background: transparent; }
  </style>
</head>

<body>
  <!-- ambient -->
  <div class="orb o1"></div>
  <div class="orb o2"></div>

  <div class="wrap">
    <div class="frame">

      <!-- LEFT PANEL -->
      <section class="left">
        <div class="brand">
          <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" loading="lazy">
          <h1>Remer Fitness Gym</h1>
        </div>

        <h2>Create your account</h2>
        <p>Join the community and unlock member access with personalized training updates.</p>

        <div class="mt-1 d-grid gap-2">
          <div class="perk"><i class="ri-user-voice-line"></i><span>Friendly support from our staff</span></div>
          <div class="perk"><i class="ri-time-line"></i><span>Fast sign-up. No long forms.</span></div>
          <div class="perk"><i class="ri-lock-2-line"></i><span>We protect your data</span></div>
        </div>
      </section>

      <!-- RIGHT PANEL (FORM) -->
      <section class="right">
        <div class="head">
          <button type="button" onclick="history.back()" class="btn-back" aria-label="Go back"><i class="ri-arrow-go-back-line"></i></button>
          <a href="login.php" class="link-soft" style="color:#C7EEFF;text-decoration:none;">Back to login</a>
        </div>

        <div class="titlebox">
          <h2>Sign Up</h2>
        </div>

        <hr class="hr-soft">

        <div class="container">
          <form action="Actions/action_register.php" method="POST" class="w-100">

            <!-- Full Name -->
            <div class="row mt-1 align-items-start">
              <div class="col-12 col-md-0">
                <label class="form-label">Full Name</label>
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label small">First Name</label>
                <input type="text" class="form-control" name="fname" placeholder="First Name" required>
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label small">Last Name</label>
                <input type="text" class="form-control" name="lname" placeholder="Last Name" required>
              </div>
            </div>

            <!-- Email + Age -->
            <div class="row mt-1 align-items-start">
              <div class="col-12 col-md-0">
                <label class="form-label">Contact</label>
              </div>
              <div class="col-12 col-md-7">
                <label class="form-label small">Email</label>
                <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label small">Age</label>
                <input type="number" class="form-control" name="age" placeholder="Age" min="13" required>
              </div>
            </div>

            <!-- Phone -->
            <div class="row mt-1 align-items-start">
              <div class="col-12 col-md-0">
                <label class="form-label">Phone</label>
              </div>
              <div class="col-12 col-md-10">
                <label class="form-label small">Mobile Number</label>
                <input type="text" class="form-control" name="phone" placeholder="09XXXXXXXXX" required>
              </div>
            </div>

            <!-- Address (Street) -->
            <div class="row mt-1 align-items-start">
              <div class="col-12 col-md-0">
                <label class="form-label">Address</label>
              </div>
              <div class="col-12 col-md-10">
                <label class="form-label small">Street</label>
                <input type="text" class="form-control" name="street" placeholder="Street" required>
              </div>
            </div>

            <!-- Postal / City / Province -->
            <div class="row mt-1 align-items-start">
              <div class="col-12 col-md-0">
                <label class="form-label">Location</label>
              </div>
              <div class="col-12 col-md-10">
                <div class="row">
                  <div class="col-12 col-md-4">
                    <label class="form-label small">Postal Code</label>
                    <input type="text" class="form-control" name="postal" placeholder="Postal Code" required>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label small">City</label>
                    <input type="text" class="form-control" name="city" placeholder="City" required>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label small">Province</label>
                    <input type="text" class="form-control" name="province" placeholder="Province" required>
                  </div>
                </div>
              </div>
            </div>

            <!-- Passwords -->
            <div class="row mt-1 align-items-start">
              <div class="col-12 col-md-0">
                <label class="form-label">Password</label>
              </div>
              <div class="col-12 col-md-10">
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                  </div>
                </div>
                <div class="helper mt-2">Use 8–20 characters with letters &amp; numbers. No spaces or emoji.</div>
              </div>
            </div>

            <!-- Submit -->
            <div class="row mt-3">
              <div class="col-12">
                <button type="submit" name="submit" class="btn btn-primary w-100">Create Account</button>
              </div>
            </div>

            <p class="mt-3 text-center" style="color:var(--ink-1);">
              Already have an account?
              <a href="login.php" class="fw-semibold" style="color:#C7EEFF; text-decoration:none;">Login here</a>
            </p>
          </form>
        </div>
      </section>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
