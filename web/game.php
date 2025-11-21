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
  <title>Tres en raya</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <h2>Tres en raya</h2>
    <p>Jugador: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
    <div class="board" id="board"></div>
    <p id="status"></p>
    <button id="reset">Reiniciar</button>
    <p><a href="index.php">Volver</a></p>
  </div>
  <script>
    const boardEl = document.getElementById('board');
    const statusEl = document.getElementById('status');
    const resetBtn = document.getElementById('reset');
    let board = Array(9).fill('');
    let gameOver = false;
    const wins = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
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
      const emptyIdx = board.map((v,i)=> v===''?i:null).filter(v=>v!==null);
      for (const [a,b,c] of wins) {
        const line = [board[a],board[b],board[c]];
        if (line.filter(x=>x==='O').length===2 && line.includes('')) {
          const idx = [a,b,c][line.indexOf('')];
          board[idx] = 'O'; return;
        }
      }
      for (const [a,b,c] of wins) {
        const line = [board[a],board[b],board[c]];
        if (line.filter(x=>x==='X').length===2 && line.includes('')) {
          const idx = [a,b,c][line.indexOf('')];
          board[idx] = 'O'; return;
        }
      }
      if (board[4]==='') { board[4]='O'; return; }
      const corners = [0,2,6,8].filter(i => board[i]==='');
      if (corners.length) { board[corners[0]]='O'; return; }
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
      statusEl.textContent = result === 'win' ? '🎉 ¡Has ganado!' : (result === 'loss' ? '😢 Has perdido.' : '🤝 Empate.');
      fetch('save_result.php', {
        method: 'POST',
        headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        body: 'result=' + encodeURIComponent(result)
      }).catch(()=>{});
      render();
    }
    resetBtn.addEventListener('click',