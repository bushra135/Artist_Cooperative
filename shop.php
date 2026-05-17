<?php
include 'connection.php';
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop handmade goods — Artisan</title>

<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/shop.css">

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
<a href="cart.php" class="btn btn-ghost btn-sm">Cart • 2</a>
<a href="login.html" class="btn btn-outline btn-sm">Log in</a>
<a href="signup.html" class="btn btn-accent btn-sm">Sign up</a>
</div>

</div>
</header>

<main>

<section class="shop-hero">
<div class="container">

<span class="eyebrow">Marketplace</span>

<h1 style="margin-top:14px">Shop handmade</h1>

<p>
Browse pieces from 200+ independent makers —
pottery, textiles, jewelry, woodwork and more.
</p>

</div>
</section>

<section class="container">

<div class="shop-layout">

<aside class="filters">

<h4>Category</h4>

<ul>
<li><a href="#" class="active">All products</a></li>
<li><a href="#">Ceramics</a></li>
<li><a href="#">Textiles</a></li>
<li><a href="#">Jewelry</a></li>
<li><a href="#">Woodwork</a></li>
<li><a href="#">Paintings</a></li>
</ul>

</aside>

<div>

<div class="shop-toolbar">

<div class="shop-count">
Showing <strong>9</strong> of 248 products
</div>

<select class="select" style="width:auto">
<option>Sort: Newest</option>
</select>

</div>

<div class="product-grid">

<?php

$sql = "
SELECT p.product_id, p.title, p.price, ap.shop_name, MIN(pi.image_url) AS image_url
FROM products p
JOIN artisan_profiles ap ON p.artisan_id = ap.user_id
LEFT JOIN product_images pi ON p.product_id = pi.product_id
GROUP BY p.product_id, p.title, p.price, ap.shop_name
";

$stmt = $db->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {

?>

<a href="product.php?id=<?php echo $product['product_id']; ?>"?id=<?php echo $product['product_id']; ?>" class="p-card">

<div class="p-thumb">

<?php if (!empty($product['image_url'])) { ?>

<img
src="<?php echo $product['image_url']; ?>"
alt="<?php echo $product['title']; ?>"
style="width:100%; height:100%; object-fit:cover;"
>

<?php } ?>

</div>

<div class="p-body">

<div class="p-title">
<?php echo $product['title']; ?>
</div>

<div class="p-maker">
<?php echo $product['shop_name']; ?>
</div>

<div class="p-price">
€<?php echo $product['price']; ?>
</div>

</div>

</a>

<?php
}
?>

</div>

</div>

</div>

</section>

</main>

</body>
</html>