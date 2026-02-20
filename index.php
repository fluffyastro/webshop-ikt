<?php 
session_start();
include_once("helpers/db.php");

$products = [];

$sql = "SELECT * FROM products LIMIT 8";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL error");
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="hu">
<?php include_once("components/head.php") ?>
<body>

<?php 
include_once("components/navbar.php");
include_once("components/hero.php");
?>

<div class="productSearch">
    <h1>Keress számos termékeink között!</h1>

    <form method="post" action="search.php">
        <div class="search">
            <input 
                type="text" 
                placeholder="Keresés..." 
                id="search" 
                name="search"
            >
            <button type="submit">
                <i class="bx bx-search-alt-2"></i>
            </button>
        </div>
    </form>

    <div class="products">
        <?php foreach ($products as $product): ?>
            <a href="product.php?id=<?= (int)$product['id'] ?>">
                <div class="product">
                    <img src="<?= htmlspecialchars($product["image_url"], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="nemtom">
                        <h2><?= htmlspecialchars($product["name"], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= substr(htmlspecialchars($product["description"], ENT_QUOTES, 'UTF-8'), 0, 120) . (strlen($product["description"]) > 120 ? "..." : "") ?></p>
                        <span class="price">
                            <?= number_format((int)$product["price"], 0, ',', ' ') ?> Ft
                        </span>
                        <button class="addToCart" type="button">
                            <i class="bx bx-cart-add"></i>Kosárba
                        </button>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <p>Nincsenek megjeleníthető termékek.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>