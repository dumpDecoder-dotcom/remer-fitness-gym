<?php
  session_start();
  // Flash alerts (kept from your original)
  if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
      <?= htmlspecialchars($_SESSION['success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php unset($_SESSION['success']); endif;

  if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
      <?= htmlspecialchars($_SESSION['error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php unset($_SESSION['error']); endif; ?>

  <?php if (isset($_GET['logout']) && $_GET['logout']==='success'): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
      Logged out successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
      if (window.history.replaceState){
        const url=new URL(window.location.href);
        url.searchParams.delete('logout');
        window.history.replaceState(null,'',url.pathname+url.search);
      }
    </script>
  <?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Remer Fitness Gym — Login</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    /* ===== Theme palette ===== */
    :root{
      --c-white:#FAFAFA;    /* near white */
      --c-ice:#C7EEFF;      /* accent light blue */
      --c-pri:#0077C0;      /* primary blue */
      --c-base:#1D242B;     /* deep slate */
      --ink-1:#EAF7FF;
      --ink-2:#B8D9EA;
      --glass-1:rgba(255,255,255,.08);
      --glass-2:rgba(255,255,255,.14);
      --ring:rgba(0,119,192,.30);
      --edge:rgba(199,238,255,.5);
    }

    html,body{height:100%;}
    body{
      margin:0; color:var(--c-white);
      background:
        radial-gradient(60rem 60rem at 120% -10%, rgba(0,119,192,.18), transparent),
        radial-gradient(42rem 42rem at -20% 110%, rgba(199,238,255,.16), transparent),
        linear-gradient(135deg, #141A20 0%, #1B2430 100%);
      overflow-x:hidden;
    }

    /* Floating lights */
    .orb{position:fixed; filter:blur(32px); opacity:.35; z-index:0; pointer-events:none;}
    .orb.o1{width:380px;height:380px;background:#0077C0; top:-80px; right:-60px; animation:float 11s ease-in-out infinite;}
    .orb.o2{width:320px;height:320px;background:#C7EEFF; bottom:-60px; left:-40px; animation:float 13s ease-in-out infinite reverse;}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(18px)}}

    /* Layout shell */
    .shell{position:relative; z-index:1; min-height:100%; display:grid; place-items:center; padding:24px;}
    .frame{
      display:grid; gap:0;
      grid-template-columns: 1.1fr .9fr;
      width:min(1120px, 96vw);
      border-radius:22px; overflow:hidden;
      border:1px solid var(--edge);
      background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.04));
      box-shadow: 0 22px 60px rgba(0,0,0,.45);
      backdrop-filter: blur(10px);
    }
    @media(max-width: 992px){ .frame{ grid-template-columns:1fr; } }

    /* Left showcase (clean) */
    .showcase{
      position:relative; padding:42px 42px 34px;
      background:
        linear-gradient(180deg, rgba(199,238,255,.08), rgba(255,255,255,0)),
        radial-gradient(60rem 60rem at -20% 110%, rgba(0,119,192,.18), transparent);
      border-right:1px solid var(--edge);
      display:flex; flex-direction:column; justify-content:center;
      gap:18px;
    }
    .brand{display:flex; align-items:center; gap:14px;}
    .brand img{ width:64px; height:64px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,.35); }
    .brand h1{ font-size:1.35rem; margin:0; letter-spacing:.4px; color:var(--c-ice); }

    .hero-title{
      font-weight:800; line-height:1.1; letter-spacing:.6px;
      font-size: clamp(2rem, 4vw, 2.6rem);
      text-shadow:0 2px 14px rgba(199,238,255,.25);
    }
    .hero-sub{ color:var(--ink-2); max-width:48ch; }

    /* Right login panel */
    .panel{
      position:relative; padding:40px 34px 34px; display:flex; flex-direction:column;
      background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
    }
    /* Animated border ring (subtle) */
    .panel::before{
      content:""; position:absolute; inset:16px; border-radius:18px;
      background: conic-gradient(from 120deg, rgba(0,119,192,.0), rgba(0,119,192,.40), rgba(199,238,255,.22), rgba(0,119,192,.0) 70%);
      filter: blur(18px); opacity:.30; z-index:-1; animation:ring 8s linear infinite;
    }
    @keyframes ring{ to{ transform: rotate(1turn); } }

    .backline{display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:8px;}
    .btn-back{
      width:42px;height:42px;border-radius:999px; display:inline-grid; place-items:center;
      background:rgba(0,0,0,.25); border:1px solid var(--edge); color:var(--c-white);
    }
    .btn-back:hover{ background:rgba(0,0,0,.35); }

    .login-head{text-align:center; margin:10px 0 2px;}
    .login-head h2{
      margin:0; font-weight:900; letter-spacing:.5px;
      font-size: clamp(1.6rem, 3.5vw, 2.1rem);
    }

    .hr-soft{ border:0;height:1px;width:100%;
      background:linear-gradient(90deg, transparent, var(--edge), transparent);
      margin:16px 0 18px;
    }

    /* Form controls */
    .form-label{ color:var(--ink-1); }
    .form-control{
      background:#23303a; color:#f2fbff; border:1px solid var(--edge);
      padding:.85rem .95rem; border-radius:12px;
    }
    .form-control::placeholder{ color:#c1d7e5; }
    .form-control:focus{
      background:#283845; border-color:var(--c-pri); box-shadow:0 0 0 4px var(--ring);
    }

    .input-wrap{ position:relative; }
    .input-icon{
      position:absolute; right:.75rem; top:50%; transform:translateY(-50%);
      color:#c1e6fb; cursor:pointer; opacity:.95;
    }

    .row-mini{ display:flex; justify-content:space-between; align-items:center; gap:1rem; }
    .form-check-input{ background:#23303a; border:1px solid var(--edge); }
    .form-check-input:checked{ background-color:var(--c-pri); border-color:var(--c-pri); box-shadow:0 0 0 3px var(--ring); }
    .link-soft{ color:var(--c-ice); text-decoration:none; }
    .link-soft:hover{ color:#fff; text-decoration:underline; }

    .btn-primary{
      border:0;border-radius:12px;font-weight:800;
      background:linear-gradient(135deg, var(--c-pri), #0b6aa3);
      color:#041723; padding:.85rem 1rem; box-shadow:0 16px 32px rgba(0,119,192,.28);
    }
    .btn-primary:hover{ filter:brightness(1.06); transform:translateY(-1px); }

    /* Footer micro links */
    .mini-links{ display:flex; gap:14px; justify-content:center; margin-top:16px; color:var(--ink-2); font-size:.92rem; }
    .mini-links a{ color:var(--c-ice); text-decoration:none; }
    .mini-links a:hover{ text-decoration:underline; }
  </style>
</head>
<body>

  <!-- floating orbs -->
  <div class="orb o1"></div>
  <div class="orb o2"></div>

  <div class="shell">
    <div class="frame">
      <!-- LEFT: showcase (no stat cards) -->
      <section class="showcase">
        <div class="brand">
          <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" loading="lazy">
          <h1>Remer Fitness Gym</h1>
        </div>

        <h2 class="hero-title">Train Smarter. <span style="color:#C7EEFF">Recover Faster.</span></h2>
        <p class="hero-sub">
          Access coach-led classes, member-only promos, and a community that moves with you.
        </p>
      </section>

      <!-- RIGHT: login panel -->
      <section class="panel">
        <div class="backline">
          <button type="button" onclick="history.back()" class="btn-back" aria-label="Go back"><i class="ri-arrow-go-back-line"></i></button>
          <a href="index.php" class="link-soft">Home</a>
        </div>

        <div class="login-head">
          <h2>Log in to your account</h2>
        </div>

        <hr class="hr-soft">

        <form action="Actions/action_login.php" method="POST" class="w-100">
          <div class="mb-3">
            <label for="email" class="form-label m-0">Email address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required>
          </div>

          <label for="password" class="form-label m-0">Password</label>
          <div class="input-wrap mb-1">
            <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Your password" required>
            <i class="ri-eye-line input-icon" id="togglePass" role="button" aria-label="Show password"></i>
          </div>
          <div class="form-text" id="passwordHelpBlock">
            Use 8–20 characters with letters & numbers. No spaces or emoji.
          </div>

          <div class="row-mini mt-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember_me">
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a href="forgotpassword.php" class="link-soft">Forgot password?</a>
          </div>

          <button type="submit" name="submit" id="submit" class="btn btn-primary mt-3 w-100">Log in</button>

          <p class="mt-3 text-center" style="color:var(--ink-2);">
            Don’t have an account? <a href="register.php" class="link-soft">Register here</a>
          </p>
        </form>

        <div class="mini-links">
     
        </div>
      </section>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle password visibility
    const pass = document.getElementById('password');
    const toggle = document.getElementById('togglePass');
    if (toggle && pass) {
      toggle.addEventListener('click', () => {
        const isPw = pass.type === 'password';
        pass.type = isPw ? 'text' : 'password';
        toggle.classList.toggle('ri-eye-line', !isPw);
        toggle.classList.toggle('ri-eye-off-line', isPw);
        toggle.setAttribute('aria-label', isPw ? 'Hide password' : 'Show password');
      });
    }
  </script>
</body>
</html>
