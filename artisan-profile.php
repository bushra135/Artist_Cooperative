<?php
include 'connection.php';

$id = $_GET['id'] ?? 1;

$sql = "
SELECT ap.user_id, ap.shop_name
FROM artisan_profiles ap
WHERE ap.user_id = ?
";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$artisan = $stmt->fetch(PDO::FETCH_ASSOC);

$product_sql = "
SELECT p.product_id, p.title, p.price, MIN(pi.image_url) AS image_url
FROM products p
LEFT JOIN product_images pi ON p.product_id = pi.product_id
WHERE p.artisan_id = ?
GROUP BY p.product_id, p.title, p.price
";

$product_stmt = $db->prepare($product_sql);
$product_stmt->execute([$id]);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $artisan['shop_name']; ?> — Artisan</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/artisan-profile.css">
</head>

<body>
<header class="site-header">
  <div class="nav-inner">
    <a class="brand" href="index.html">Arti<span>san</span></a>
    <nav class="nav-links">
      <a href="index.html">About site</a>
      <a href="home.html">Home</a>
      <a href="shop.php">Shop</a>
      <a href="artists.php" class="active">Artisans</a>
      <a href="about.html">About</a>
    </nav>
    <div class="nav-actions">
      <a href="cart.php" class="btn btn-ghost btn-sm">Cart · 2</a>
      <a href="login.html" class="btn btn-outline btn-sm">Log in</a>
      <a href="signup.html" class="btn btn-accent btn-sm">Sign up</a>
    </div>
  </div>
</header>

<main>

<div class="cover c<?php echo $id; ?>"></div>

<div class="profile-head">
  <div class="profile-av a<?php echo $id; ?>"></div>

  <div class="profile-meta">
    <h1><?php echo $artisan['shop_name']; ?></h1>
    <div class="loc">📍 Bahrain · ★ 4.9 reviews</div>
    <div class="profile-stats">
      <span>142 sold</span>
      <span><?php echo count($products); ?> products</span>
      <span>Member since 2024</span>
    </div>
  </div>

  <div>
    <a href="#" class="btn btn-accent">♡ Follow</a>
  </div>
</div>

<section class="container profile-content">

  <aside class="about">
    <h3>About the studio</h3>
    <p><?php echo $artisan['shop_name']; ?> creates handmade products with care and quality.</p>

    <h3 style="margin-top:18px">Materials</h3>
    <p>High quality handmade materials.</p>

    <h3 style="margin-top:18px">Shipping</h3>
    <p>Ships within 2 days. Free shipping on selected orders.</p>
  </aside>

  <div>
    <h2 style="margin-bottom:18px">
      Products from <?php echo $artisan['shop_name']; ?>
    </h2>

    <div class="product-grid">

      <?php foreach ($products as $product) { ?>

        <a href="product.php?id=<?php echo $product['product_id']; ?>" class="p-card">

          <div class="p-thumb">
            <?php if (!empty($product['image_url'])) { ?>
              <img src="<?php echo $product['image_url']; ?>"
                   alt="<?php echo $product['title']; ?>"
                   style="width:100%; height:100%; object-fit:cover;">
            <?php } ?>
          </div>

          <div class="p-body">
            <div class="p-title"><?php echo $product['title']; ?></div>
            <div class="p-price">€<?php echo $product['price']; ?></div>
          </div>

        </a>

      <?php } ?>

    </div>
  </div>

</section>

</main>

<footer class="site-footer">
  <div class="container">

    <div>
      <a class="brand" href="index.html">Arti<span>san</span></a>
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