<?php 
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = trim($_POST['identifier']); // username or email
    $password = $_POST['password'];

    // validálás
    if (empty($identifier) || empty($password)) {
        $error = "Minden mező kötelező!.";
    } else {
        // megnézi létezik-e a user
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.html");
            exit;
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
    <title>Login for projektgeci</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Bejelentkezés</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <?php if (isset($_GET['success'])) echo "<p class='success'>Regisztráció sikeres! Jelentkezz be!</p>"; ?>
            <form method="POST">
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
        </div>
</body>
</html>