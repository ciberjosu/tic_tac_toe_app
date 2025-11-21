Archivo db.php (conexión a MySQL)

<?php
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'tictactoe';
$user = getenv('DB_USER') ?: 'appuser';
$pass = getenv('DB_PASS') ?: 'apppass';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Error de conexión: ' . $mysqli->connect_error);
}
?>
**********************************************************
Archivo schema.sql (creación de tablas)

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  result ENUM('win', 'loss', 'draw') NOT NULL,
  played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
**********************************************************
Archivo index.php (home con links)

<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tres en raya</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <h1>Tres en raya</h1>
  <?php if (isset($_SESSION['username'])): ?>
    <p>Hola, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    <p>
      <a href="game.php">Jugar</a> |
      <a href="logout.php">Salir</a>
    </p>
  <?php else: ?>
    <p>
      <a href="register.php">Registrarse</a> |
      <a href="login.php">Iniciar sesión</a>
    </p>
  <?php endif; ?>
</body>
</html>
**********************************************************
Archivo register.php (registro de usuario)

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
<head><meta charset="UTF-8"><title>Registro</title></head>
<body>
  <h2>Registrarse</h2>
  <p style="color:red;"><?php echo htmlspecialchars($msg); ?></p>
  <form method="post">
    <label>Usuario: <input type="text" name="username" required></label><br>
    <label>Contraseña: <input type="password" name="password" required></label><br>
    <button type="submit">Crear cuenta</button>
  </form>
  <p><a href="index.php">Volver</a></p>
</body>
</html>
**********************************************************
Archivo login.php (inicio de sesión)

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
<head><meta charset="UTF-8"><title>Login</title></head>
<body>
  <h2>Iniciar sesión</h2>
  <p style="color:red;"><?php echo htmlspecialchars($msg); ?></p>
  <form method="post">
    <label>Usuario: <input type="text" name="username" required></label><br>
    <label>Contraseña: <input type="password" name="password" required></label><br>
    <button type="submit">Entrar</button>
  </form>
  <p><a href="index.php">Volver</a></p>
</body>
</html>
**********************************************************
Archivo logout.php (cerrar sesión)

<?php
session_start();
session_unset();
session_destroy();
header('Location: index.php');
**********************************************************
Archivo game.php (interfaz del juego y lógica simple del bot)

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Jugar tres en raya</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .board { display:grid; grid-template-columns:repeat(3,80px); gap:8px; }
    .cell { width:80px; height:80px; display:flex; align-items:center; justify-content:center; font-size:28px; border:1px solid #333; cursor:pointer; }
  </style>
</head>
<body>
  <h2>Tres en raya</h2>
  <p>Jugador: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
  <div class="board" id="board"></div>
  <p id="status"></p>
  <button id="reset">Reiniciar</button>
  <script>
    const boardEl = document.getElementById('board');
    const statusEl = document.getElementById('status');
    const resetBtn = document.getElementById('reset');
    let board = Array(9).fill('');
    let gameOver = false;

    const wins = [
      [0,1,2],[3,4,5],[6,7,8],
      [0,3,6],[1,4,7],[2,5,8],
      [0,4,8],[2,4,6]
    ];

    function render() {
      boardEl.innerHTML = '';
      board.forEach((mark, i) => {
        const div = document.createElement('div');
        div.className = 'cell';
        div.textContent = mark;
        div.addEventListener('click', () => move(i));
        boardEl.appendChild(div);
      });
    }

    function checkResult() {
      for (const [a,b,c] of wins) {
        if (board[a] && board[a] === board[b] && board[b] === board[c]) {
          return board[a] === 'X' ? 'win' : 'loss';
        }
      }
      return board.every(x => x) ? 'draw' : null;
    }

    function botMove() {
      // Estrategia simple: ganar, bloquear, centro, esquinas, aleatorio
      const emptyIdx = board.map((v,i)=> v===''?i:null).filter(v=>v!==null);
      // Intentar ganar
      for (const [a,b,c] of wins) {
        const line = [board[a],board[b],board[c]];
        if (line.filter(x=>x==='O').length===2 && line.includes('')) {
          const idx = [a,b,c][line.indexOf('')];
          board[idx] = 'O'; return;
        }
      }
      // Bloquear
      for (const [a,b,c] of wins) {
        const line = [board[a],board[b],board[c]];
        if (line.filter(x=>x==='X').length===2 && line.includes('')) {
          const idx = [a,b,c][line.indexOf('')];
          board[idx] = 'O'; return;
        }
      }
      // Centro
      if (board[4]==='') { board[4]='O'; return; }
      // Esquinas
      const corners = [0,2,6,8].filter(i => board[i]==='');
      if (corners.length) { board[corners[0]]='O'; return; }
      // Aleatorio
      if (emptyIdx.length) { board[emptyIdx[Math.floor(Math.random()*emptyIdx.length)]]='O'; }
    }

    function move(i) {
      if (gameOver || board[i] !== '') return;
      board[i] = 'X';
      let r = checkResult();
      if (r) endGame(r);
      else {
        botMove();
        r = checkResult();
        if (r) endGame(r);
        else render();
      }
    }

    function endGame(result) {
      gameOver = true;
      statusEl.textContent = result === 'win' ? '¡Has ganado!' : (result === 'loss' ? 'Has perdido.' : 'Empate.');
      fetch('save_result.php', {
        method: 'POST',
        headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        body: 'result=' + encodeURIComponent(result)
      }).catch(()=>{});
      render();
    }

    resetBtn.addEventListener('click', () => {
      board = Array(9).fill('');
      gameOver = false;
      statusEl.textContent = '';
      render();
    });

    render();
  </script>
  <p><a href="index.php">Volver</a></p>
</body>
</html>
**********************************************************
Archivo save_result.php (guardar resultado)

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('No autenticado');
}
require 'db.php';

$result = $_POST['result'] ?? '';
if (!in_array($result, ['win','loss','draw'], true)) {
    http_response_code(400);
    exit('Resultado inválido');
}

$stmt = $mysqli->prepare('INSERT INTO results (user_id, result) VALUES (?, ?)');
$stmt->bind_param('is', $_SESSION['user_id'], $result);
$stmt->execute();
echo 'OK';
**********************************************************
Archivo assets/styles.css (estilos simples)

body { font-family: Arial, sans-serif; margin: 24px; }
h1, h2 { margin-bottom: 8px; }
a { margin-right: 8px; }
**********************************************************
Fase 3: Dockerfile de la web (PHP + Apache)

# web/Dockerfile
FROM php:8.2-apache

# Extensiones necesarias para MySQLi
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Activar mod_rewrite si lo necesitas
RUN a2enmod rewrite

# Copiar código al contenedor
COPY . /var/www/html/

# Permisos básicos (opcional)
RUN chown -R www-data:www-data /var/www/html

# Puerto por defecto de Apache
EXPOSE 80
**********************************************************
Fase 4: Docker Compose (web + db + volumen)

# docker-compose.yml
services:
  web:
    build: ./web
    ports:
      - "8080:80"
    depends_on:
      - db
    environment:
      DB_HOST: db
      DB_NAME: tictactoe
      DB_USER: appuser
      DB_PASS: apppass
    volumes:
      - ./web:/var/www/html
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: tictactoe
      MYSQL_USER: appuser
      MYSQL_PASSWORD: apppass
    volumes:
      - db_data:/var/lib/mysql
      - ./web/schema.sql:/docker-entrypoint-initdb.d/schema.sql:ro

volumes:
  db_data:
**********************************************************
Construir y levantar el entorno:
sudo docker compose up -d --build

comprobar contenedores:
docker ps

Entrar a MySQL para verificar:
docker exec -it $(docker ps --filter "ancestor=mysql:8.0" -q) mysql -u appuser -p
# Contraseña: apppass
# Dentro de MySQL:
USE tictactoe;
SHOW TABLES;
SELECT * FROM users;
SELECT result, COUNT(*) FROM results GROUP BY result;

