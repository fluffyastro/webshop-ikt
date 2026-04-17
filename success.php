<?php
session_start();

include_once("helpers/db.php");
require_once("helpers/stripe/init.php");

if (empty($STRIPE_SECRET_KEY)) {
    http_response_code(500);
    die("Stripe secret key nincs beállítva (\$STRIPE_SECRET_KEY).");
}

\Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);

$sessionId = $_GET["session_id"] ?? null;
$sessionId = is_string($sessionId) ? trim($sessionId) : null;

$session = null;
$paymentStatus = "unknown";
$errorMsg = null;

if (!$sessionId) {
    $errorMsg = "Hiányzó session_id. Kérlek, indítsd újra a fizetést a kosárból.";
} else {
    try {
        $session = \Stripe\Checkout\Session::retrieve([
            'id' => $sessionId,
            'expand' => ['payment_intent'],
        ]);

        $paymentStatus = $session->payment_status ?? "unknown";

        if ($paymentStatus === "paid") {
            $_SESSION["cart"] = [];
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $errorMsg = "Stripe hiba: " . $e->getMessage();
    } catch (\Exception $e) {
        $errorMsg = "Szerver hiba: " . $e->getMessage();
    }
}

function formatStripeAmount(?int $amountTotal, ?string $currency): string
{
    if ($amountTotal === null || $currency === null) return "";

    $cur = strtoupper($currency);

    if ($cur === "HUF") {
        return number_format($amountTotal / 100, 2, ',', ' ') . " Ft";
    }

    return number_format($amountTotal / 100, 2, '.', ' ') . " " . htmlspecialchars($cur);
}

?>
<!DOCTYPE html>
<html lang="hu">
<?php include_once("components/head.php"); ?>
<body>
<?php include_once("components/navbar.php"); ?>

<div class="cart-page">
    <div class="cart-header">
        <h1>Fizetés</h1>
        <a href="cart.php" class="cart-clear">Vissza a kosárhoz</a>
    </div>

    <?php if ($errorMsg): ?>
        <div class="cart-empty">
            <i class="bx bx-x-circle error"></i>
            <p style="margin-top: 12px; font-weight: 800;">Sikertelen</p>
            <p style="color: var(--subtle); max-width: 720px; margin: 12px auto 0;">
                <?= htmlspecialchars($errorMsg) ?>
            </p>

            <a class="cart-back" href="cart.php">Kosár</a>
        </div>

    <?php else: ?>
        <?php if ($paymentStatus === "paid"): ?>
            <div class="cart-empty">
                <i class="bx bx-check-circle" style="font-size: 2.5rem; color: #16a34a;"></i>
                <p style="margin-top: 12px; font-weight: 900;">Sikeres fizetés</p>
                <p style="color: var(--subtle); max-width: 720px; margin: 12px auto 0;">
                    Köszönjük! A fizetés sikeresen megtörtént.
                </p>

                <div style="margin-top: 18px; color: var(--subtle); font-weight: 700;">
                    <div>Stripe session: <span style="color: var(--text); font-weight: 900;"><?= htmlspecialchars($session->id) ?></span></div>

                    <?php if (isset($session->amount_total) && isset($session->currency)): ?>
                        <div style="margin-top: 8px;">
                            Összeg: <span style="color: var(--text); font-weight: 900;">
                                <?= formatStripeAmount((int)$session->amount_total, (string)$session->currency) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <a class="cart-back" href="/">Vissza a főoldalra</a>
            </div>

        <?php else: ?>
            <div class="cart-empty">
                <i class="bx bx-time-five" style="font-size: 2.5rem; color: #9a6a00;"></i>
                <p style="margin-top: 12px; font-weight: 900;">
                    Fizetés állapota: <?= htmlspecialchars($paymentStatus) ?>
                </p>
                <p style="color: var(--subtle); max-width: 720px; margin: 12px auto 0;">
                    Ha megszakadt a fizetés, próbáld újra a kosárból.
                </p>

                <a class="cart-back" href="cart.php">Vissza a kosárhoz</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>