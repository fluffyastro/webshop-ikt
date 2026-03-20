<?php
session_start();
include_once("helpers/db.php");
?>
<!DOCTYPE html>
<html lang="hu">
<?php include_once("components/head.php"); ?>
<body>
<?php include_once("components/navbar.php"); ?>

<?php
if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$action = $_GET["action"] ?? null;
$id = isset($_GET["id"]) ? intval($_GET["id"]) : null;

if($action === "add" && $id > 0) {
    $_SESSION["cart"][$id] = ($_SESSION["cart"][$id] ?? 0) + 1;
    header("Location: cart.php");
    exit;
}

if($action === "minus" && $id > 0) {
    if (isset($_SESSION["cart"][$id])) {
        $_SESSION["cart"][$id]--;
        if ($_SESSION["cart"][$id] <= 0) {
            unset($_SESSION["cart"][$id]);
        }
    }
    header("Location: cart.php");
    exit;
}

if($action === "remove" && $id > 0) {
    unset($_SESSION["cart"][$id]);
    header("Location: cart.php");
    exit;
}

if ($action === "clear") {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}

$cartIds = array_keys($_SESSION["cart"]);
$products = [];
if(count($cartIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $types = str_repeat('i', count($cartIds));
    $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if(!$stmt) die("SQL hiba");
    $params = [];
    $params[] = &$types;
    foreach ($cartIds as $index => $id) {
        $cartIds[$index] = intval($id);
        $params[] = &$cartIds[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $params);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $products[(int)$row["id"]] = $row;
    }
    $stmt->close();
}

/**
 * ÁFA számítás
 * Magyarországon tipikusan 27%.
 * FONTOS: ez csak akkor helyes, ha az ár BRUTTÓ (ÁFÁ-val növelt) ár a DB-ben.
 * Ha a DB-ben NETTÓ ár van, szólj és átírom.
 */
$VAT_RATE = 0.27;

$grossTotal = 0;
foreach ($_SESSION["cart"] as $id => $quantity) {
    if (isset($products[$id])) {
        $grossTotal += (float)$products[$id]["price"] * (int)$quantity;
    }
}

// Bruttóból nettó + áfa bontás:
$netTotal = ($grossTotal > 0) ? ($grossTotal / (1 + $VAT_RATE)) : 0;
$vatTotal = $grossTotal - $netTotal;

// Kerekítés Ft-ra (0 tizedes)
$grossTotalRounded = round($grossTotal);
$netTotalRounded = round($netTotal);
$vatTotalRounded = $grossTotalRounded - $netTotalRounded; // hogy a végösszeg pontosan kijöjjön
?>

<div class="cart-page">
    <div class="cart-header">
        <h1>Kosár</h1>
        <?php if(count($_SESSION["cart"]) > 0): ?>
            <a href="?action=clear" class="cart-clear">Kosár ürítése</a>
        <?php endif; ?>
    </div>

    <?php if(count($_SESSION["cart"]) === 0): ?>
        <div class="cart-empty">
            <p>A kosarad jelenleg üres.</p>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach($_SESSION["cart"] as $pid => $qty): ?>
                <?php
                    $pid = (int)$pid;
                    $qty = (int)$qty;
                    if(!isset($products[$pid])) continue;
                    $p = $products[$pid];

                    // feltételezzük, hogy a price BRUTTÓ
                    $lineGross = (float)$p["price"] * $qty;
                ?>
                <div class="cart-item">
                    <img src="<?= htmlspecialchars($p["image_url"]) ?>" alt="<?= htmlspecialchars($p["name"]) ?>">
                    <div class="cart-item__info">
                        <h2><?= htmlspecialchars($p["name"]) ?></h2>
                        <p><?= htmlspecialchars($p["description"]) ?></p>
                        <span class="cart-item__price"><?= number_format(round($lineGross), 0, ',', ' ') ?> Ft</span>
                    </div>
                    <div class="cart-item__actions">
                        <div class="qty">
                            <a href="?action=minus&id=<?= $pid ?>" class="qty-btn">-</a>
                            <span class="qty-val"><?= $qty ?></span>
                            <a href="?action=add&id=<?= $pid ?>" class="qty-btn">+</a>
                        </div>
                        <div class="cart-item__line">
                            <?= number_format(round($lineGross), 0, ',', ' ') ?> Ft
                        </div>
                        <a href="?action=remove&id=<?= $pid ?>" class="remove">Eltávolítás</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div class="cart-summary__row">
                <span>Nettó (<?= (int)round($VAT_RATE * 100) ?>% ÁFA nélkül):</span>
                <span class="cart-summary__subtotal"><?= number_format($netTotalRounded, 0, ',', ' ') ?> Ft</span>
            </div>
            <div class="cart-summary__row">
                <span>ÁFA:</span>
                <span class="cart-summary__vat"><?= number_format($vatTotalRounded, 0, ',', ' ') ?> Ft</span>
            </div>
            <div class="cart-summary__row">
                <span>Összesen (bruttó):</span>
                <span class="cart-summary__total"><?= number_format($grossTotalRounded, 0, ',', ' ') ?> Ft</span>
            </div>

            <button class="addToCart" type="button" style="width:100%;" onclick="alert('kifizetted eskü')">
                <i class="bx bx-credit-card"></i> Fizetés
            </button>
        </div>
    <?php endif; ?>
</div>
</body>
</html>