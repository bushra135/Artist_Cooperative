<?php
include 'connection.php';

$id = $_GET['id'] ?? 1;

$sql = "
SELECT p.*, ap.shop_name, MIN(pi.image_url) AS image_url
FROM products p
JOIN artisan_profiles ap ON p.artisan_id = ap.user_id
LEFT JOIN product_images pi ON p.product_id = pi.product_id
WHERE p.product_id = ?
GROUP BY p.product_id
";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$image_stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ?");
$image_stmt->execute([$id]);
$product_images = $image_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $product['title']; ?> — Artisan</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/product.css">
</head>

<body>

<header class="site-header">
  <div class="nav-inner">
    <a class="brand" href="index.html">Arti<span>san</span></a>

    <nav class="nav-links">
      <a href="index.html">About site</a>
      <a href="home.html">Home</a>
      <a href="shop.php" class="active">Shop</a>
      <a href="artists.php">Artisans</a>
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

<div class="container">

<nav class="crumbs">
  <a href="shop.php">Shop</a> / <?php echo $product['title']; ?>
</nav>

<div class="product">

  <div class="gallery">

    <div class="main">
      <?php if (!empty($product['image_url'])) { ?>
        <img id="mainImage"
             src="<?php echo $product['image_url']; ?>"
             alt="<?php echo $product['title']; ?>"
             style="width:100%; height:100%; object-fit:cover; border-radius:18px;">
      <?php } ?>
    </div>

    <div class="thumbs">
      <?php foreach ($product_images as $img) { ?>
        <img src="<?php echo $img['image_url']; ?>"
             onclick="changeImage(this.src)"
             style="width:80px; height:80px; object-fit:cover; border-radius:10px; cursor:pointer;">
      <?php } ?>
    </div>

  </div>

  <div class="product-info">

    <a href="artisan-profile.php?id=<?php echo $product['artisan_id']; ?>" class="maker-link">
      <?php echo $product['shop_name']; ?> →
    </a>

    <h1><?php echo $product['title']; ?></h1>

    <div class="price">€<?php echo $product['price']; ?></div>

    <div class="meta">
      <span class="badge badge-success">In stock</span>
      <span class="badge">Ships in 2 days</span>
      <span class="badge badge-outline">One of 12 made</span>
    </div>

    <p class="desc"><?php echo $product['description']; ?></p>

    <div class="qty">
      <button>−</button>
      <span class="n">1</span>
      <button>+</button>
    </div>

    <div class="actions">
      <a href="cart.php" class="btn btn-accent btn-lg">
        Add to cart — €<?php echo $product['price']; ?>
      </a>
      <button class="btn btn-outline btn-lg">♡ Save</button>
    </div>

    <div class="specs">
      <dl>
        <dt>Material</dt>
        <dd><?php echo $product['material']; ?></dd>

        <dt>Stock</dt>
        <dd><?php echo $product['stock']; ?> available</dd>

        <dt>Status</dt>
        <dd><?php echo $product['stock_status']; ?></dd>
      </dl>
    </div>

  </div>

</div>

<div class="about-maker">
  <div class="av"></div>

  <div>
    <h3 style="margin-bottom:4px"><?php echo $product['shop_name']; ?></h3>
    <p class="muted" style="font-size:14px">Handmade products by this artisan.</p>
  </div>

  <a href="artisan-profile.php?id=<?php echo $product['artisan_id']; ?>" class="btn btn-outline">
    Visit shop
  </a>
</div>

</div>

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

<script>
function changeImage(src){
  document.getElementById("mainImage").src = src;
}
</script>

</body>
</html>