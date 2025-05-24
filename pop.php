<?php
session_start();
include __DIR__ . '/includes/conexao.php';

// Simulação de login (remova isso e use seu sistema real)
if (!isset($_SESSION['usuario_nome'])) {
    // Simule um usuário logado
    $_SESSION['usuario_nome'] = 'João da Silva';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Listar Bets com Sessão</title>
  <style>
    body {
      background-color: #111;
      color: #fff;
      font-family: Arial, sans-serif;
      text-align: center;
    }

    .center-btn {
      margin-top: 20%;
    }

    button {
      padding: 15px 30px;
      font-size: 18px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      padding-top: 100px;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.8);
    }

    .modal-content {
      background-color: #222;
      margin: auto;
      padding: 20px;
      border-radius: 10px;
      width: 80%;
      max-width: 800px;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .search-box {
      margin-bottom: 20px;
    }

    input[type="text"] {
      width: 100%;
      padding: 10px;
      font-size: 16px;
      border-radius: 8px;
      border: none;
    }

    .grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .card {
      background-color: #333;
      border-radius: 10px;
      padding: 15px;
      width: 45%;
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
    }

    .card img {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }

    #userModal .modal-content {
      max-width: 400px;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="center-btn">
    <button id="openModal">Abrir Lista de Bets</button>
  </div>

  <!-- MODAL 1 - Lista de Bets -->
  <div id="myModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeMain">&times;</span>
      <div class="search-box">
        <input type="text" id="search" placeholder="Buscar...">
      </div>
      <div id="results" class="grid">
        <?php
        $sql = "SELECT * FROM bets";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
          $logo = htmlspecialchars($row['logo']);
          $nome = htmlspecialchars($row['nome']);
          echo "
            <div class='card' onclick=\"showUserPopup('$nome')\">
              <img src='img/logos/{$logo}' alt='{$nome}'>
              <span>$nome</span>
            </div>
          ";
        }
        ?>
      </div>
    </div>
  </div>

  <!-- MODAL 2 - Sessão do usuário -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeUser">&times;</span>
      <h2 id="popupTitle">Sessão</h2>
      <p id="userContent">Verificando...</p>
    </div>
  </div>

  <script>
    const mainModal = document.getElementById("myModal");
    const userModal = document.getElementById("userModal");
    const openBtn = document.getElementById("openModal");
    const closeMain = document.getElementById("closeMain");
    const closeUser = document.getElementById("closeUser");
    const searchInput = document.getElementById("search");
    const userContent = document.getElementById("userContent");

    const cards = document.querySelectorAll("#results .card");

    openBtn.onclick = () => mainModal.style.display = "block";
    closeMain.onclick = () => mainModal.style.display = "none";
    closeUser.onclick = () => userModal.style.display = "none";

    window.onclick = (event) => {
      if (event.target == mainModal) mainModal.style.display = "none";
      if (event.target == userModal) userModal.style.display = "none";
    }

    searchInput.addEventListener("input", () => {
      const filter = searchInput.value.toLowerCase();
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(filter) ? "flex" : "none";
      });
    });

    function showUserPopup(nomeClicado) {
      // Fazer uma chamada AJAX para verificar sessão
      fetch("verificar_sessao.php")
        .then(response => response.json())
        .then(data => {
          if (data.logado) {
            userContent.innerHTML = `Olá, <strong>${data.nome}</strong>! Você clicou em <strong>${nomeClicado}</strong>.`;
          } else {
            userContent.innerHTML = "Você não está logado.";
          }
          userModal.style.display = "block";
        });
    }
  </script>

</body>
</html>
