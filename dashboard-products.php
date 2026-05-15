<?php
session_start();
include 'connection.php';

$user_id = 1;

function e($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function productStatusLabel($product) {
  if ($product['status'] === 'draft') return 'Draft';
  if ((int)$product['stock'] <= 0) return 'Out of stock';
  if ((int)$product['stock'] <= 3) return 'Low stock';
  return 'Live';
}

function productBadgeClass($product) {
  if ($product['status'] === 'draft') return '';
  if ((int)$product['stock'] <= 0) return 'badge-danger';
  if ((int)$product['stock'] <= 3) return 'badge-warn';
  return 'badge-success';
}

$artisan_stmt = $db->prepare("
  SELECT user_id, shop_name, verification_status
  FROM artisan_profiles
  WHERE user_id = ?
  LIMIT 1
");
$artisan_stmt->execute([$user_id]);
$artisan = $artisan_stmt->fetch(PDO::FETCH_ASSOC);

$shop_name = $artisan ? $artisan['shop_name'] : 'Maison Clay';
$verification_status = $artisan ? ucfirst($artisan['verification_status']) : 'Verified';
$artisan_id = $artisan ? (int)$artisan['user_id'] : $user_id;

$products_stmt = $db->prepare("
  SELECT products.product_id, products.title, products.price, products.stock,
         products.stock_status, products.status, categories.name AS category_name
  FROM products
  LEFT JOIN categories ON categories.category_id = products.category_id
  WHERE products.artisan_id = ?
  ORDER BY products.created_at DESC
");
$products_stmt->execute([$artisan_id]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_products = count($products);
$low_stock_count = 0;

foreach ($products as $product) {
  if ((int)$product['stock'] > 0 && (int)$product['stock'] <= 3) {
    $low_stock_count++;
  }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My products — Artisan</title>
<meta name="description" content="Manage your product listings.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/dash-products.css">
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
      <a href="dashboard.php">Overview</a>
      <a href="dashboard-products.php" class="active">Products</a>
      <a href="dashboard-orders.php">Orders</a>
      <a href="dashboard-profile.php">Profile</a>
    </nav>
  </aside>

  <div>
    <div class="head">
      <div>
        <h1 style="font-size:30px">My products</h1>
        <p class="muted"><?= e($total_products); ?> products · <?= e($low_stock_count); ?> low stock</p>
      </div>
      <a href="dashboard-product-form.php" class="btn btn-accent">+ New product</a>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th></th>
          <th>Product</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>

      <tbody>
        <?php if (count($products) > 0): ?>
          <?php foreach ($products as $product): ?>
            <tr>
              <td><div class="thumb" style="background:#8AA38B"></div></td>
              <td>
                <strong><?= e($product['title']); ?></strong><br>
                <span class="muted" style="font-size:12px"><?= e($product['category_name'] ?? 'Uncategorized'); ?></span>
              </td>
              <td>€<?= e(number_format((float)$product['price'], 2)); ?></td>
              <td><?= e($product['stock']); ?></td>
              <td>
                <span class="badge <?= e(productBadgeClass($product)); ?>">
                  <?= e(productStatusLabel($product)); ?>
                </span>
              </td>
              <td>
                <a href="dashboard-product-form.php?id=<?= e($product['product_id']); ?>" class="btn btn-ghost btn-sm">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="muted">No products yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
</main>

<footer class="site-footer">
  <div class="container">
    <div>
      <a class="brand" href="index.php">Arti<span>san</span></a>
      <p class="footer-tag">A cooperative marketplace for handmade pottery, textiles, jewelry, and woodwork from independent makers.</p>
    </div>
    <div>
      <h4>Shop</h4>
      <a href="shop.php">All products</a>
      <a href="shop.php?cat=ceramics">Ceramics</a>
      <a href="shop.php?cat=textiles">Textiles</a>
      <a href="shop.php?cat=jewelry">Jewelry</a>
    </div>
    <div>
      <h4>Makers</h4>
      <a href="artists.php">All artisans</a>
      <a href="signup.php?role=artisan">Become a maker</a>
      <a href="dashboard.php">Maker dashboard</a>
    </div>
    <div>
      <h4>Company</h4>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="#">Help center</a>
    </div>
  </div>
  <div class="footer-bottom">© 2026 Artisan — Made with care for the makers.</div>
</footer>
</body>
</html>
