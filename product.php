<?php
    session_start();
    include_once("helpers/db.php");

    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

    if ($id <= 0) {
        header("Location: search.php");
        exit;
    }

    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error");
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $product = null;
    } else {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
?>
<!DOCTYPE html>
<html lang="hu">
<?php include_once("components/head.php") ?>
<body>
    <?php include_once("components/navbar.php"); ?>

    <?php if ($product): ?>
        <div class="product-page">
            <a href="search.php" class="back-link">
                <i class="bx bx-arrow-back"></i> Vissza a termékekhez
            </a>

            <div class="product-detail">
                <div class="product-detail__image">
                    <img src="<?= htmlspecialchars($product["image_url"]) ?>" alt="<?= htmlspecialchars($product["name"]) ?>">
                </div>

                <div class="product-detail__info">
                    <h1><?= htmlspecialchars($product["name"]) ?></h1>
                    <p class="product-detail__description"><?= htmlspecialchars($product["description"]) ?></p>
                    <span class="product-detail__price"><?= number_format((int)$product["price"], 0, ',', ' ') ?> Ft</span>

                    <button class="addToCart" data-id="<?= (int)$product["id"] ?>">
                        <i class="bx bx-cart-add"></i>Kosárba
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="product-page product-page--notfound">
            <i class="bx bx-error-circle error"></i>
            <h2>A termék nem található.</h2>
            <a href="search.php" class="back-link">
                <i class="bx bx-arrow-back small"></i> Vissza a termékekhez
            </a>
        </div>
    <?php endif; ?>
</body>
</html>