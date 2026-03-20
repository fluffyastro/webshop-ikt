<?php
session_start();
require_once(__DIR__ . '/../helpers/config.php');

if (isset($_SESSION['user_id'])) {
    header("Location: ../profile.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $password2 === '') {
        $error = "Minden mező kitöltése kötelező!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Érvénytelen email cím!";
    } elseif ($password !== $password2) {
        $error = "A két jelszó nem egyezik!";
    } elseif (strlen($password) < 6) {
        $error = "A jelszónak legalább 6 karakter hosszúnak kell lennie!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "Ez a felhasználónév vagy email már foglalt.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
            );
            $stmt->execute([$username, $email, $hashedPassword]);

            header("Location: login.php?success=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Regisztráció</h2>

            <?php if ($error !== ''): ?>
                <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Felhasználónév</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="password2">Jelszó újra</label>
                    <input type="password" id="password2" name="password2" required>
                </div>

                <button type="submit" class="btn">Regisztráció</button>
            </form>

            <div class="link">
                <a href="login.php">Van már fiókod? Bejelentkezés</a>
            </div>
        </div>
    </div>
</body>
</html>