<?php
session_start();
include 'connection.php';

$user_id = 1; // Temporary until login sessions are ready
$message = '';
$error = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $shop_name = trim($_POST['shop_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');

    try {
        $update_user = $db->prepare("
            UPDATE users
            SET full_name = ?, email = ?
            WHERE user_id = ?
        ");
        $update_user->execute([$full_name, $email, $user_id]);

        $update_profile = $db->prepare("
            UPDATE artisan_profiles
            SET shop_name = ?, bio = ?, location = ?, website = ?
            WHERE user_id = ?
        ");
        $update_profile->execute([$shop_name, $bio, $location, $website, $user_id]);

        if ($update_profile->rowCount() === 0) {
            $insert_profile = $db->prepare("
                INSERT INTO artisan_profiles
                (user_id, shop_name, craft_specialty, bio, location, website, avatar, cover_photo, member_since, verification_status)
                VALUES (?, ?, 'Pottery', ?, ?, ?, '', '', CURDATE(), 'pending')
            ");
            $insert_profile->execute([$user_id, $shop_name, $bio, $location, $website]);
        }

        $message = 'Profile saved successfully.';
    } catch (PDOException $ex) {
        $error = 'Could not save profile. Please check the data and try again.';
    }
}

$stmt = $db->prepare("
    SELECT
        users.full_name,
        users.email,
        artisan_profiles.shop_name,
        artisan_profiles.bio,
        artisan_profiles.location,
        artisan_profiles.website,
        artisan_profiles.member_since,
        artisan_profiles.verification_status
    FROM users
    LEFT JOIN artisan_profiles ON artisan_profiles.user_id = users.user_id
    WHERE users.user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$full_name = $profile['full_name'] ?? 'Maison Clay';
$email = $profile['email'] ?? 'contact@maisonclay.fr';
$shop_name = $profile['shop_name'] ?? 'Maison Clay';
$bio = $profile['bio'] ?? 'Slow-fired stoneware from Provence. Each piece is shaped by hand in a small ceramics studio.';
$location = $profile['location'] ?? 'Provence, France';
$website = $profile['website'] ?? 'maisonclay.fr';
$member_since = !empty($profile['member_since']) ? date('Y', strtotime($profile['member_since'])) : '2024';
$verification_status = !empty($profile['verification_status']) ? ucfirst($profile['verification_status']) : 'Verified';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile — Artisan</title>
<meta name="description" content="Edit your artisan profile.">
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/dash-form.css">

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
      <a href="dashboard-orders.php">Orders</a>
      <a href="dashboard-profile.php" class="active">Profile</a>
    </nav>
  </aside>

  <div>
    <h1 style="font-size:30px;margin-bottom:6px">Profile</h1>
    <p class="muted" style="margin-bottom:20px">Manage how customers see your studio and account details.</p>

    <?php if ($message): ?>
      <div class="badge badge-success" style="margin-bottom:16px"><?= e($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="badge badge-danger" style="margin-bottom:16px"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid">
      <div>
        <div class="block">
          <h3>Studio profile</h3>

          <div class="field">
            <label>Full name</label>
            <input class="input" name="full_name" value="<?= e($full_name); ?>" required>
          </div>

          <div class="field">
            <label>Shop name</label>
            <input class="input" name="shop_name" value="<?= e($shop_name); ?>" required>
          </div>

          <div class="field">
            <label>Short bio</label>
            <textarea class="textarea" name="bio" rows="6" required><?= e($bio); ?></textarea>
          </div>

          <div class="row-2">
            <div class="field">
              <label>Location <span class="muted">(optional)</span></label>
              <input class="input" name="location" value="<?= e($location); ?>">
            </div>

            <div class="field">
              <label>Member since</label>
              <input class="input" value="<?= e($member_since); ?>" disabled>
            </div>
          </div>

          <button class="btn btn-accent btn-lg" type="submit">Save profile</button>
        </div>

        <div class="block">
          <h3>Account</h3>

          <div class="field">
            <label>Email</label>
            <input class="input" name="email" type="email" value="<?= e($email); ?>" required>
          </div>

          <p class="muted">To change your password, use the forgot password flow. We'll email you a reset link.</p>
        </div>
      </div>

      <aside>
        <div class="block">
          <h3>Branding</h3>

          <div class="field">
            <label>Avatar <span class="muted">(optional)</span></label>
            <input class="input" type="file">
          </div>

          <div class="field">
            <label>Cover photo <span class="muted">(optional)</span></label>
            <input class="input" type="file">
          </div>
        </div>

        <div class="block">
          <h3>Contact</h3>

          <div class="field">
            <label>Public email <span class="muted">(optional)</span></label>
            <input class="input" type="email" value="<?= e($email); ?>">
          </div>

          <div class="field">
            <label>Website <span class="muted">(optional)</span></label>
            <input class="input" name="website" value="<?= e($website); ?>">
          </div>
        </div>

        <a href="login.php" class="btn btn-outline btn-block">Log out</a>
      </aside>
    </form>
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
