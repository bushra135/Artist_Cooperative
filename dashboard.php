<?php
session_start();

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'artisan';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'artisan') {
    header("Location: login.php");
    exit;
}

include 'connection.php';

$user_id = (int)$_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusLabel($status) {
    $labels = [
        'paid' => 'Paid',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];

    return $labels[$status] ?? ucfirst((string)$status);
}

function badgeClass($status) {
    $classes = [
        'paid' => '',
        'processing' => 'badge-warn',
        'shipped' => 'badge-success',
        'delivered' => 'badge-sage',
        'cancelled' => 'badge-danger'
    ];

    return $classes[$status] ?? '';
}

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

$revenue_stmt = $db->prepare("
    SELECT COALESCE(SUM(quantity * unit_price), 0)
    FROM order_items
    WHERE artisan_id = ?
");
$revenue_stmt->execute([$user_id]);
$revenue = (float)$revenue_stmt->fetchColumn();

$orders_count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT order_id)
    FROM order_items
    WHERE artisan_id = ?
");
$orders_count_stmt->execute([$user_id]);
$orders_count = (int)$orders_count_stmt->fetchColumn();

$products_live_stmt = $db->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE artisan_id = ?
    AND status = 'live'
");
$products_live_stmt->execute([$user_id]);
$products_live = (int)$products_live_stmt->fetchColumn();

$low_stock_stmt = $db->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE artisan_id = ?
    AND stock <= 5");
$low_stock_stmt->execute([$user_id]);
$low_stock = (int)$low_stock_stmt->fetchColumn();

$recent_orders_stmt = $db->prepare("
    SELECT
        orders.order_id,
        orders.status,
        orders.placed_at,
        (
            SELECT GROUP_CONCAT(
                CONCAT(COALESCE(products.title, 'Product'), ' × ', item_titles.quantity)
                ORDER BY item_titles.order_item_id ASC
                SEPARATOR ', '
            )
            FROM order_items item_titles
            JOIN products ON products.product_id = item_titles.product_id
            WHERE item_titles.order_id = orders.order_id
            AND item_titles.artisan_id = ?
        ) AS order_items_text
    FROM orders
    WHERE EXISTS (
        SELECT 1
        FROM order_items
        WHERE order_items.order_id = orders.order_id
        AND order_items.artisan_id = ?
    )
    ORDER BY orders.placed_at DESC
    LIMIT 3
");
$recent_orders_stmt->execute([$user_id, $user_id]);
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
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
  .dash-wrap{
    padding:42px 0 80px;
    grid-template-columns:260px 1fr;
    gap:40px;
  }

  .dash-head{
    align-items:flex-start;
    margin-bottom:26px;
  }

  .dash-head h1{
    font-size:34px !important;
  }

  .kpis{
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:28px;
  }

  .kpi{
    padding:28px;
    min-height:150px;
  }

  .dash-grid{
    grid-template-columns:1.5fr .95fr;
    gap:24px;
    align-items:start;
  }

  .panel{
    padding:28px;
  }

  .panel h3{
    font-size:22px;
    margin-bottom:22px;
  }

  .quick-actions{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .quick-actions .btn{
    padding:14px 18px;
  }

  @media (max-width:900px){
    .dash-wrap,
    .dash-grid,
    .kpis{
      grid-template-columns:1fr;
    }

    .dash-head{
      flex-direction:column;
      gap:14px;
    }
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

    <h3><?= e($shop_name); ?></h3>

    <div class="role">Artisan · <?= e($verification_status); ?></div>

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
        <h1>Welcome back, <?= e($shop_name); ?></h1>
        <p class="muted">Here's how your shop is doing this month.</p>
      </div>

      <a href="dashboard-product-form.php" class="btn btn-accent">
        + New product
      </a>
    </div>

    <div class="kpis">

      <div class="kpi">
        <div class="l">Revenue</div>
        <div class="v">€<?= e(number_format($revenue, 2)); ?></div>
        <div class="d">From your order items</div>
      </div>

      <div class="kpi alt">
        <div class="l">Orders</div>
        <div class="v"><?= e($orders_count); ?></div>
        <div class="d">Orders containing your products</div>
      </div>

      <div class="kpi">
        <div class="l">Products live</div>
        <div class="v"><?= e($products_live); ?></div>
        <div class="d"><?= e($low_stock); ?> low stock</div>
      </div>

    </div>

    <div class="dash-grid">

      <div class="panel">
        <h3>Recent orders</h3>

        <div class="recent">
          <?php if (count($recent_orders) > 0): ?>
            <?php foreach ($recent_orders as $order): ?>
              <div class="row">
                <div>
                  <strong>#ART-<?= e(str_pad($order['order_id'], 4, '0', STR_PAD_LEFT)); ?></strong>
                  · <?= e($order['order_items_text'] ?? 'Product'); ?>
                </div>

                <div>
                  <span class="badge <?= e(badgeClass($order['status'])); ?>">
                    <?= e(statusLabel($order['status'])); ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="row">
              <div>No recent orders yet</div>
              <div><span class="badge">Empty</span></div>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <div class="panel">
        <h3>Quick actions</h3>

        <div class="quick-actions">
          <a href="dashboard-product-form.php" class="btn btn-outline btn-block">
            Add a new product
          </a>

          <a href="dashboard-orders.php" class="btn btn-outline btn-block">
            Manage orders
          </a>

          <a href="dashboard-profile.php" class="btn btn-outline btn-block">
            Edit profile
          </a>
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