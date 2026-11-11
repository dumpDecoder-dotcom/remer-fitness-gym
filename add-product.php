<?php
session_start();
include 'Actions/dbconnect.php';

// flash
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator | Remer Fitness Gym</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    .thumb{width:140px;height:140px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;background:#f8f9fa}
    .dropzone{
      border:2px dashed #a3bffa; border-radius:14px; padding:18px;
      background:#f8fbff; cursor:pointer; transition:background .2s,border-color .2s;
    }
    .dropzone.dragover{ background:#eef6ff; border-color:#3b82f6; }
    .dz-icon{font-size:28px}
    .dz-text{line-height:1.2}
    .dz-help{font-size:.85rem}
  </style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

<nav class="navbar navbar-expand-lg bg-transparent">
  <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link active" href="product-list.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
  <div class="w-100">
    <a href="product-list.php" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width:40px;height:40px;">
      <i class="ri-arrow-go-back-line"></i>
    </a>
  </div>

  <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
    <div class="rounded-5 bg-white p-4 d-flex flex-column gap-5 w-100" style="min-height:690px">
      <div class="w-100 d-flex justify-content-between align-items-center">
        <h2 class="lh-1 m-0 display-6 fw-bold">Add Product</h2>
      </div>

      <div class="w-100 flex-grow-1">
        <!-- enctype is required for files -->
        <form action="Actions/action_addproduct.php" method="POST" enctype="multipart/form-data" class="d-flex flex-column h-100 w-100">

          <div class="mb-3 row">
            <label for="product_name" class="col-sm-2 col-form-label">Product Name</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter product name" required>
            </div>
          </div>

          <div class="mb-3 row">
            <label for="description" class="col-sm-2 col-form-label">Description</label>
            <div class="col-sm-10">
              <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description (optional)"></textarea>
            </div>
          </div>

          <!-- Drag & Drop Image Upload -->
          <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">Product Image</label>
            <div class="col-sm-10">
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <img id="preview" class="thumb" src="Assets/Graphics/sample.png" alt="Preview">
                <div class="flex-grow-1">
                  <!-- Hidden real input (kept name=image for your action_addproduct) -->
                  <input type="file" id="image" name="image" class="d-none" accept="image/jpeg,image/png,image/webp" required>
                  <!-- Dropzone -->
                  <div id="dropzone" class="dropzone d-flex align-items-center gap-3">
                    <i class="ri-upload-cloud-2-line dz-icon text-primary"></i>
                    <div class="dz-text">
                      <div class="fw-semibold">Drag & drop image here</div>
                      <div class="dz-help text-muted">or <u id="browseLink" role="button">browse</u> (JPG/PNG/WEBP, max 5 MB)</div>
                    </div>
                  </div>
                  <button id="removeBtn" type="button" class="btn btn-outline-secondary btn-sm mt-2 d-none">
                    <i class="ri-close-line"></i> Remove image
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-sm-2 col-form-label" for="category">Category</label>
            <div class="col-sm-10">
              <select id="category" name="category" class="form-select" required>
                <option disabled>Select Category</option>
                <option value="Protein" selected>Protein</option>
                <option value="Performance">Performance</option>
                <option value="Wellness">Wellness</option>
              </select>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-sm-2 col-form-label" for="subcategory">Sub Category</label>
            <div class="col-sm-10">
              <select id="subcategory" name="subcategory" class="form-select" required></select>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-sm-2 col-form-label" for="price">Price</label>
            <div class="col-sm-10">
              <input type="number" class="form-control" id="price" name="price"
                     placeholder="Enter the price" step="1" min="0" required
                     oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            </div>
          </div>

          <div class="mb-3 row">
            <label for="quantity" class="col-sm-2 col-form-label">Quantity</label>
            <div class="col-sm-10">
              <input type="number" class="form-control" id="quantity" name="quantity"
                     placeholder="Enter the quantity" required min="0" step="1">
            </div>
          </div>

          <div class="d-flex justify-content-end align-items-end flex-grow-1">
            <input type="submit" name="submit" id="submit" class="btn btn-primary mt-2 px-4 fw-semibold" value="Create product">
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // subcategory options
  const subcategories = { Protein:['Shake','Whey'], Performance:['Creatine','Preworkout'], Wellness:['Fishoil'] };
  const cat = document.getElementById('category');
  const sub = document.getElementById('subcategory');
  function updateSubs(v){ sub.innerHTML=''; (subcategories[v]||[]).forEach((s,i)=>{ const o=document.createElement('option'); o.value=s;o.textContent=s;if(i===0)o.selected=true; sub.appendChild(o); }); }
  cat.addEventListener('change',()=>updateSubs(cat.value));
  updateSubs(cat.value);

  // Drag & Drop uploader (single file)
  const input   = document.getElementById('image');
  const dz      = document.getElementById('dropzone');
  const browse  = document.getElementById('browseLink');
  const preview = document.getElementById('preview');
  const removeBtn = document.getElementById('removeBtn');
  const MAX = 5*1024*1024; // 5MB
  const OK  = ['image/jpeg','image/png','image/webp'];

  function setFile(file){
    if(!file) return;
    if(!OK.includes(file.type)){ alert('Only JPG, PNG, or WEBP allowed.'); return; }
    if(file.size>MAX){ alert('Image too large (max 5 MB).'); return; }
    // put file into the hidden input
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    // preview
    const r=new FileReader();
    r.onload=e=>{ preview.src=e.target.result; removeBtn.classList.remove('d-none'); };
    r.readAsDataURL(file);
  }

  browse.addEventListener('click',()=>input.click());
  dz.addEventListener('click',()=>input.click());

  ['dragenter','dragover'].forEach(ev=>{
    dz.addEventListener(ev,(e)=>{ e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); });
  });
  ['dragleave','drop'].forEach(ev=>{
    dz.addEventListener(ev,(e)=>{ e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); });
  });
  dz.addEventListener('drop',(e)=>{
    const file = e.dataTransfer.files && e.dataTransfer.files[0];
    setFile(file);
  });
  input.addEventListener('change',(e)=> setFile(e.target.files[0]));
  removeBtn.addEventListener('click',()=>{
    input.value='';
    preview.src='Assets/Graphics/product-placeholder.png';
    removeBtn.classList.add('d-none');
  });
</script>
</body>
</html>
