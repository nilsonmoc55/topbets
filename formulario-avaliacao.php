<?php
// [Código anterior de verificação...]

// Busca critérios específicos da categoria
$criterios = $conn->query("
    SELECT * FROM criterios_categoria 
    WHERE categoria_id = $categoria_id
    ORDER BY peso DESC
");
?>

<!-- [Cabeçalho...] -->

<div class="container my-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3>Avaliar <?= htmlspecialchars($bet_nome) ?> - <?= htmlspecialchars($categoria_nome) ?></h3>
        </div>
        
        <div class="card-body">
            <form action="processa-avaliacao.php" method="post">
                <input type="hidden" name="bet_id" value="<?= $bet_id ?>">
                <input type="hidden" name="categoria_id" value="<?= $categoria_id ?>">
                
                <!-- Avaliação Geral -->
                <div class="mb-4 p-3 border rounded">
                    <h4 class="mb-3">Avaliação Geral</h4>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" id="nota-geral-<?= $i ?>" name="nota_geral" value="<?= $i ?>" required>
                        <label for="nota-geral-<?= $i ?>" class="rating-star">
                            <i class="far fa-star"></i>
                            <i class="fas fa-star"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="mt-3">
                        <label for="comentario_geral" class="form-label">Comentário Geral</label>
                        <textarea class="form-control" id="comentario_geral" name="comentario_geral" rows="3"></textarea>
                    </div>
                </div>
                
                <!-- Critérios Específicos -->
                <div class="mb-4">
                    <h4 class="mb-3">Avaliação Detalhada</h4>
                    
                    <?php while ($criterio = $criterios->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5><?= htmlspecialchars($criterio['pergunta']) ?></h5>
                            
                            <?php if ($criterio['tipo'] == 'estrelas'): ?>
                            <div class="rating-criterio" data-criterio-id="<?= $criterio['id'] ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" id="criterio-<?= $criterio['id'] ?>-<?= $i ?>" 
                                       name="criterios[<?= $criterio['id'] ?>]" value="<?= $i ?>">
                                <label for="criterio-<?= $criterio['id'] ?>-<?= $i ?>">
                                    <i class="far fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </label>
                                <?php endfor; ?>
                            </div>
                            
                            <?php elseif ($criterio['tipo'] == 'sim_nao'): ?>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="criterios[<?= $criterio['id'] ?>]" 
                                       id="sim-<?= $criterio['id'] ?>" value="1" autocomplete="off">
                                <label class="btn btn-outline-success" for="sim-<?= $criterio['id'] ?>">Sim</label>
                                
                                <input type="radio" class="btn-check" name="criterios[<?= $criterio['id'] ?>]" 
                                       id="nao-<?= $criterio['id'] ?>" value="0" autocomplete="off">
                                <label class="btn btn-outline-danger" for="nao-<?= $criterio['id'] ?>">Não</label>
                            </div>
                            
                            <?php else: ?>
                            <textarea class="form-control mt-2" 
                                      name="criterios[<?= $criterio['id'] ?>]" 
                                      placeholder="Sua resposta..."></textarea>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Enviar Avaliação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rating-group, .rating-criterio {
    display: inline-flex;
    flex-direction: row-reverse;
}
.rating-group input, .rating-criterio input {
    display: none;
}
.rating-group label, .rating-criterio label {
    cursor: pointer;
    color: #ccc;
    margin: 0 5px;
    position: relative;
}
.rating-group label i.fas, 
.rating-criterio label i.fas {
    display: none;
    color: #ffc107;
}
.rating-group input:checked ~ label i.far,
.rating-group input:hover ~ label i.far,
.rating-criterio input:checked ~ label i.far,
.rating-criterio input:hover ~ label i.far {
    display: none;
}
.rating-group input:checked ~ label i.fas,
.rating-group input:hover ~ label i.fas,
.rating-criterio input:checked ~ label i.fas,
.rating-criterio input:hover ~ label i.fas {
    display: inline-block;
}
</style>