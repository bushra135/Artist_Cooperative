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
$order_id = (int)($_GET['id'] ?? 0);

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

function stepDone($current_status, $step) {
    if ($current_status === 'cancelled') {
        return false;
    }

    $order = [
        'paid' => 1,
        'processing' => 2,
        'shipped' => 3,
        'delivered' => 4
    ];

    return ($order[$current_status] ?? 0) >= ($order[$step] ?? 0);
}

$order = null;
$items = [];
$can_view = false;
$back_url = 'orders.php';
$back_label = 'My orders';

if ($order_id > 0) {
    $order_stmt = $db->prepare("
        SELECT
            orders.order_id,
            orders.customer_id,
            orders.status,
            orders.subtotal,
            orders.shipping_fee,
            orders.total,
            orders.shipping_address,
            orders.payment_method,
            orders.tracking_number,
            orders.placed_at,
            users.full_name AS customer_name,
            users.email AS customer_email,
            users.city_country AS customer_location
        FROM orders
        LEFT JOIN users ON users.user_id = orders.customer_id
        WHERE orders.order_id = ?
        LIMIT 1
    ");
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $artisan_check = $db->prepare("
            SELECT COUNT(*)
            FROM order_items
            WHERE order_id = ?
            AND artisan_id = ?
        ");
        $artisan_check->execute([$order_id, $user_id]);

        $is_artisan_order = (int)$artisan_check->fetchColumn() > 0;
        $is_customer_order = (int)$order['customer_id'] === $user_id;

        $can_view = $is_customer_order || $is_artisan_order;

        if ($is_artisan_order && !$is_customer_order) {
            $back_url = 'dashboard-orders.php';
            $back_label = 'Orders';
        }

        if ($can_view) {
            $items_stmt = $db->prepare("
                SELECT
                    oi.order_item_id,
                    oi.quantity,
                    oi.unit_price,
                    products.title,
                    users.full_name AS artisan_full_name,
                    artisan_profiles.shop_name
                FROM order_items oi
                LEFT JOIN products ON products.product_id = oi.product_id
                LEFT JOIN (
                    SELECT user_id, MAX(shop_name) AS shop_name
                    FROM artisan_profiles
                    GROUP BY user_id
                ) AS artisan_profiles ON artisan_profiles.user_id = oi.artisan_id
                LEFT JOIN users ON users.user_id = artisan_profiles.user_id
                WHERE oi.order_id = ?
                ORDER BY oi.order_item_id ASC
            ");
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$item_count = 0;
foreach ($items as $item) {
    $item_count += (int)$item['quantity'];
}

$subtotal = $order ? (float)$order['subtotal'] : 0;
$shipping_fee = $order ? (float)$order['shipping_fee'] : 0;
$total = $subtotal + $shipping_fee;
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Order #ART-<?= e(str_pad($order_id, 4, '0', STR_PAD_LEFT)); ?> — Artisan</title>
<meta name="description" content="Order detail.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/order-detail.css">
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
<section class="container detail-wrap">
  <?php if (!$order || !$can_view): ?>
    <nav class="crumbs"><a href="orders.php">My orders</a> / Order not found</nav>

    <div class="block">
      <h1>Order not found</h1>
      <p class="muted">This order does not exist or is not linked to your account.</p>
      <a href="orders.php" class="btn btn-outline btn-sm">Back to orders</a>
    </div>
  <?php else: ?>
    <?php if (isset($_GET['placed']) && $_GET['placed'] === '1'): ?>
      <div class="badge badge-success" style="margin-bottom:16px">Order placed successfully.</div>
    <?php endif; ?>

    <nav class="crumbs">
      <a href="<?= e($back_url); ?>"><?= e($back_label); ?></a>
      / Order #ART-<?= e(str_pad($order['order_id'], 4, '0', STR_PAD_LEFT)); ?>
    </nav>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
      <div>
        <h1>Order #ART-<?= e(str_pad($order['order_id'], 4, '0', STR_PAD_LEFT)); ?></h1>
        <p class="muted">
          Placed <?= e(date('F j, Y', strtotime($order['placed_at']))); ?> ·
          <?= e($item_count); ?> <?= $item_count === 1 ? 'item' : 'items'; ?>
        </p>
      </div>

      <span class="badge <?= e(badgeClass($order['status'])); ?>">
        <?= e(statusLabel($order['status'])); ?>
      </span>
    </div>

    <div class="detail-grid">
      <div>
        <div class="block">
          <h3>Status</h3>

          <div class="timeline">
            <div class="tl-step done"><div class="tl-dot"></div><span>Placed</span></div>
            <div class="tl-step <?= stepDone($order['status'], 'paid') ? 'done' : ''; ?>"><div class="tl-dot"></div><span>Paid</span></div>
            <div class="tl-step <?= stepDone($order['status'], 'processing') ? 'done' : ''; ?>"><div class="tl-dot"></div><span>Processing</span></div>
            <div class="tl-step <?= stepDone($order['status'], 'shipped') ? 'done' : ''; ?>"><div class="tl-dot"></div><span>Shipped</span></div>
            <div class="tl-step <?= stepDone($order['status'], 'delivered') ? 'done' : ''; ?>"><div class="tl-dot"></div><span>Delivered</span></div>
          </div>

          <p class="muted" style="font-size:13px;text-align:center">
            Tracking:
            <?= $order['tracking_number'] ? e($order['tracking_number']) : 'Not available yet'; ?>
          </p>
        </div>

        <div class="block">
          <h3>Items</h3>

          <?php if (count($items) > 0): ?>
            <?php foreach ($items as $index => $item): ?>
              <div class="item">
                <div class="sw <?= $index === 1 ? 't2' : ($index === 2 ? 't3' : ''); ?>"></div>

                <div style="flex:1">
                  <div style="font-weight:600"><?= e($item['title'] ?? 'Product'); ?></div>
                  <div class="muted" style="font-size:13px">
                    <?= e($item['artisan_full_name'] ?? 'Artisan'); ?> · <?= e($item['shop_name'] ?? 'Shop'); ?> × <?= e($item['quantity']); ?>
                  </div>
                </div>

                <div style="font-weight:600;color:var(--accent)">
                  €<?= e(number_format((float)$item['unit_price'], 2)); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="muted">No items found for this order.</p>
          <?php endif; ?>
        </div>
      </div>

      <aside>
        <div class="block">
          <h3>Summary</h3>

          <div class="line">
            <span>Subtotal</span>
            <span>€<?= e(number_format($subtotal, 2)); ?></span>
          </div>

          <div class="line">
            <span>Shipping</span>
            <span><?= $shipping_fee > 0 ? '€' . e(number_format($shipping_fee, 2)) : 'Free'; ?></span>
          </div>

          <div class="line total">
            <span>Total</span>
            <span>€<?= e(number_format($total, 2)); ?></span>
          </div>
        </div>

        <div class="block">
          <h3>Shipping to</h3>
          <p style="font-size:14px;line-height:1.6">
            <?= e($order['customer_name'] ?? 'Customer'); ?><br>
            <?= nl2br(e($order['shipping_address'] ?: ($order['customer_location'] ?? 'No shipping address saved'))); ?>
          </p>
        </div>

        <div class="block">
          <h3>Payment</h3>
          <p style="font-size:14px;line-height:1.6">
            <?= e($order['payment_method'] ?: 'Not specified'); ?>
          </p>
        </div>
      </aside>
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


