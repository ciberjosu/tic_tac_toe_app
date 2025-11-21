<?php
session_start();
require 'db.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare('SELECT id, password_hash FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash);
    if ($stmt->fetch()) {
        if (password_verify($password, $hash)) {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $id;
            header('Location: index.php');
            exit;
        } else {
            $msg = 'Credenciales inválidas.';
        }
    } else {
        $msg = 'Usuario no encontrado.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <h2>Iniciar sesión</h2>
    <?php if ($msg): ?><p class="error"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Usuario" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <button type="submit">Entrar</button>
    </form>
    <p><a href="index.php">Volver</a></p>
  </div>
</body>
</html>

