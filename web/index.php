<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tres en raya</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Tres en raya</h1>
      <p class="helper">Regístrate, inicia sesión y juega contra la máquina.</p>
    </div>
    <div class="content">
      <?php if (isset($_SESSION['username'])): ?>
        <div class="success">Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 👋</div>
        <div class="row" style="margin-top:16px;">
          <a class="btn" href="game.php">🎮 Jugar</a>
          <a class="btn" href="stats.php">📈 Estadísticas</a>
          <a class="btn" href="logout.php">🚪 Salir</a>
        </div>
      <?php else: ?>
        <div class="row">
          <a class="btn" href="register.php">📝 Registrarse</a>
          <a class="btn" href="login.php">🔑 Iniciar sesión</a>
        </div>
      <?php endif; ?>
    </div>
    <div class="footer"></div>
  </div>
</body>
</html>


