<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

/* Load promos from DB (active only, and within date if set) */
$today = date('Y-m-d');
$sql = "
  SELECT *
  FROM promos
  WHERE status='active'
    AND (start_date IS NULL OR start_date <= ?)
    AND (end_date   IS NULL OR end_date   >= ?)
  ORDER BY promo_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$res = $stmt->get_result();
$promos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* Helpers for badges & expiry text */
function badgeClass($type){
  if ($type==='product') return 'special-gradient';
  if ($type==='membership') return 'hot';
  if ($type==='class') return 'new';
  return '';
}
function expiryLabel($end){
  if (!$end) return 'No expiry';
  $d = (strtotime($end) - strtotime(date('Y-m-d'))) / 86400;
  if ($d < 0) return 'Expired';
  if ($d === 0.0) return 'Expires today';
  if ($d <= 7) return "Expires in ".(int)$d." days";
  return "Valid until ".htmlspecialchars($end);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Promos - Remer Fitness Gym</title>
<link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
<style>
  /* ===== Dark / Blue theme tokens ===== */
  :root{
    --bg-900:#0b1220;        /* page background */
    --bg-800:#0f172a;        /* panels/cards */
    --bg-700:#111827;        /* navbar/hero overlay */
    --line-600:#1f2a44;      /* borders */
    --line-500:#223152; 
    --text-100:#e5ecff;      /* primary text */
    --text-200:#c6d3ff;      /* secondary text */
    --text-300:#9fb3ff;      
    --muted:#8a97b3;         /* muted text */
    --brand-400:#60a5fa;     /* light blue */
    --brand-500:#3b82f6;     /* primary blue */
    --brand-600:#2563eb;     /* darker blue */
    --accent-rose:#f472b6;
    --accent-orange:#fb923c;

    --grad-brand: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 45%, #1d4ed8 100%);
    --grad-hero : radial-gradient(1200px 500px at 20% -10%, rgba(59,130,246,.25), transparent 60%),
                  radial-gradient(1200px 500px at 80% -10%, rgba(14,165,233,.22), transparent 60%),
                  linear-gradient(180deg, #0b1220 0%, #0f172a 100%);
    --grad-badge-membership: linear-gradient(135deg, #fb7185, #f59e0b);
    --grad-badge-class: linear-gradient(135deg, #34d399, #22c55e);
    --grad-badge-product: linear-gradient(135deg, #a78bfa, #f472b6);
  }

  /* Page */
  html,body{height:100%}
  body{
    min-height:100vh;display:flex;flex-direction:column;
    background:var(--grad-hero); color:var(--text-100);
  }
  main{flex:1}

  /* ===== Navbar (changed to dark) ===== */
  .navbar{
    background:rgba(17,24,39,.65)!important;
    backdrop-filter: blur(10px);
    border-bottom:1px solid var(--line-600);
  }
  .navbar .nav-link{
    color:var(--text-200)!important; font-weight:600; padding:.5rem 1rem;
  }
  .navbar .nav-link.active,
  .navbar .nav-link:hover{ color:#fff!important }
  .join-now-btn{
    background:var(--grad-brand); color:#fff; border:none;
    padding:.5rem 1.25rem; border-radius:999px; font-weight:800;
    box-shadow:0 8px 24px rgba(59,130,246,.35);
  }
  .logo-img{width:56px;height:56px;object-fit:contain}

  /* ===== Hero ===== */
  .promo-hero{
    padding:4rem 0 2.75rem; position:relative; overflow:hidden;
    border-bottom:1px solid var(--line-600);
  }
  .promo-hero h1{color:#fff}
  .promo-hero p{color:var(--text-200)}
  .search-box{
    border-radius:999px; padding:.9rem 1.25rem;
    background:rgba(255,255,255,0.06); border:1px solid var(--line-600); color:#fff;
  }
  .search-box::placeholder{color:#b9c4e6}
  .search-box:focus{outline:0;border-color:var(--brand-400); box-shadow:0 0 0 .25rem rgba(59,130,246,.25)}

  /* ===== Filter bar (dark panel) ===== */
  .filter-section{
    background:var(--bg-800); border:1px solid var(--line-600); border-radius:16px;
    padding:1.25rem 1.25rem 1rem; box-shadow:0 10px 30px rgba(0,0,0,.35)
  }
  .filter-section h5{color:#fff}
  .filter-btn{
    margin:.25rem; border-radius:999px; padding:.55rem 1.25rem;
    border:1px solid var(--line-600); background:transparent; color:var(--text-200);
    cursor:pointer; font-weight:600
  }
  .filter-btn:hover{border-color:var(--brand-400); color:#fff}
  .filter-btn.active{
    background:var(--grad-brand); border-color:transparent; color:#fff;
    box-shadow:0 10px 22px rgba(37,99,235,.35);
  }
  .form-select{
    background-color:#0b1220; color:#fff; border:1px solid var(--line-600);
  }

  .promo-count{color:var(--brand-400); font-weight:800}

  /* ===== Promo cards (dark) ===== */
  .promo-card{
    background:linear-gradient(180deg, rgba(17,24,39,.92), rgba(7,12,24,.95));
    border:1px solid var(--line-600); border-radius:18px; padding:1.25rem 1.25rem 1.1rem;
    height:100%; display:flex; flex-direction:column;
    transition:transform .2s ease, box-shadow .25s ease, border-color .2s ease, background .2s ease;
  }
  .promo-card:hover{
    transform:translateY(-6px); border-color:#3b82f6;
    box-shadow:0 16px 40px rgba(2,6,23,.9), 0 0 0 1px rgba(59,130,246,.18) inset;
  }
  .promo-card h3{color:#fff}
  .text-muted{color:var(--muted)!important}

  /* Badges */
  .promo-badge{
    display:inline-flex; align-items:center; gap:.4rem;
    color:#fff; padding:.28rem .85rem; border-radius:999px; font-weight:800; font-size:.82rem;
    background:var(--grad-badge-product);
    box-shadow:0 6px 18px rgba(0,0,0,.35)
  }
  .promo-badge.hot{background:var(--grad-badge-membership)}
  .promo-badge.new{background:var(--grad-badge-class)}

  /* Pricing & discount */
  .promo-discount{font-size:1.85rem; font-weight:900; color:var(--brand-400)}
  .promo-original-price{color:#99a8d8; text-decoration:line-through; margin-right:.5rem}
  .promo-new-price{color:#fda4af; font-weight:900}

  /* Expiry bar */
  .promo-timer{
    background:rgba(59,130,246,.10);
    border:1px dashed rgba(96,165,250,.35);
    border-left:4px solid var(--brand-500);
    padding:.6rem 1rem; border-radius:12px; margin-top:auto; margin-bottom:1rem;
    color:var(--text-200)
  }

  /* Buttons */
  .btn-primary{
    background:var(--grad-brand); border:none; font-weight:800; letter-spacing:.02em;
    box-shadow:0 14px 28px rgba(37,99,235,.35);
  }
  .btn-primary:hover{filter:brightness(1.08)}
  .btn-outline-light{border-color:var(--line-600); color:#fff}
  .btn-outline-light:hover{background:var(--brand-600); border-color:transparent}

  /* Results empty */
  .no-results{color:#aab6dd}
  .no-results i{font-size:4.5rem; color:#2a3554}

  /* Footer */
  footer{background:#0a0f1a; color:#c8d3ff; border-top:1px solid var(--line-600)}
  .text-blue{color:var(--brand-400)!important}
  .shadow-blur-blue{ text-shadow:0 0 22px rgba(59,130,246,.45) }

  @media (max-width:768px){
    .promo-hero{padding:2.25rem 0 1.25rem}
    .promo-hero h1{font-size:2rem}
    .promo-card{padding:1rem}
    .promo-discount{font-size:1.5rem}
  }
</style>
</head>
<body>

<!-- NAVBAR: switched to dark look -->
<nav class="navbar navbar-expand-lg navbar-dark">
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
        <li class="nav-item"><a class="nav-link" href="trainings.php">TRAINING</a></li>
        <li class="nav-item"><a class="nav-link active" href="all-promos.php">PROMOS</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <a href="login.php" class="btn join-now-btn">JOIN NOW</a>
      </div>
    </div>
  </div>
</nav>

<main>
  <div class="promo-hero">
    <div class="container text-center position-relative">
      <h1 class="display-4 fw-bold mb-2">
        <i class="ri-gift-fill"></i> All Promos & Deals
      </h1>
      <p class="fs-5 mb-4">Save on memberships, classes, and products.</p>
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <input type="text" class="search-box" id="searchInput" placeholder=" Search promos...">
        </div>
      </div>
    </div>
  </div>

  <div class="container py-5">
    <div class="filter-section">
      <div class="row align-items-end">
        <div class="col-lg-9 mb-3 mb-lg-0">
          <h5 class="mb-3"><i class="ri-filter-3-line text-blue"></i> Filter by Category</h5>
          <div class="d-flex flex-wrap gap-2">
            <button class="filter-btn active" data-filter="all"><i class="ri-list-check"></i> All Promos</button>
            <button class="filter-btn" data-filter="membership"><i class="ri-user-star-line"></i> Membership</button>
            <button class="filter-btn" data-filter="product"><i class="ri-shopping-bag-line"></i> Products</button>
            <button class="filter-btn" data-filter="class"><i class="ri-run-line"></i> Classes</button>
            <button class="filter-btn" data-filter="expiring"><i class="ri-time-line"></i> Expiring Soon</button>
          </div>
        </div>
        <div class="col-lg-3">
          <h5 class="mb-3"><i class="ri-sort-desc text-blue"></i> Sort by</h5>
          <select class="form-select" id="sortSelect">
            <option value="featured">Featured</option>
            <option value="discount-high">Highest Discount</option>
            <option value="discount-low">Lowest Discount</option>
            <option value="expiry">Expiring Soon</option>
            <option value="newest">Newest First</option>
          </select>
        </div>
      </div>
      <div class="mt-3 pt-3 border-top" style="border-color:var(--line-600)!important">
        <p class="mb-0"><i class="ri-coupon-3-line text-blue"></i> Showing <span class="promo-count" id="promoCount">0</span> active promos</p>
      </div>
    </div>

    <div class="row g-4" id="promosGrid">
      <?php foreach($promos as $p):
        $badgeVariant = ($p['promo_type']==='membership' ? 'hot' : ($p['promo_type']==='class' ? 'new' : ''));
        $discountLabel = $p['discount_type']==='percent'
          ? (rtrim(rtrim($p['discount_value'],'0'),'.').'% OFF')
          : ('₱'.number_format($p['discount_value'],0).' OFF');
        $expiresText = expiryLabel($p['end_date']);
        $expiryDays = $p['end_date'] ? max(0, (int)ceil( (strtotime($p['end_date'])-strtotime(date('Y-m-d')))/86400 )) : 9999;
        $orig = $p['original_price']; $new = $p['new_price'];
      ?>
      <div class="col-sm-6 col-lg-4"
           data-category="<?= htmlspecialchars($p['promo_type']) ?>"
           data-discount="<?= (int)$p['discount_value'] ?>"
           data-expiry="<?= (int)$expiryDays ?>">
        <div class="promo-card">
          <span class="promo-badge <?= $badgeVariant ?>"><?= strtoupper($p['promo_type']) ?> DEAL</span>
          <h3 class="fw-bold mt-2 mb-2"><?= htmlspecialchars($p['title']) ?></h3>
          <div class="promo-discount"><?= $discountLabel ?></div>
          <?php if ($orig!==null || $new!==null): ?>
            <div class="mb-3">
              <?php if ($orig!==null): ?><span class="promo-original-price">₱<?= fmod($orig,1)==0?number_format($orig,0):number_format($orig,2) ?></span><?php endif; ?>
              <?php if ($new!==null): ?><span class="promo-new-price">₱<?= fmod($new,1)==0?number_format($new,0):number_format($new,2) ?></span><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($p['description'])): ?>
            <p class="text-muted mb-3"><?= htmlspecialchars($p['description']) ?></p>
          <?php endif; ?>
          <div class="promo-timer">
            <small><i class="ri-time-line"></i> <strong><?= $expiresText ?></strong></small>
          </div>
          <a href="login.php" class="btn btn-primary w-100">Claim</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="no-results d-none" id="noResults">
      <i class="ri-emotion-sad-line"></i>
      <h3 class="mt-3">No promos found</h3>
      <p>Try adjusting your filters or search terms.</p>
      <button class="btn btn-outline-light mt-3" id="resetFilters">
        <i class="ri-refresh-line"></i> Reset Filters
      </button>
    </div>
  </div>
</main>

<footer class="px-md-4 px-lg-5 py-4">
  <div class="container d-flex align-items-center gap-4 py-4" style="border-bottom: 1px solid var(--line-600);">
    <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" width="90" height="90">
    <h1 class="fst-italic text-blue shadow-blur-blue fw-bold display-4">BEAST MODE</h1>
  </div>
  <div class="container py-3">
    <p class="mb-0 text-center" style="color:var(--muted)">© 2025 Remer Fitness Gym. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const filterBtns = document.querySelectorAll('.filter-btn');
  const searchInput = document.getElementById('searchInput');
  const sortSelect  = document.getElementById('sortSelect');
  const promoCount  = document.getElementById('promoCount');
  const noResults   = document.getElementById('noResults');
  const promosGrid  = document.getElementById('promosGrid');

  let currentFilter = 'all';
  let currentSearch = '';

  function cards(){ return Array.from(promosGrid.querySelectorAll(':scope > div')); }

  function updatePromoCount(){
    const visible = cards().filter(c => c.style.display !== 'none').length;
    promoCount.textContent = visible;
  }

  function applyFilters(){
    let visibleCount = 0;
    cards().forEach(card => {
      const category = card.getAttribute('data-category');
      const expiry   = parseInt(card.getAttribute('data-expiry'));
      const text     = card.textContent.toLowerCase();

      let matchCat = (currentFilter==='all') ? true
                   : (currentFilter==='expiring' ? (expiry <= 7) : (category === currentFilter));
      let matchSearch = !currentSearch || text.includes(currentSearch);

      if (matchCat && matchSearch){ card.style.display=''; visibleCount++; }
      else { card.style.display='none'; }
    });
    updatePromoCount();
    if (visibleCount===0){ noResults.classList.remove('d-none'); promosGrid.classList.add('d-none'); }
    else { noResults.classList.add('d-none'); promosGrid.classList.remove('d-none'); }
  }

  function sortPromos(sortType){
    const list = cards();
    list.sort((a,b)=>{
      const dA = parseInt(a.getAttribute('data-discount'));
      const dB = parseInt(b.getAttribute('data-discount'));
      const eA = parseInt(a.getAttribute('data-expiry'));
      const eB = parseInt(b.getAttribute('data-expiry'));
      switch(sortType){
        case 'discount-high': return dB - dA;
        case 'discount-low':  return dA - dB;
        case 'expiry':        return eA - eB;
        case 'newest':        return 0; // keep DB order newest first
        case 'featured':
        default:              return 0;
      }
    });
    list.forEach(el => promosGrid.appendChild(el));
    applyFilters();
  }

  filterBtns.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      filterBtns.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.getAttribute('data-filter');
      applyFilters();
    });
  });

  searchInput.addEventListener('input', ()=>{
    currentSearch = searchInput.value.toLowerCase().trim();
    applyFilters();
  });

  sortSelect.addEventListener('change', ()=>sortPromos(sortSelect.value));

  document.getElementById('resetFilters').addEventListener('click', ()=>{
    filterBtns.forEach(b => b.classList.toggle('active', b.getAttribute('data-filter')==='all'));
    searchInput.value=''; currentSearch='';
    sortSelect.value='featured'; currentFilter='all';
    applyFilters();
  });

  // init
  updatePromoCount();
  applyFilters();
</script>
</body>
</html>
