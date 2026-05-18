<?php
session_start();
include 'connection.php';
include 'image-path.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function productImageUrl($image_urls) {
    return storedImageUrl($image_urls);
}

$cart_count = 0;
$current_user_id = (int)($_SESSION['user_id'] ?? 0);

if ($current_user_id > 0) {
    $cart_count_stmt = $db->prepare("
        SELECT COALESCE(SUM(cart_items.quantity), 0)
        FROM carts
        LEFT JOIN cart_items ON cart_items.cart_id = carts.cart_id
        WHERE carts.user_id = ?
    ");
    $cart_count_stmt->execute([$current_user_id]);
    $cart_count = (int)$cart_count_stmt->fetchColumn();
}

$category_id = (int)($_GET['category_id'] ?? 0);

$categories_stmt = $db->query("
    SELECT category_id, name
    FROM categories
    ORDER BY name
");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$params = [];

$sql = "
    SELECT
        p.product_id,
        p.title,
        p.price,
        ap.shop_name,
        (
            SELECT GROUP_CONCAT(pi.image_url ORDER BY pi.sort_order ASC, pi.image_id DESC SEPARATOR '||')
            FROM product_images pi
            WHERE pi.product_id = p.product_id
        ) AS image_urls
    FROM products p
    JOIN (
        SELECT user_id, MAX(shop_name) AS shop_name
        FROM artisan_profiles
        GROUP BY user_id
    ) ap ON p.artisan_id = ap.user_id
    WHERE p.status = 'live'
";

if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<a class="brand" href="index.php">Arti<span>san</span></a>

<nav class="nav-links">
<a href="index.php">About site</a>
<a href="home.php">Home</a>
<a href="shop.php" class="active">Shop</a>
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
<li>
  <a href="shop.php" class="<?= $category_id === 0 ? 'active' : ''; ?>">
    All products
  </a>
</li>

<?php foreach ($categories as $category): ?>
<li>
  <a href="shop.php?category_id=<?= e($category['category_id']); ?>" class="<?= $category_id === (int)$category['category_id'] ? 'active' : ''; ?>">
    <?= e($category['name']); ?>
  </a>
</li>
<?php endforeach; ?>
</ul>

</aside>

<div class="shop-content">

<div class="shop-toolbar">

<div class="shop-count">
Showing <strong><?= e(count($products)); ?></strong> products
</div>

<select class="select" style="width:auto">
<option>Sort: Newest</option>
</select>

</div>

<div class="product-grid">

<?php foreach ($products as $product): ?>

<?php
$image_url = productImageUrl($product['image_urls'] ?? '');
?>

<a href="product.php?id=<?= e($product['product_id']); ?>" class="p-card">

<div class="p-thumb">

<?php if ($image_url !== ''): ?>
<img
src="<?= e($image_url); ?>"
alt="<?= e($product['title']); ?>"
style="width:100%;height:100%;object-fit:cover;display:block;"
>
<?php endif; ?>

</div>

<div class="p-body">

<div class="p-title">
<?= e($product['title']); ?>
</div>

<div class="p-maker">
<?= e($product['shop_name']); ?>
</div>

<div class="p-price">
€<?= e(number_format((float)$product['price'], 2)); ?>
</div>

</div>

</a>

<?php endforeach; ?>

</div>

</div>

</div>

</section>

</main>

</body>
</html>
