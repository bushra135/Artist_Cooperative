<?php
session_start();
include 'connection.php';

$user_id = 1; // Temporary until login sessions are ready
$error = '';

function e($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function goToProducts() {
  echo "<script>window.location.replace('dashboard-products.php');</script>";
  exit;
}

$artisan_stmt = $db->prepare("
  SELECT user_id, shop_name, verification_status
  FROM artisan_profiles
  WHERE user_id = ?
  LIMIT 1
");
$artisan_stmt->execute([$user_id]);
$artisan = $artisan_stmt->fetch(PDO::FETCH_ASSOC);

$shop_name = $artisan ? $artisan['shop_name'] : 'Maison Clay';
$verification_status = $artisan ? ucfirst($artisan['verification_status']) : 'Verified';
$artisan_id = $artisan ? (int)$artisan['user_id'] : $user_id;

$categories_stmt = $db->query("
  SELECT category_id, name
  FROM categories
  ORDER BY name
");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$product_id = 0;

if (isset($_GET['id'])) {
  $product_id = (int)$_GET['id'];
} elseif (isset($_POST['product_id'])) {
  $product_id = (int)$_POST['product_id'];
}

$is_edit = $product_id > 0;

$product = [
  'title' => '',
  'description' => '',
  'category_id' => '',
  'material' => '',
  'price' => '',
  'stock' => '',
  'status' => 'live'
];

if ($is_edit) {
  $product_stmt = $db->prepare("
    SELECT product_id, title, description, category_id, material, price, stock, status
    FROM products
    WHERE product_id = ?
    AND artisan_id = ?
    LIMIT 1
  ");
  $product_stmt->execute([$product_id, $artisan_id]);
  $found_product = $product_stmt->fetch(PDO::FETCH_ASSOC);

  if ($found_product) {
    $product = $found_product;
  } else {
    goToProducts();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $material = trim($_POST['material'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $status = $_POST['status'] ?? 'draft';

  $stock_status = $stock <= 0 ? 'out_of_stock' : 'low_stock';

  if ($title === '' || $description === '' || $category_id <= 0 || $material === '' || $price <= 0) {
    $error = 'Please fill in all required product fields.';

    $product['title'] = $title;
    $product['description'] = $description;
    $product['category_id'] = $category_id;
    $product['material'] = $material;
    $product['price'] = $price;
    $product['stock'] = $stock;
    $product['status'] = $status;
  } else {
    try {
      if ($is_edit) {
        $update = $db->prepare("
          UPDATE products
          SET category_id = ?,
              title = ?,
              description = ?,
              material = ?,
              price = ?,
              stock = ?,
              stock_status = ?,
              status = ?
          WHERE product_id = ?
          AND artisan_id = ?
        ");

        $update->execute([
          $category_id,
          $title,
          $description,
          $material,
          $price,
          $stock,
          $stock_status,
          $status,
          $product_id,
          $artisan_id
        ]);

        goToProducts();
      } else {
        $insert = $db->prepare("
          INSERT INTO products
          (artisan_id, category_id, title, description, material, price, stock, stock_status, status, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $insert->execute([
          $artisan_id,
          $category_id,
          $title,
          $description,
          $material,
          $price,
          $stock,
          $stock_status,
          $status
        ]);

        goToProducts();
      }
    } catch (PDOException $ex) {
      $error = 'Could not save product. Please check the product data and try again.';
    }
  }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $is_edit ? 'Edit product' : 'New product'; ?> — Artisan</title>
<meta name="description" content="Create or edit a product.">
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
      <a href="dashboard-products.php" class="active">Products</a>
      <a href="dashboard-orders.php">Orders</a>
      <a href="dashboard-profile.php">Profile</a>
    </nav>
  </aside>

  <div>
    <div style="margin-bottom:20px">
      <a href="dashboard-products.php" style="color:var(--fg);font-size:13px;opacity:.7">← Back to products</a>
      <h1 style="font-size:30px;margin-top:8px"><?= $is_edit ? 'Edit product' : 'New product'; ?></h1>
    </div>

    <?php if ($error): ?>
      <div class="badge badge-danger" style="margin-bottom:16px"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="dashboard-product-form.php<?= $is_edit ? '?id=' . e($product_id) : ''; ?>" class="form-grid">
      <?php if ($is_edit): ?>
        <input type="hidden" name="product_id" value="<?= e($product_id); ?>">
      <?php endif; ?>

      <div>
        <div class="block">
          <h3>Basics</h3>

          <div class="field">
            <label>Title</label>
            <input class="input" name="title" value="<?= e($product['title']); ?>" placeholder="Speckled stoneware bowl" required>
          </div>

          <div class="field">
            <label>Description</label>
            <textarea class="textarea" name="description" rows="6" placeholder="Tell the story of this piece..." required><?= e($product['description']); ?></textarea>
          </div>

          <div class="row-2">
            <div class="field">
              <label>Category</label>
              <select class="select" name="category_id" required>
                <option value="">Choose category</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= e($category['category_id']); ?>" <?= (int)$product['category_id'] === (int)$category['category_id'] ? 'selected' : ''; ?>>
                    <?= e($category['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Material</label>
              <input class="input" name="material" value="<?= e($product['material']); ?>" placeholder="Stoneware" required>
            </div>
          </div>
        </div>

        <div class="block">
          <h3>Photos</h3>
          <div class="upload">Drag photos here or <a href="#">browse</a></div>
          <div class="thumbs">
            <div></div>
            <div class="t2"></div>
            <div class="t3"></div>
            <div class="t4"></div>
          </div>
        </div>
      </div>

      <aside>
        <div class="block">
          <h3>Pricing &amp; stock</h3>

          <div class="field">
            <label>Price (€)</label>
            <input class="input" name="price" type="number" step="0.01" min="0" value="<?= e($product['price']); ?>" placeholder="42.00" required>
          </div>

          <div class="field">
            <label>Stock</label>
            <input class="input" name="stock" type="number" min="0" value="<?= e($product['stock']); ?>" placeholder="12" required>
          </div>

          <div class="field">
            <label>SKU</label>
            <input class="input" placeholder="Not stored in current database" disabled>
          </div>
        </div>

        <div class="block">
          <h3>Status</h3>

          <div class="field">
            <label>Visibility</label>
            <select class="select" name="status">
              <option value="live" <?= $product['status'] === 'live' ? 'selected' : ''; ?>>Live</option>
              <option value="draft" <?= $product['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
            </select>
          </div>
        </div>

        <button class="btn btn-accent btn-block btn-lg" type="submit">
          <?= $is_edit ? 'Update product' : 'Save product'; ?>
        </button>

        <a href="dashboard-products.php" class="btn btn-ghost btn-block" style="margin-top:8px">Cancel</a>
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
