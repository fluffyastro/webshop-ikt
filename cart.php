<?php
session_start();

include_once("helpers/db.php");
require_once("helpers/stripe/init.php"); // ebben legyen: $STRIPE_SECRET_KEY

// Kosár inicializálás
if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Action olvasás (GET + POST támogatással)
$action = $_POST["action"] ?? ($_GET["action"] ?? null);
$id = isset($_GET["id"]) ? intval($_GET["id"]) : null;

// Kosár műveletek
if ($action === "add" && $id > 0) {
    $_SESSION["cart"][$id] = ($_SESSION["cart"][$id] ?? 0) + 1;
    header("Location: cart.php");
    exit;
}

if ($action === "minus" && $id > 0) {
    if (isset($_SESSION["cart"][$id])) {
        $_SESSION["cart"][$id]--;
        if ($_SESSION["cart"][$id] <= 0) {
            unset($_SESSION["cart"][$id]);
        }
    }
    header("Location: cart.php");
    exit;
}

if ($action === "remove" && $id > 0) {
    unset($_SESSION["cart"][$id]);
    header("Location: cart.php");
    exit;
}

if ($action === "clear") {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}

// --- Termékek betöltése DB-ből a kosár alapján (MINDIG, még checkout előtt) ---
$cartIds = array_keys($_SESSION["cart"]);
$products = [];

if (count($cartIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $types = str_repeat('i', count($cartIds));
    $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL hiba (prepare)");
    }

    $params = [];
    $params[] = &$types;

    // bind_param-hoz referenciák kellenek
    foreach ($cartIds as $index => $pid) {
        $cartIds[$index] = (int)$pid;
        $params[] = &$cartIds[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $params);
    $stmt->execute();

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[(int)$row["id"]] = $row;
    }
    $stmt->close();
}

if ($action === "checkout") {
    if (empty($_SESSION["cart"])) {
        header("Location: cart.php");
        exit;
    }

    if (empty($STRIPE_SECRET_KEY)) {
        die("Stripe secret key nincs beállítva (\$STRIPE_SECRET_KEY).");
    }

    \Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);

    $lineItems = [];
    foreach ($_SESSION["cart"] as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;

        if (!isset($products[$pid])) {
            continue;
        }

        $unitAmount = (int) round((float)$products[$pid]["price"]);

        if ($unitAmount <= 0 || $qty <= 0) {
            continue;
        }

        $lineItems[] = [
            'price_data' => [
                'currency' => 'huf',
                'product_data' => [
                    'name' => (string)$products[$pid]['name'],
                ],
                'unit_amount' => $unitAmount*100, //miért egy ekkora szar
            ],
            'quantity' => $qty,
        ];
    }

    if (count($lineItems) === 0) {
        die("Stripe checkout nem indítható: üres line_items (hiányzó termékek vagy 0 ár).");
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    $checkoutSession = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'line_items' => $lineItems,
        'success_url' => $scheme . "://{$host}/success.php?session_id={CHECKOUT_SESSION_ID}",
        'cancel_url'  => $scheme . "://{$host}/cart.php?transaction=cancelled",
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkoutSession->url);
    exit;
}

$VAT_RATE = 0.27;

$grossTotal = 0.0;
foreach ($_SESSION["cart"] as $pid => $quantity) {
    $pid = (int)$pid;
    $quantity = (int)$quantity;
    if (isset($products[$pid])) {
        $grossTotal += (float)$products[$pid]["price"] * $quantity;
    }
}

$netTotal = ($grossTotal > 0) ? ($grossTotal / (1 + $VAT_RATE)) : 0;
$vatTotal = $grossTotal - $netTotal;

$grossTotalRounded = round($grossTotal);
$netTotalRounded = round($netTotal);
$vatTotalRounded = $grossTotalRounded - $netTotalRounded;

?>
<!DOCTYPE html>
<html lang="hu">
<?php include_once("components/head.php"); ?>
<body>
<?php include_once("components/navbar.php"); ?>

<div class="cart-page">
    <div class="cart-header">
        <h1>Kosár</h1>
        <?php if (count($_SESSION["cart"]) > 0): ?>
            <a href="?action=clear" class="cart-clear">Kosár ürítése</a>
        <?php endif; ?>
    </div>

    <?php if (count($_SESSION["cart"]) === 0): ?>
        <div class="cart-empty">
            <p>A kosarad jelenleg üres.</p>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($_SESSION["cart"] as $pid => $qty): ?>
                <?php
                    $pid = (int)$pid;
                    $qty = (int)$qty;
                    if (!isset($products[$pid])) continue;

                    $p = $products[$pid];
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

            <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="checkout">
                <button class="addToCart" type="submit" style="width:100%;">
                    <i class="bx bx-credit-card"></i> Fizetés
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>