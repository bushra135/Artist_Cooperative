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

function goToCart() {
    header("Location: cart.php");
    exit;
}

$cart_stmt = $db->prepare("
    SELECT cart_id
    FROM carts
    WHERE user_id = ?
    LIMIT 1
");
$cart_stmt->execute([$user_id]);
$cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);
$cart_id = $cart ? (int)$cart['cart_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cart_id > 0) {
    $cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $item_stmt = $db->prepare("
        SELECT cart_items.cart_item_id, cart_items.quantity
        FROM cart_items
        JOIN carts ON carts.cart_id = cart_items.cart_id
        WHERE cart_items.cart_item_id = ?
        AND carts.user_id = ?
        LIMIT 1
    ");
    $item_stmt->execute([$cart_item_id, $user_id]);
    $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $quantity = (int)$item['quantity'];

        if ($action === 'increase') {
            $update_stmt = $db->prepare("
                UPDATE cart_items
                SET quantity = quantity + 1
                WHERE cart_item_id = ?
            ");
            $update_stmt->execute([$cart_item_id]);
        } elseif ($action === 'decrease') {
            if ($quantity > 1) {
                $update_stmt = $db->prepare("
                    UPDATE cart_items
                    SET quantity = quantity - 1
                    WHERE cart_item_id = ?
                ");
                $update_stmt->execute([$cart_item_id]);
            } else {
                $delete_stmt = $db->prepare("
                    DELETE FROM cart_items
                    WHERE cart_item_id = ?
                ");
                $delete_stmt->execute([$cart_item_id]);
            }
        } elseif ($action === 'remove') {
            $delete_stmt = $db->prepare("
                DELETE FROM cart_items
                WHERE cart_item_id = ?
            ");
            $delete_stmt->execute([$cart_item_id]);
        }
    }

    goToCart();
}

$cart_items = [];

if ($cart_id > 0) {
    $items_stmt = $db->prepare("
        SELECT
            cart_items.cart_item_id,
            cart_items.quantity,
            products.product_id,
            products.title,
            products.material,
            products.price,
            artisan_shop.shop_name
        FROM cart_items
        JOIN products ON products.product_id = cart_items.product_id
        LEFT JOIN (
            SELECT user_id, MAX(shop_name) AS shop_name
            FROM artisan_profiles
            GROUP BY user_id
        ) AS artisan_shop ON artisan_shop.user_id = products.artisan_id
        WHERE cart_items.cart_id = ?
        ORDER BY cart_items.cart_item_id ASC
    ");
    $items_stmt->execute([$cart_id]);
    $cart_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$cart_count = 0;
$subtotal = 0;

foreach ($cart_items as $item) {
    $cart_count += (int)$item['quantity'];
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

$shipping = 0;
$total = $subtotal + $shipping;
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your cart — Artisan</title>
<meta name="description" content="Review items in your cart.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/cart.css">

<style>
  .qty-inline form{
    display:inline;
  }

  .qty-inline button{
    cursor:pointer;
  }

  .remove{
    background:none;
    border:0;
    padding:0;
    cursor:pointer;
    font:inherit;
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
      <a href="login.php" class="btn btn-outline btn-sm">Log in</a>
      <a href="signup.php" class="btn btn-accent btn-sm">Sign up</a>
    </div>
  </div>
</header>

<main>
<section class="container cart-wrap">
  <h1>Your cart</h1>

  <div class="cart-grid">
    <div class="cart-list">
      <?php if (count($cart_items) > 0): ?>
        <?php foreach ($cart_items as $index => $item): ?>
          <div class="cart-row">
            <div class="thumb <?= $index === 1 ? 't2' : ''; ?>"></div>

            <div>
              <h4><?= e($item['title']); ?></h4>
              <div class="m">
                <?= e($item['shop_name'] ?? 'Artisan'); ?>
                <?php if (!empty($item['material'])): ?>
                  · <?= e($item['material']); ?>
                <?php endif; ?>
              </div>

              <div class="qty-inline">
                <form method="post">
                  <input type="hidden" name="cart_item_id" value="<?= e($item['cart_item_id']); ?>">
                  <input type="hidden" name="action" value="decrease">
                  <button type="submit">-</button>
                </form>

                <span><?= e($item['quantity']); ?></span>

                <form method="post">
                  <input type="hidden" name="cart_item_id" value="<?= e($item['cart_item_id']); ?>">
                  <input type="hidden" name="action" value="increase">
                  <button type="submit">+</button>
                </form>
              </div>
            </div>

            <div>
              <div class="price">€<?= e(number_format((float)$item['price'] * (int)$item['quantity'], 2)); ?></div>

              <form method="post">
                <input type="hidden" name="cart_item_id" value="<?= e($item['cart_item_id']); ?>">
                <input type="hidden" name="action" value="remove">
                <button type="submit" class="remove">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="cart-row">
          <div>
            <h4>Your cart is empty</h4>
            <div class="m">Add handmade pieces from the shop to see them here.</div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <aside class="summary">
      <h3>Order summary</h3>

      <div class="line">
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

      <?php if (count($cart_items) > 0): ?>
        <a href="checkout.php" class="btn btn-accent btn-block btn-lg" style="margin-top:18px">Continue to checkout</a>
      <?php else: ?>
        <a href="shop.php" class="btn btn-accent btn-block btn-lg" style="margin-top:18px">Start shopping</a>
      <?php endif; ?>

      <a href="shop.php" class="btn btn-ghost btn-block" style="margin-top:8px">&larr; Keep shopping</a>
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
</body>
</html>
