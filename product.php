<?php
session_start();

include 'connection.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function productImageUrl($image_url) {

    $image_url = trim(str_replace('\\', '/', (string)$image_url));

    if ($image_url === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $image_url)) {
        return $image_url;
    }

    if (strpos($image_url, '/') !== false) {
        return ltrim($image_url, '/');
    }

    return 'uploads/' . $image_url;
}

function goToCart() {
    header("Location: cart.php");
    exit;
}

/*
Temporary guest session
حتى يشتغل الكارت بدون تسجيل دخول
*/
if (!isset($_SESSION['user_id'])) {

    $_SESSION['user_id'] = 2;
    $_SESSION['role'] = 'customer';
}

$id = (int)($_GET['id'] ?? $_POST['product_id'] ?? 1);

$user_id = (int)($_SESSION['user_id'] ?? 2);

/*
ADD TO CART
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add_to_cart'
) {

    $product_id = (int)($_POST['product_id'] ?? 0);

    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $product_check = $db->prepare("
        SELECT product_id
        FROM products
        WHERE product_id = ?
        AND status = 'live'
        LIMIT 1
    ");

    $product_check->execute([$product_id]);

    $can_add = $product_check->fetch(PDO::FETCH_ASSOC);

    if ($can_add) {

        /*
        GET CART
        */
        $cart_stmt = $db->prepare("
            SELECT cart_id
            FROM carts
            WHERE user_id = ?
            LIMIT 1
        ");

        $cart_stmt->execute([$user_id]);

        $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);

        /*
        CREATE CART IF NOT EXISTS
        */
        if ($cart) {

            $cart_id = (int)$cart['cart_id'];

        } else {

            $create_cart = $db->prepare("
                INSERT INTO carts (user_id, created_at)
                VALUES (?, NOW())
            ");

            $create_cart->execute([$user_id]);

            $cart_id = (int)$db->lastInsertId();
        }

        /*
        CHECK IF PRODUCT EXISTS IN CART
        */
        $item_stmt = $db->prepare("
            SELECT cart_item_id
            FROM cart_items
            WHERE cart_id = ?
            AND product_id = ?
            LIMIT 1
        ");

        $item_stmt->execute([
            $cart_id,
            $product_id
        ]);

        $cart_item = $item_stmt->fetch(PDO::FETCH_ASSOC);

        /*
        UPDATE QUANTITY
        */
        if ($cart_item) {

            $update_item = $db->prepare("
                UPDATE cart_items
                SET quantity = quantity + ?
                WHERE cart_item_id = ?
            ");

            $update_item->execute([
                $quantity,
                (int)$cart_item['cart_item_id']
            ]);

        } else {

            /*
            INSERT NEW ITEM
            */
            $insert_item = $db->prepare("
                INSERT INTO cart_items (
                    cart_id,
                    product_id,
                    quantity
                )
                VALUES (?, ?, ?)
            ");

            $insert_item->execute([
                $cart_id,
                $product_id,
                $quantity
            ]);
        }
    }

    goToCart();
}

/*
CART COUNT
*/
$cart_count_stmt = $db->prepare("
    SELECT COALESCE(SUM(cart_items.quantity), 0)
    FROM carts
    LEFT JOIN cart_items
    ON cart_items.cart_id = carts.cart_id
    WHERE carts.user_id = ?
");

$cart_count_stmt->execute([$user_id]);

$cart_count = (int)$cart_count_stmt->fetchColumn();

/*
PRODUCT QUERY
*/
$sql = "
    SELECT
        p.*,
        ap.shop_name,
        pi.image_url

    FROM products p

    JOIN (
        SELECT
            user_id,
            MAX(shop_name) AS shop_name
        FROM artisan_profiles
        GROUP BY user_id
    ) ap
    ON p.artisan_id = ap.user_id

    LEFT JOIN product_images pi
    ON pi.image_id = (

        SELECT first_image.image_id

        FROM product_images first_image

        WHERE first_image.product_id = p.product_id

        ORDER BY
            first_image.sort_order ASC,
            first_image.image_id DESC

        LIMIT 1
    )

    WHERE p.product_id = ?

    LIMIT 1
";

$stmt = $db->prepare($sql);

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

/*
IF PRODUCT NOT FOUND
*/
if (!$product) {

    header("Location: shop.php");

    exit;
}

$main_image = productImageUrl(
    $product['image_url'] ?? ''
);

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
<?= e($product['title']); ?> — Artisan
</title>

<link rel="stylesheet" href="css/global.css">

<link rel="stylesheet" href="css/product.css">

</head>

<body>

<header class="site-header">

    <div class="nav-inner">

        <a class="brand" href="index.html">
            Arti<span>san</span>
        </a>

        <nav class="nav-links">

            <a href="index.html">
                About site
            </a>

            <a href="home.html">
                Home
            </a>

            <a href="shop.php" class="active">
                Shop
            </a>

            <a href="artists.php">
                Artisans
            </a>

            <a href="about.html">
                About
            </a>

        </nav>

        <div class="nav-actions">

            <a
                href="cart.php"
                class="btn btn-ghost btn-sm"
            >
                Cart · <?= e($cart_count); ?>
            </a>

            <a
                href="login.html"
                class="btn btn-outline btn-sm"
            >
                Log in
            </a>

            <a
                href="signup.html"
                class="btn btn-accent btn-sm"
            >
                Sign up
            </a>

        </div>

    </div>

</header>

<main>

<div class="container">

<nav class="crumbs">

    <a href="shop.php">
        Shop
    </a>

    /

    <?= e($product['title']); ?>

</nav>

<div class="product">

    <div class="gallery">

        <div class="main">

            <?php if ($main_image !== ''): ?>

                <img
                    src="<?= e($main_image); ?>"
                    alt="<?= e($product['title']); ?>"

                    style="
                        width:100%;
                        height:100%;
                        object-fit:cover;
                        display:block;
                        border-radius:18px;
                    "
                >

            <?php endif; ?>

        </div>

    </div>

    <div class="product-info">

        <a
            href="artisan-profile.php?id=<?= e($product['artisan_id']); ?>"
            class="maker-link"
        >
            <?= e($product['shop_name']); ?> →
        </a>

        <h1>
            <?= e($product['title']); ?>
        </h1>

        <div class="price">

            €<?= e(number_format(
                (float)$product['price'],
                2
            )); ?>

        </div>

        <div class="meta">

            <span class="badge badge-success">
                In stock
            </span>

            <span class="badge">
                Ships in 2 days
            </span>

            <span class="badge badge-outline">

                One of
                <?= e($product['stock']); ?>
                made

            </span>

        </div>

        <p class="desc">
            <?= e($product['description']); ?>
        </p>

        <form method="post">

            <input
                type="hidden"
                name="action"
                value="add_to_cart"
            >

            <input
                type="hidden"
                name="product_id"
                value="<?= e($product['product_id']); ?>"
            >

            <input
                type="hidden"
                name="quantity"
                value="1"
            >

            <div class="actions">

                <button
                    type="submit"
                    class="btn btn-accent btn-lg"
                    style="
                        width:100%;
                        justify-content:center
                    "
                >

                    Add to cart —

                    €<?= e(number_format(
                        (float)$product['price'],
                        2
                    )); ?>

                </button>

            </div>

        </form>

        <div class="specs">

            <dl>

                <dt>Material</dt>

                <dd>
                    <?= e($product['material']); ?>
                </dd>

                <dt>Stock</dt>

                <dd>
                    <?= e($product['stock']); ?>
                    available
                </dd>

                <dt>Status</dt>

                <dd>
                    <?= e($product['stock_status']); ?>
                </dd>

            </dl>

        </div>

    </div>

</div>

<div class="about-maker">

    <div class="av"></div>

    <div>

        <h3 style="margin-bottom:4px">

            <?= e($product['shop_name']); ?>

        </h3>

        <p
            class="muted"
            style="font-size:14px"
        >
            Handmade products by this artisan.
        </p>

    </div>

    <a
        href="artisan-profile.php?id=<?= e($product['artisan_id']); ?>"
        class="btn btn-outline"
    >
        Visit shop
    </a>

</div>

</div>

</main>

</body>

</html>