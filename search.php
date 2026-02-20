<?php 
    session_start();
    include_once("helpers/db.php");
?>
<!DOCTYPE html>
<html lang="hu">
<?php include_once("components/head.php") ?>
<body>
    <?php
        include_once("components/navbar.php");
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $searchTerm = trim($_POST["search"] ?? '');
            if ($searchTerm === '') {
                $products = [];
            } else {
                $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ?";
                $stmt = $conn->prepare($sql);
                if(!$stmt){
                    die("SQL error");
                }
                $likeSearchTerm = "%" . $searchTerm . "%";
                $stmt->bind_param("ss", $likeSearchTerm, $likeSearchTerm);
                $stmt->execute();
                $result = $stmt->get_result();
                $products = [];
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $products[] = $row;
                    }
                }
                $stmt->close();
            }
        } else {
            $products = [];
            $sql = "SELECT * FROM products LIMIT 8;";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
        }
    ?>
    <div class="productSearch">
        <h1>Keress számos termékeink között!</h1>
        <form method="post">
            <div class="search">
                <input type="text" placeholder="Keresés..." id="search" name="search" value="<?= isset($searchTerm) ? htmlspecialchars($searchTerm) : '' ?>">
                <button>
                    <i class="bx bx-search-alt-2"></i>
                </button>
            </div>

            <?php if($_SERVER["REQUEST_METHOD"]=="POST") echo ("<span>\"". htmlspecialchars($searchTerm) . "\": " . count($products) . " találat</span>") ?>
        </form>
        <div class="products">
            <?php foreach ($products as $product): ?>
                <a href="product.php?id=<?= (int)$product['id'] ?>">
                    <div class="product">
                        <img src="<?= htmlspecialchars($product["image_url"]) ?>">
                        <div class="nemtom">
                            <h2><?= htmlspecialchars($product["name"]) ?></h2>
                            <p><?= substr(htmlspecialchars($product["description"], ENT_QUOTES, 'UTF-8'), 0, 120) . (strlen($product["description"]) > 120 ? "..." : "") ?></p>
                            <span class="price"><?= number_format((int)$product["price"], 0, ',', ' ') ?> Ft</span>
                            <button class="addToCart">
                                <i class="bx bx-cart-add"></i>Kosárba
                            </button>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (count($products) === 0): ?>
                <p>Nincs találat a keresésre.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>