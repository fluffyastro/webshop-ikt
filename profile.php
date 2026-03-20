<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}


?>

<?php
include_once("components/navbar.php");
?>

<head>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2>Profil</h2>
            <p><strong>Felhasználó:</strong> <?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Azonosító:</strong> <?= (int)($_SESSION['user_id'] ?? 0) ?></p>

            <?php if (!empty($_SESSION['email'])): ?>
                <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <div class="link" style="margin-top: 15px;">
                <a href="index.php">Főoldal</a>
            </div>
            <div class="link" style="margin-top: 10px;">
                <a href="logout.php">Kijelentkezés</a>
            </div>
        </div>
    </div>
</body>
</html>