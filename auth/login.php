<?php
session_start();
require_once(__DIR__ . '/../helpers/config.php');

if (isset($_SESSION['user_id'])) {
    header("Location: ../profile.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = "Minden mező kötelező!";
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, username, email, password FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            header("Location: ../profile.php");
            exit();
        } else {
            $error = "Hibás felhasználónév/email vagy jelszó.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Bejelentkezés</h2>

            <?php if ($error !== ''): ?>
                <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <p class="success">Regisztráció sikeres! Jelentkezz be!</p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="identifier">Felhasználónév vagy Email</label>
                    <input type="text" id="identifier" name="identifier" required>
                </div>

                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn">Bejelentkezés</button>
            </form>

            <div class="link">
                <a href="register.php">Nincs fiókod? Regisztráció</a>
            </div>

            <div class="link" style="margin-top:10px;">
                <a href="../index.php">Vissza a főoldalra</a>
            </div>
        </div>
    </div>
</body>
</html>