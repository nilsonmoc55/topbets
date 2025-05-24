<?php
require_once 'includes/conexao.php';

$id_bet = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_bet <= 0) {
    echo "ID da bet inválido.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Formulário de Avaliação</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/3.3.4/jquery.rateyo.min.css">
    <style>
        .rateyo { margin-bottom: 15px; }
    </style>
</head>
<body class="p-4">
<div class="container">
    <h2>Avaliação da Plataforma</h2>
    <form method="POST" action="salvar_resposta.php">
        <input type="hidden" name="id_bet" value="<?= $id_bet ?>">

        <div class="mb-4">
            <label class="form-label">Tipo de Avaliação</label>
            <select class="form-select" id="tipo_avaliacao" name="tipo_avaliacao" onchange="exibirCampos()">
                <option value="">Selecione</option>
                <option value="cassino">Cassino Online</option>
                <option value="apostas">Apostas Esportivas</option>
            </select>
        </div>

        <!-- Área de Avaliação de Cassino -->
        <div id="secao_cassino" class="d-none">
            <h4>🃏 Avaliação de Cassino</h4>

            <div class="mb-3">
                <label for="bonus">Você recebeu bônus?</label>
                <select class="form-select" name="bonus" id="bonus" onchange="toggleBonusUso()">
                    <option value="">Selecione</option>
                    <option value="sim">Sim</option>
                    <option value="nao">Não</option>
                </select>
            </div>

            <div class="mb-3 d-none" id="bonus-uso-div">
                <label>Conseguiu usar ou sacar o bônus?</label>
                <select class="form-select" name="bonus_uso">
                    <option value="sim">Sim</option>
                    <option value="nao">Não</option>
                    <option value="parcial">Parcialmente</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Avaliação geral do cassino</label>
                <div id="estrela_cassino"></div>
                <input type="hidden" name="avaliacao_cassino" id="avaliacao_cassino">
            </div>

            <div class="mb-3 d-none" id="detalhe-cassino">
                <label>Deseja detalhar sua insatisfação?</label>
                <textarea name="detalhe_cassino" class="form-control"></textarea>
            </div>
        </div>

        <!-- Área de Avaliação de Apostas -->
        <div id="secao_apostas" class="d-none">
            <h4>⚽ Avaliação de Apostas Esportivas</h4>

            <div class="mb-3">
                <label>Qual jogo ou tipo de aposta você utilizou?</label>
                <input type="text" name="jogo_especifico" class="form-control" placeholder="Ex: Futebol, Roleta, Crash">
            </div>

            <div class="mb-3">
                <label>Avaliação geral das apostas</label>
                <div id="estrela_apostas"></div>
                <input type="hidden" name="avaliacao_apostas" id="avaliacao_apostas">
            </div>

            <div class="mb-3 d-none" id="detalhe-apostas">
                <label>Deseja detalhar sua insatisfação?</label>
                <textarea name="detalhe_apostas" class="form-control"></textarea>
            </div>
        </div>

        <hr>
        <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/3.3.4/jquery.rateyo.min.js"></script>
<script>
    function exibirCampos() {
        const tipo = document.getElementById("tipo_avaliacao").value;
        document.getElementById("secao_cassino").classList.toggle("d-none", tipo !== "cassino");
        document.getElementById("secao_apostas").classList.toggle("d-none", tipo !== "apostas");
    }

    function toggleBonusUso() {
        const valor = document.getElementById("bonus").value;
        document.getElementById("bonus-uso-div").classList.toggle("d-none", valor !== "sim");
    }

    $(function () {
        $("#estrela_cassino").rateYo({
            rating: 3,
            fullStar: true,
            starWidth: "25px",
            onSet: function (rating) {
                $('#avaliacao_cassino').val(rating);
                $('#detalhe-cassino').toggleClass('d-none', rating >= 3);
            }
        });

        $("#estrela_apostas").rateYo({
            rating: 3,
            fullStar: true,
            starWidth: "25px",
            onSet: function (rating) {
                $('#avaliacao_apostas').val(rating);
                $('#detalhe-apostas').toggleClass('d-none', rating >= 3);
            }
        });
    });
</script>
</body>
</html>
