<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Minden mező kötelező!";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Hibás email formátum!";
    } 
    elseif (strlen($password) < 6) {
        $error = "A jelszónak legalább 6 karakter hosszúnak kell lennie!";
    } 
    else {

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $error = "A felhasználónév vagy email már létezik!";
        } 
        else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                header("Location: login.php?success=1");
                exit;
            } else {
                $error = "Regisztráció sikertelen!";
            }
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Regisztráció</h2>

            <?php if (isset($error)) : ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST">
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

                <button type="submit" class="btn">Regisztráció</button>
            </form>
            
            <div class="link">
                <a href="login.php">Van már fiókod? Bejelentkezés</a>
            </div>
        </div>
    </div>
</body>
</html>