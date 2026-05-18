<?php
include 'connection.php';
include 'image-path.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 1);

$sql = "
    SELECT
        ap.user_id,
        ap.shop_name,
        ap.bio,
        ap.location,
        ap.avatar,
        ap.cover_photo,
        ap.member_since,
        COALESCE(product_count.total_products, 0) AS total_products,
        COALESCE(sold_count.total_sold, 0) AS total_sold
    FROM artisan_profiles ap
    LEFT JOIN (
        SELECT
            artisan_id,
            COUNT(product_id) AS total_products
        FROM products
        GROUP BY artisan_id
    ) AS product_count ON product_count.artisan_id = ap.user_id
    LEFT JOIN (
        SELECT
            artisan_id,
            SUM(quantity) AS total_sold
        FROM order_items
        GROUP BY artisan_id
    ) AS sold_count ON sold_count.artisan_id = ap.user_id
    WHERE ap.user_id = ?
    LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$artisan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artisan) {
    header("Location: artists.php");
    exit;
}

$product_sql = "
    SELECT
        p.product_id,
        p.title,
        p.price,
        (
            SELECT GROUP_CONCAT(pi.image_url ORDER BY pi.sort_order ASC, pi.image_id DESC SEPARATOR '||')
            FROM product_images pi
            WHERE pi.product_id = p.product_id
        ) AS image_urls
    FROM products p
    WHERE p.artisan_id = ?
    ORDER BY p.created_at DESC
";

$product_stmt = $db->prepare($product_sql);
$product_stmt->execute([$id]);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

$avatar_url = storedImageUrl($artisan['avatar'] ?? '');
$cover_url = storedImageUrl($artisan['cover_photo'] ?? '');
$location = $artisan['location'] ?: 'Bahrain';
$bio = $artisan['bio'] ?: $artisan['shop_name'] . ' creates handmade products with care and quality.';
$member_since = !empty($artisan['member_since']) ? date('Y', strtotime($artisan['member_since'])) : '2024';
$total_products = (int)($artisan['total_products'] ?? 0);
$total_sold = (int)($artisan['total_sold'] ?? 0);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($artisan['shop_name']); ?> — Artisan</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/artisan-profile.css">

<style>
  .cover img,
  .profile-av img,
  .p-thumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .cover{
    overflow:hidden;
  }

  .profile-av{
    overflow:hidden;
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
      <a href="artists.php" class="active">Artisans</a>
      <a href="about.php">About</a>
    </nav>
    <div class="nav-actions">
      <a href="cart.php" class="btn btn-ghost btn-sm">Cart · 2</a>
      <a href="login.html" class="btn btn-outline btn-sm">Log in</a>
      <a href="signup.html" class="btn btn-accent btn-sm">Sign up</a>
    </div>
  </div>
</header>

<main>

<div class="cover c<?= e($id); ?>">
  <?php if ($cover_url !== ''): ?>
    <img src="<?= e($cover_url); ?>" alt="<?= e($artisan['shop_name']); ?>">
  <?php endif; ?>
</div>

<div class="profile-head">
  <div class="profile-av a<?= e($id); ?>">
    <?php if ($avatar_url !== ''): ?>
      <img src="<?= e($avatar_url); ?>" alt="<?= e($artisan['shop_name']); ?>">
    <?php endif; ?>
  </div>

  <div class="profile-meta">
    <h1><?= e($artisan['shop_name']); ?></h1>
    <div class="loc">📍 <?= e($location); ?></div>
    <div class="profile-stats">
      <span><?= e($total_sold); ?> sold</span>
      <span><?= e($total_products); ?> products</span>
      <span>Member since <?= e($member_since); ?></span>
    </div>
  </div>

  <div>
    <a href="#" class="btn btn-accent">♡ Follow</a>
  </div>
</div>

<section class="container profile-content">

  <aside class="about">
    <h3>About the studio</h3>
    <p><?= e($bio); ?></p>

    <h3 style="margin-top:18px">Materials</h3>
    <p>High quality handmade materials.</p>

    <h3 style="margin-top:18px">Shipping</h3>
    <p>Ships within 2 days. Free shipping on selected orders.</p>
  </aside>

  <div>
    <h2 style="margin-bottom:18px">
      Products from <?= e($artisan['shop_name']); ?>
    </h2>

    <div class="product-grid">

      <?php foreach ($products as $product): ?>
        <?php $image_url = storedImageUrl($product['image_urls'] ?? ''); ?>

        <a href="product.php?id=<?= e($product['product_id']); ?>" class="p-card">

          <div class="p-thumb">
            <?php if ($image_url !== ''): ?>
              <img src="<?= e($image_url); ?>" alt="<?= e($product['title']); ?>">
            <?php endif; ?>
          </div>

          <div class="p-body">
            <div class="p-title"><?= e($product['title']); ?></div>
            <div class="p-price">€<?= e(number_format((float)$product['price'], 2)); ?></div>
          </div>

        </a>

      <?php endforeach; ?>

    </div>
  </div>

</section>

</main>

<footer class="site-footer">
  <div class="container">

    <div>
      <a class="brand" href="index.php">Arti<span>san</span></a>
      <p class="footer-tag">
        A cooperative marketplace for handmade pottery, textiles, jewelry, and woodwork from independent makers.
      </p>
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
      <a href="signup.html?role=artisan">Become a maker</a>
      <a href="dashboard.php">Maker dashboard</a>
    </div>

  </div>

  <div class="footer-bottom">© 2026 Artisan — Made with care for the makers.</div>
</footer>

</body>
</html>