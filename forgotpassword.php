<?php
    session_start();

    // ====== HANDLE OTP SEND ======
    unset($_SESSION['user_email']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
        include 'Actions/dbconnect.php'; 
        require 'PHPMailer/PHPMailer.php';
        require 'PHPMailer/SMTP.php';
        require 'PHPMailer/Exception.php';

        $email = mysqli_real_escape_string($conn, $_POST['email']);

        if (!empty($email)) {
            $sql = "SELECT * FROM user WHERE email = '$email'";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) === 1) {
                $code = rand(100000, 999999);
                $_SESSION['verification_email'] = $email;
                $_SESSION['verification_code'] = $code;

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = (require __DIR__ . '/Actions/mail_config.php')['username'];
                    $mail->Password = (require __DIR__ . '/Actions/mail_config.php')['password']; // Consider moving to env var
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('fitnessremer@gmail.com', 'Remer Fitness Gym');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Remer Fitness Verification Code';
                    $mail->Body    = "
                        <h2 style='margin:0 0 8px 0;'>Verification Code</h2>
                        <p style='margin:0 0 8px 0;'>Your verification code is:</p>
                        <h1 style='color:#0077C0;letter-spacing:4px;margin:0 0 12px 0;'>$code</h1>
                        <p style='margin:0 0 12px 0;'>Use this code within 10 minutes to reset your password.</p>
                        <hr style='border:none;border-top:1px solid #e5e5e5;margin:16px 0;'/>
                        <small>If you did not request a password reset, you can safely ignore this message.</small>
                    ";

                    $mail->send();
                    $_SESSION['verification_code'] = $code;
                    $_SESSION['otp_sent_time'] = time();
                    $_SESSION['user_email'] = $email;
                    
                    header("Location: verifypassword.php");
                    exit();
                } catch (PHPMailer\PHPMailer\Exception $e) {
                    $_SESSION['error'] = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $_SESSION['error'] = "Email not found.";
            }
        } else {
            $_SESSION['error'] = "Please enter an email address.";
        }
    }
?>

<?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
    <?= htmlspecialchars($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px;" role="alert">
    <?= htmlspecialchars($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Remer Fitness Gym — Forgot Password</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    /* ===== Palette ===== */
    :root{
      --c-white:#FAFAFA;   /* near white */
      --c-ice:#C7EEFF;     /* accent light blue */
      --c-pri:#0077C0;     /* primary blue */
      --c-base:#1D242B;    /* deep slate */
      --ink-1:#EAF7FF;
      --ink-2:#B8D9EA;
      --glass-1:rgba(255,255,255,.08);
      --edge:rgba(199,238,255,.50);
      --ring:rgba(0,119,192,.30);
    }

    html,body{height:100%;}
    body{
      margin:0; color:var(--c-white);
      background:
        radial-gradient(60rem 60rem at 120% -10%, rgba(0,119,192,.16), transparent),
        radial-gradient(42rem 42rem at -20% 110%, rgba(199,238,255,.14), transparent),
        linear-gradient(135deg, #141A20 0%, #1B2430 100%);
      overflow-x:hidden;
    }

    /* floating lights */
    .orb{position:fixed; filter:blur(32px); opacity:.35; z-index:0; pointer-events:none;}
    .orb.o1{width:360px;height:360px;background:#0077C0; top:-80px; right:-60px; animation:float 11s ease-in-out infinite;}
    .orb.o2{width:300px;height:300px;background:#C7EEFF; bottom:-60px; left:-40px; animation:float 13s ease-in-out infinite reverse;}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(16px)}}

    /* layout */
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
    @media(max-width:992px){ .frame{ grid-template-columns:1fr; } }

    /* left hero */
    .showcase{
      position:relative; padding:42px 42px 34px;
      background:
        linear-gradient(180deg, rgba(199,238,255,.08), rgba(255,255,255,0)),
        radial-gradient(60rem 60rem at -20% 110%, rgba(0,119,192,.18), transparent);
      border-right:1px solid var(--edge);
      display:flex; flex-direction:column; justify-content:center; gap:18px;
    }
    .brand{display:flex; align-items:center; gap:14px;}
    .brand img{ width:64px; height:64px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,.35); }
    .brand h1{ font-size:1.35rem; margin:0; letter-spacing:.4px; color:var(--c-ice); }

    .hero-title{
      font-weight:800; line-height:1.1; letter-spacing:.6px;
      font-size: clamp(2rem, 4vw, 2.6rem);
      text-shadow:0 2px 14px rgba(199,238,255,.25);
    }
    .hero-sub{ color:var(--ink-2); max-width:50ch; }

    .steps{display:grid; gap:10px; margin-top:6px;}
    .steps .step{
      display:flex; gap:10px; align-items:flex-start; color:var(--ink-1);
    }
    .steps i{ color:var(--c-ice); margin-top:2px; }

    /* right panel */
    .panel{
      position:relative; padding:40px 34px 34px; display:flex; flex-direction:column;
      background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
    }
    .panel::before{
      content:""; position:absolute; inset:16px; border-radius:18px;
      background: conic-gradient(from 120deg, rgba(0,119,192,.0), rgba(0,119,192,.40), rgba(199,238,255,.22), rgba(0,119,192,.0) 70%);
      filter: blur(18px); opacity:.28; z-index:-1; animation:ring 8s linear infinite;
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

    /* form controls */
    .form-label{ color:var(--ink-1); }
    .form-control{
      background:#23303a; color:#f2fbff; border:1px solid var(--edge);
      padding:.85rem .95rem; border-radius:12px;
    }
    .form-control::placeholder{ color:#c1d7e5; }
    .form-control:focus{
      background:#283845; border-color:var(--c-pri); box-shadow:0 0 0 4px var(--ring);
    }

    .helper{ color:var(--ink-2); font-size:.95rem; }

    .btn-primary{
      border:0;border-radius:12px;font-weight:800;
      background:linear-gradient(135deg, var(--c-pri), #0b6aa3);
      color:#041723; padding:.85rem 1rem; box-shadow:0 16px 32px rgba(0,119,192,.28);
    }
    .btn-primary:hover{ filter:brightness(1.06); transform:translateY(-1px); }

    .mini-links{ display:flex; gap:14px; justify-content:center; margin-top:16px; color:var(--ink-2); font-size:.92rem; }
    .mini-links a{ color:var(--c-ice); text-decoration:none; }
    .mini-links a:hover{ text-decoration:underline; }

  </style>
</head>
<body>

  <!-- subtle ambient lights -->
  <div class="orb o1"></div>
  <div class="orb o2"></div>

  <div class="shell">
    <div class="frame">

      <!-- LEFT: info/branding -->
      <section class="showcase">
        <div class="brand">
          <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" loading="lazy">
          <h1>Remer Fitness Gym</h1>
        </div>

        <h2 class="hero-title">Forgot your password?</h2>
        <p class="hero-sub">
          No stress. We’ll send a <strong>6-digit code</strong> to your email so you can verify it’s you,
          then you’ll create a new password in the next step.
        </p>

        <div class="steps">
          <div class="step"><i class="ri-mail-send-line"></i><span>Enter your email address.</span></div>
          <div class="step"><i class="ri-shield-keyhole-line"></i><span>Check your inbox for a 6-digit code.</span></div>
          <div class="step"><i class="ri-check-double-line"></i><span>Verify and reset your password.</span></div>
        </div>
      </section>

      <!-- RIGHT: form -->
      <section class="panel">
        <div class="backline">
          <button type="button" onclick="history.back()" class="btn-back" aria-label="Go back"><i class="ri-arrow-go-back-line"></i></button>
          <a href="login.php" class="link-soft" style="color:#C7EEFF;text-decoration:none;">Back to login</a>
        </div>

        <div class="login-head">
          <h2>Send a verification code</h2>
        </div>

        <hr class="hr-soft">

        <form action="" method="POST" class="w-100">
          <div class="mb-3 mt-2">
            <label for="email" class="form-label m-0">Email address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required>
            <div class="helper mt-2">We’ll email a 6-digit code. Codes expire after 10 minutes.</div>
          </div>

          <button type="submit" name="verify" id="verify" class="btn btn-primary mt-2 w-100 fw-semibold">
            Send Code
          </button>
        </form>

        <div class="mini-links">
    
        </div>
      </section>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
