<?php
include 'connection.php';
include 'image-path.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$sql = "
SELECT
    MIN(ap.user_id) AS user_id,
    ap.shop_name,
    COALESCE(MAX(NULLIF(ap.bio, '')), '') AS bio,
    COALESCE(MAX(NULLIF(ap.location, '')), 'Bahrain') AS location,
    COALESCE(MAX(NULLIF(ap.avatar, '')), '') AS avatar,
    COALESCE(MAX(NULLIF(ap.cover_photo, '')), '') AS cover_photo,
    COUNT(DISTINCT p.product_id) AS product_count
FROM artisan_profiles ap
LEFT JOIN products p ON p.artisan_id = ap.user_id
GROUP BY ap.shop_name
ORDER BY user_id
";

$stmt = $db->query($sql);
$artisans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Meet the artisans — Artisan</title>
<meta name="description" content="Independent makers selling on Artisan.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/artists.css">
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

<section class="artists-hero">
  <div class="container">
    <span class="eyebrow">Verified makers</span>
    <h1 style="margin-top:14px">Meet the artisans</h1>
    <p class="muted">Independent studios selling handmade products.</p>
  </div>
</section>

<section class="container">
  <div class="artists-grid">

    <?php
    $i = 1;

    foreach ($artisans as $artisan) {
        $avatar_url = storedImageUrl($artisan['avatar'] ?? '');
        $cover_url = storedImageUrl($artisan['cover_photo'] ?? '');
        $bio = trim($artisan['bio'] ?? '');

        if ($bio === '') {
            $bio = 'Handmade products created with care by this artisan.';
        }
    ?>

      <a href="artisan-profile.php?id=<?php echo e($artisan['user_id']); ?>" class="artist-card">

        <div class="artist-cover c<?php echo e($i); ?>">
          <?php if ($cover_url !== '') { ?>
            <img
              src="<?php echo e($cover_url); ?>"
              alt="<?php echo e($artisan['shop_name']); ?>"
              style="width:100%;height:100%;object-fit:cover;display:block;"
            >
          <?php } ?>
        </div>

        <div class="artist-body">
          <div class="artist-av a<?php echo e($i); ?>">
            <?php if ($avatar_url !== '') { ?>
              <img
                src="<?php echo e($avatar_url); ?>"
                alt="<?php echo e($artisan['shop_name']); ?>"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"
              >
            <?php } ?>
          </div>

          <h3><?php echo e($artisan['shop_name']); ?></h3>

          <div class="loc">📍 <?php echo e($artisan['location']); ?></div>

          <p><?php echo e($bio); ?></p>

          <div class="artist-stats">
            <span><?php echo e((int)$artisan['product_count']); ?> products</span>
          </div>
        </div>

      </a>

    <?php
      $i++;
    }
    ?>

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