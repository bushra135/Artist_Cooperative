<?php
session_start();

$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'customer';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
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

$orders_stmt = $db->prepare("
    SELECT
        orders.order_id,
        orders.status,
        orders.total,
        orders.placed_at
    FROM orders
    WHERE orders.customer_id = ?
    ORDER BY orders.placed_at DESC
");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

$items_stmt = $db->prepare("
    SELECT products.title, order_items.quantity
    FROM order_items
    LEFT JOIN products ON products.product_id = order_items.product_id
    WHERE order_items.order_id = ?
    ORDER BY order_items.order_item_id ASC
");
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My orders — Artisan</title>
<meta name="description" content="Track your past and current orders.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/orders.css">
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
<section class="container orders-wrap">
  <h1>My orders</h1>

  <?php if (count($orders) > 0): ?>
    <?php foreach ($orders as $order): ?>
      <?php
        $items_stmt->execute([$order['order_id']]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <div class="order-card">
        <div class="order-head">
          <div>
            <div class="id">#ART-<?= e(str_pad($order['order_id'], 4, '0', STR_PAD_LEFT)); ?></div>
            <div class="date">Placed <?= e(date('F j, Y', strtotime($order['placed_at']))); ?></div>
          </div>
          <span class="badge <?= e(badgeClass($order['status'])); ?>"><?= e(statusLabel($order['status'])); ?></span>
        </div>

        <div class="order-items">
          <?php if (count($items) > 0): ?>
            <?php foreach ($items as $index => $item): ?>
              <div class="it">
                <div class="sw <?= $index === 1 ? 't2' : ($index === 2 ? 't3' : ''); ?>"></div>
                <?= e($item['title'] ?? 'Product'); ?> × <?= e($item['quantity']); ?>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="it">
              <div class="sw"></div>
              No items found for this order
            </div>
          <?php endif; ?>
        </div>

        <div class="order-foot">
          <span class="total">€<?= e(number_format((float)$order['total'], 2)); ?></span>
          <a href="order-detail.php?id=<?= e($order['order_id']); ?>" class="btn btn-outline btn-sm">View order →</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="order-card">
      <div class="order-head">
        <div>
          <div class="id">No orders yet</div>
          <div class="date">Your orders will appear here after checkout.</div>
        </div>
      </div>
    </div>
  <?php endif; ?>
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
