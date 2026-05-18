<?php
session_start();

$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'customer';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: login.html");
    exit;
}

include 'connection.php';
include 'image-path.php';

$user_id = (int)$_SESSION['user_id'];
$error = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function goToOrderDetail($order_id) {
    header("Location: order-detail.php?id=" . $order_id . "&placed=1");
    exit;
}

$user_stmt = $db->prepare("
    SELECT city_country
    FROM users
    WHERE user_id = ?
    LIMIT 1
");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$city_country = $user['city_country'] ?? '';

$cart_stmt = $db->prepare("
    SELECT cart_id
    FROM carts
    WHERE user_id = ?
    LIMIT 1
");
$cart_stmt->execute([$user_id]);
$cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);
$cart_id = $cart ? (int)$cart['cart_id'] : 0;

$cart_items = [];

if ($cart_id > 0) {
    $items_stmt = $db->prepare("
        SELECT
            cart_items.cart_item_id,
            cart_items.quantity,
            products.product_id,
            products.artisan_id,
            products.title,
            products.price,
            users.full_name AS artisan_full_name,
            artisan_profiles.shop_name,
            (
                SELECT GROUP_CONCAT(product_images.image_url ORDER BY product_images.sort_order ASC, product_images.image_id DESC SEPARATOR '||')
                FROM product_images
                WHERE product_images.product_id = products.product_id
            ) AS image_urls
        FROM cart_items
        JOIN products ON products.product_id = cart_items.product_id
        LEFT JOIN (
            SELECT user_id, MAX(shop_name) AS shop_name
            FROM artisan_profiles
            GROUP BY user_id
        ) AS artisan_profiles ON artisan_profiles.user_id = products.artisan_id
        LEFT JOIN users ON users.user_id = artisan_profiles.user_id
        WHERE cart_items.cart_id = ?
        ORDER BY cart_items.cart_item_id ASC
    ");
    $items_stmt->execute([$cart_id]);
    $cart_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$cart_count = 0;
$subtotal = 0;

foreach ($cart_items as $item) {
    $quantity = (int)$item['quantity'];
    $cart_count += $quantity;
    $subtotal += (float)$item['price'] * $quantity;
}

$shipping = 0;
$total = $subtotal + $shipping;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'card');

    if (count($cart_items) === 0) {
        $error = 'Your cart is empty.';
    } elseif ($address === '' || $city === '' || $country === '') {
        $error = 'Please fill in all checkout fields.';
    } else {
        $shipping_address = $address . "\n" .
            $city . ($postal_code !== '' ? ', ' . $postal_code : '') . "\n" .
            $country;

        try {
            $db->beginTransaction();
            $tracking_number = 'TRK' . date('YmdHis');

            $order_stmt = $db->prepare("
                INSERT INTO orders
                (customer_id, status, subtotal, shipping_fee, total, shipping_address, payment_method, tracking_number, placed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $order_stmt->execute([
                $user_id,
                'processing',
                $subtotal,
                $shipping,
                $total,
                $shipping_address,
                $payment_method,
                $tracking_number
            ]);

            $order_id = (int)$db->lastInsertId();

            $order_item_stmt = $db->prepare("
                INSERT INTO order_items
                (order_id, product_id, artisan_id, quantity, unit_price)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                $order_item_stmt->execute([
                    $order_id,
                    (int)$item['product_id'],
                    (int)$item['artisan_id'],
                    (int)$item['quantity'],
                    (float)$item['price']
                ]);
            }

            $clear_stmt = $db->prepare("
                DELETE FROM cart_items
                WHERE cart_id = ?
            ");
            $clear_stmt->execute([$cart_id]);

            $db->commit();
            goToOrderDetail($order_id);
        } catch (PDOException $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $error = $ex->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout — Artisan</title>
<meta name="description" content="Complete your order.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/checkout.css">

<style>
  .summary .item .sw{
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .summary .item .sw img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    border-radius:6px;
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
      <a href="cart.php" class="btn btn-ghost btn-sm">Cart · <?= e($cart_count); ?></a>
      <a href="login.html" class="btn btn-outline btn-sm">Log in</a>
      <a href="signup.php" class="btn btn-accent btn-sm">Sign up</a>
    </div>
  </div>
</header>

<main>
<section class="container checkout-wrap">
  <h1>Checkout</h1>

  <?php if ($error): ?>
    <div class="badge badge-danger" style="margin-bottom:16px"><?= e($error); ?></div>
  <?php endif; ?>

  <div class="checkout-grid">
    <form method="post">
      <div class="section-block">
        <h3>1. Shipping address</h3>

        <div class="field">
          <label>Address</label>
          <input class="input" name="address" placeholder="Street and number" required>
        </div>

        <div class="row-2">
          <div class="field">
            <label>City</label>
            <input class="input" name="city" value="<?= e($city_country); ?>" required>
          </div>

          <div class="field">
            <label>Postal code</label>
            <input class="input" name="postal_code">
          </div>
        </div>

        <div class="field">
          <label>Country</label>
          <select class="select" name="country" required>
            <option value="">Choose country</option>
            <option value="Bahrain">Bahrain</option>
            <option value="France">France</option>
            <option value="Belgium">Belgium</option>
            <option value="Germany">Germany</option>
          </select>
        </div>
      </div>

      <div class="section-block">
        <h3>2. Payment</h3>

        <div class="pay-method">
          <label><input type="radio" name="payment_method" value="card" checked> Card</label>
          <label><input type="radio" name="payment_method" value="paypal"> PayPal</label>
          <label><input type="radio" name="payment_method" value="apple_pay"> Apple Pay</label>
          <label><input type="radio" name="payment_method" value="cash"> Cash</label>
        </div>

        <div id="card-fields">
          <div class="field" style="margin-top:16px">
            <label>Card number</label>
            <input class="input" placeholder="1234 1234 1234 1234">
          </div>

          <div class="row-2">
            <div class="field">
              <label>Expiry</label>
              <input class="input" placeholder="MM / YY">
            </div>

            <div class="field">
              <label>CVC</label>
              <input class="input" placeholder="123">
            </div>
          </div>
        </div>
      </div>

      <?php if (count($cart_items) > 0): ?>
        <button class="btn btn-accent btn-lg btn-block" type="submit">Place order — €<?= e(number_format($total, 2)); ?></button>
      <?php else: ?>
        <a href="shop.php" class="btn btn-accent btn-lg btn-block">Start shopping</a>
      <?php endif; ?>
    </form>

    <aside class="summary">
      <h3>Your order</h3>

      <?php if (count($cart_items) > 0): ?>
        <?php foreach ($cart_items as $index => $item): ?>
          <?php
            $image_url = storedImageUrl($item['image_urls'] ?? '');
            $has_image = $image_url !== '';
          ?>

          <div class="item">
            <div class="sw <?= !$has_image && $index === 1 ? 't2' : ''; ?>">
              <?php if ($has_image): ?>
                <img src="<?= e($image_url); ?>" alt="<?= e($item['title']); ?>">
              <?php endif; ?>
            </div>

            <div class="name">
              <div><?= e($item['title']); ?></div>
              <div class="m">
                <?= e($item['artisan_full_name'] ?? 'Artisan'); ?> ·
                <?= e($item['shop_name'] ?? 'Shop'); ?> ×
                <?= e($item['quantity']); ?>
              </div>
            </div>

            <div>€<?= e(number_format((float)$item['price'] * (int)$item['quantity'], 2)); ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="muted">Your cart is empty.</p>
      <?php endif; ?>

      <div class="line" style="margin-top:14px">
        <span>Subtotal</span>
        <span>€<?= e(number_format($subtotal, 2)); ?></span>
      </div>

      <div class="line">
        <span>Shipping</span>
        <span><?= $shipping > 0 ? '€' . e(number_format($shipping, 2)) : 'Free'; ?></span>
      </div>

      <div class="line total">
        <span>Total</span>
        <span>€<?= e(number_format($total, 2)); ?></span>
      </div>
    </aside>
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

<script>
  function toggleCardFields() {
    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
    const cardFields = document.getElementById('card-fields');

    if (!selectedPayment || !cardFields) {
      return;
    }

    cardFields.style.display = selectedPayment.value === 'card' ? 'block' : 'none';
  }

  document.querySelectorAll('input[name="payment_method"]').forEach(function(input) {
    input.addEventListener('change', toggleCardFields);
  });

  toggleCardFields();
</script>
</body>
</html>