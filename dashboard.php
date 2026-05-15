<?php
session_start();
include 'connection.php';

$user_id = 1;

$stmt = $db->prepare("
SELECT shop_name, verification_status
FROM artisan_profiles
WHERE user_id = ?
LIMIT 1
");

$stmt->execute([$user_id]);

$artisan = $stmt->fetch(PDO::FETCH_ASSOC);

$shop_name = $artisan ? $artisan['shop_name'] : 'Maison Clay';
$verification_status = $artisan ? ucfirst($artisan['verification_status']) : 'Verified';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Maker dashboard — Artisan</title>
<meta name="description" content="Your artisan dashboard.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/dashboard.css">

<style>
  .dash-wrap{padding:42px 0 80px;grid-template-columns:260px 1fr;gap:40px}
  .dash-head{align-items:flex-start;margin-bottom:26px}
  .dash-head h1{font-size:34px !important}
  .kpis{grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px}
  .kpi{padding:28px;min-height:150px}
  .dash-grid{grid-template-columns:1.5fr .95fr;gap:24px;align-items:start}
  .panel{padding:28px}
  .panel h3{font-size:22px;margin-bottom:22px}
  .quick-actions{display:flex;flex-direction:column;gap:12px}
  .quick-actions .btn{padding:14px 18px}

  @media (max-width:900px){
    .dash-wrap,.dash-grid,.kpis{grid-template-columns:1fr}
    .dash-head{flex-direction:column;gap:14px}
  }
</style>
</head>

<body>
<header class="site-header">
  <div class="nav-inner">
    <a class="brand" href="index.php">Arti<span>san</span></a>

    <nav class="nav-links">
      <a href="index.php">About site</a>
      <a href="home.php">Home</a>
      <a href="shop.php">Shop</a>
      <a href="artists.php">Artisans</a>
      <a href="about.php">About</a>
    </nav>

    <div class="nav-actions">
      <a href="cart.php" class="btn btn-ghost btn-sm">Cart · 2</a>
      <a href="login.php" class="btn btn-outline btn-sm">Log in</a>
      <a href="signup.php" class="btn btn-accent btn-sm">Sign up</a>
    </div>
  </div>
</header>

<main>
<section class="container dash-wrap">

  <aside class="side">
    <div class="av"></div>

    <h3><?= htmlspecialchars($shop_name); ?></h3>

    <div class="role">Artisan · <?= htmlspecialchars($verification_status); ?></div>

    <nav>
      <a href="dashboard.php" class="active">Overview</a>
      <a href="dashboard-products.php">Products</a>
      <a href="dashboard-orders.php">Orders</a>
      <a href="dashboard-profile.php">Profile</a>
    </nav>
  </aside>

  <div>
    <div class="dash-head">
      <div>
        <h1>Welcome back, <?= htmlspecialchars($shop_name); ?></h1>
        <p class="muted">Here's how your shop is doing this month.</p>
      </div>

      <a href="dashboard-product-form.php" class="btn btn-accent">+ New product</a>
    </div>

    <div class="kpis">
      <div class="kpi">
        <div class="l">Revenue</div>
        <div class="v">€2,840</div>
        <div class="d">↑ 12% vs last month</div>
      </div>

      <div class="kpi alt">
        <div class="l">Orders</div>
        <div class="v">42</div>
        <div class="d">↑ 8 new this week</div>
      </div>

      <div class="kpi">
        <div class="l">Products live</div>
        <div class="v">18</div>
        <div class="d">2 low stock</div>
      </div>
    </div>

    <div class="dash-grid">
      <div class="panel">
        <h3>Recent orders</h3>

        <div class="recent">
          <div class="row">
            <div><strong>#ART-2842</strong> · Speckled stoneware bowl</div>
            <div><span class="badge badge-success">Shipped</span></div>
          </div>

          <div class="row">
            <div><strong>#ART-2839</strong> · Porcelain mug × 2</div>
            <div><span class="badge badge-warn">Processing</span></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <h3>Quick actions</h3>

        <div class="quick-actions">
          <a href="dashboard-product-form.php" class="btn btn-outline btn-block">Add a new product</a>
          <a href="dashboard-orders.php" class="btn btn-outline btn-block">Manage orders</a>
          <a href="dashboard-profile.php" class="btn btn-outline btn-block">Edit profile</a>
        </div>
      </div>
    </div>
  </div>

</section>
</main>

<footer class="site-footer">
  <div class="container">
    <div>
      <a class="brand" href="index.php">Arti<span>san</span></a>
      <p class="footer-tag">
        A cooperative marketplace for handmade pottery,
        textiles, jewelry, and woodwork from independent makers.
      </p>
    </div>
  </div>

  <div class="footer-bottom">
    © 2026 Artisan — Made with care for the makers.
  </div>
</footer>

</body>
</html>
