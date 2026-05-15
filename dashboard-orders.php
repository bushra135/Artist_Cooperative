<?php
session_start();
include 'connection.php';

$user_id = 1; // Temporary until login sessions are ready
$error = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusLabel($status) {
    $labels = [
        'paid' => 'Paid',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered'
    ];

    return $labels[$status] ?? ucfirst((string)$status);
}

function statusClass($status) {
    $classes = [
        'paid' => 'status-paid',
        'processing' => 'status-processing',
        'shipped' => 'status-shipped',
        'delivered' => 'status-delivered'
    ];

    return $classes[$status] ?? 'status-paid';
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

$allowed_statuses = ['paid', 'processing', 'shipped', 'delivered'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if ($order_id > 0 && in_array($new_status, $allowed_statuses, true)) {
        $check_stmt = $db->prepare("
            SELECT COUNT(*)
            FROM order_items
            WHERE order_id = ?
            AND artisan_id = ?
        ");
        $check_stmt->execute([$order_id, $artisan_id]);
        $belongs_to_artisan = (int)$check_stmt->fetchColumn();

        if ($belongs_to_artisan > 0) {
            $update_stmt = $db->prepare("
                UPDATE orders
                SET status = ?
                WHERE order_id = ?
            ");
            $update_stmt->execute([$new_status, $order_id]);

            header("Location: dashboard-orders.php");
            exit;
        }
    }

    $error = 'Could not update order status.';
}

$status_filter = $_GET['status'] ?? 'all';
$params = [$artisan_id];

$orders_sql = "
    SELECT
        orders.order_id,
        orders.status,
        orders.total,
        orders.placed_at,
        users.full_name AS customer_name,
        SUM(order_items.quantity) AS item_count
    FROM orders
    JOIN order_items ON order_items.order_id = orders.order_id
    LEFT JOIN users ON users.user_id = orders.customer_id
    WHERE order_items.artisan_id = ?
";

if ($status_filter !== 'all' && in_array($status_filter, $allowed_statuses, true)) {
    $orders_sql .= " AND orders.status = ?";
    $params[] = $status_filter;
}

$orders_sql .= "
    GROUP BY orders.order_id, orders.status, orders.total, orders.placed_at, users.full_name
    ORDER BY orders.placed_at DESC
";

$orders_stmt = $db->prepare($orders_sql);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop orders — Artisan</title>
<meta name="description" content="Manage incoming orders.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/dash-products.css">

<style>
  .status-form{
    margin:0;
  }

  .status-select{
    width:auto;
    font-weight:600;
  }

  .status-select.status-paid{
    background:var(--muted);
    color:var(--fg);
    border-color:var(--border);
  }

  .status-select.status-processing{
    background:var(--warning);
    color:var(--fg);
    border-color:var(--warning);
  }

  .status-select.status-shipped{
    background:var(--success);
    color:#fff;
    border-color:var(--success);
  }

  .status-select.status-delivered{
    background:var(--secondary);
    color:var(--fg);
    border-color:var(--secondary);
  }

  .status-select option[value="paid"]{
    background:var(--muted);
    color:var(--fg);
  }

  .status-select option[value="processing"]{
    background:var(--warning);
    color:var(--fg);
  }

  .status-select option[value="shipped"]{
    background:var(--success);
    color:#fff;
  }

  .status-select option[value="delivered"]{
    background:var(--secondary);
    color:var(--fg);
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
      <a href="dashboard.php">Overview</a>
      <a href="dashboard-products.php">Products</a>
      <a href="dashboard-orders.php" class="active">Orders</a>
      <a href="dashboard-profile.php">Profile</a>
    </nav>
  </aside>

  <div>
    <div class="head">
      <h1 style="font-size:30px">Orders</h1>

      <form method="get">
        <select class="select" name="status" style="width:auto" onchange="this.form.submit()">
          <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>All statuses</option>
          <option value="paid" <?= $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
          <option value="processing" <?= $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
          <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
          <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
        </select>
      </form>
    </div>

    <?php if ($error): ?>
      <div class="badge badge-danger" style="margin-bottom:16px"><?= e($error); ?></div>
    <?php endif; ?>

    <table class="table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>View</th>
          <th>Status</th>
        </tr>
      </thead>

      <tbody>
        <?php if (count($orders) > 0): ?>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>
                <strong>#ART-<?= e(str_pad($order['order_id'], 4, '0', STR_PAD_LEFT)); ?></strong><br>
                <span class="muted" style="font-size:12px"><?= e(date('M j', strtotime($order['placed_at']))); ?></span>
              </td>
              <td><?= e($order['customer_name'] ?? 'Customer'); ?></td>
              <td><?= e($order['item_count']); ?></td>
              <td>€<?= e(number_format((float)$order['total'], 2)); ?></td>
              <td>
                <a href="order-detail.php?id=<?= e($order['order_id']); ?>" class="btn btn-ghost btn-sm">View</a>
              </td>
              <td>
                <form method="post" class="status-form">
                  <input type="hidden" name="order_id" value="<?= e($order['order_id']); ?>">

                  <select class="select status-select <?= e(statusClass($order['status'])); ?>" name="status" onchange="this.form.submit()">
                    <?php foreach ($allowed_statuses as $status): ?>
                      <option value="<?= e($status); ?>" <?= $order['status'] === $status ? 'selected' : ''; ?>>
                        <?= e(statusLabel($status)); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="muted">No orders found.</td>
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


