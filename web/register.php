
<?php
session_start();
require 'db.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $msg = 'El usuario ya existe.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->bind_param('ss', $username, $hash);
            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $stmt->insert_id;
                header('Location: index.php');
                exit;
            } else {
                $msg = 'Error al registrar usuario.';
            }
        }
    } else {
        $msg = 'Usuario y contraseña son obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Crear cuenta</h2>
      <p class="helper">Te llevará menos de un minuto.</p>
    </div>
    <div class="content">
      <?php if ($msg): ?><p class="error"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
      <form method="post" class="form">
        <input class="input" type="text" name="username" placeholder="Usuario" required>
        <input class="input" type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Crear cuenta</button>
      </form>
      <p class="helper">¿Ya tienes cuenta? <a class="link" href="login.php">Inicia sesión</a></p>
    </div>
  </div>
</body>
</html>
