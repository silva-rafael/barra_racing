<?php
// Ativar erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. INCLUIR TODAS AS DEPENDÊNCIAS NECESSÁRIAS
require_once '../config/database.php';
require_once '../lib/Auth.php';
// A LINHA ABAIXO ESTAVA FALTANDO OU EM ORDEM INCORRETA:
require_once '../lib/Evento.php'; 

// 2. INICIAR A LÓGICA DA PÁGINA
$auth = new Auth($pdo);
$eventoObj = new Evento($pdo); // <-- Linha 9 (ou próxima a ela) que causava o erro

// 3. PEGAR O ID DO EVENTO E VALIDAR
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    // Se não houver um ID válido, redireciona para a página inicial
    header('Location: index.php');
    exit();
}

// 4. BUSCAR OS DADOS DO EVENTO E SEUS INGRESSOS
$evento = $eventoObj->buscarPorId($id);
// Se o evento com o ID fornecido não existir, redireciona também
if (!$evento) {
    header('Location: index.php?status=not_found');
    exit();
}
$ingressos = $eventoObj->buscarTiposIngresso($id);

// 5. INCLUIR O CABEÇALHO
// O header já cuida de iniciar a sessão e definir as variáveis de login
include '../templates/header.php';
?>

<!-- HTML da página -->
<div class="container">
    <a href="index.php">« Voltar para a lista de eventos</a>
    <br><br>
    
    <h2><?php echo htmlspecialchars($evento->nome); ?></h2>
    <p><strong>Data:</strong> <?php echo date('d/m/Y \à\s H:i', strtotime($evento->data_evento)); ?></p>
    <p><strong>Local:</strong> <?php echo htmlspecialchars($evento->local); ?></p>
    <p><strong>Modalidade:</strong> <?php echo htmlspecialchars($evento->modalidade_nome); ?></p>
    <div>
        <h4>Descrição do Evento</h4>
        <p><?php echo nl2br(htmlspecialchars($evento->descricao)); ?></p>
    </div>

    <hr>
    
    <h3>Comprar Ingressos</h3>
    
    <?php if (!$auth->estaLogado()): ?>
        <p class="error">Você precisa fazer <a href="login.php">login</a> para comprar ingressos.</p>
    <?php else: ?>
        <form action="carrinho.php" method="post">
            <input type="hidden" name="evento_id" value="<?php echo $evento->id; ?>">
            
            <?php if (empty($ingressos)): ?>
                <p>Os ingressos para este evento ainda não foram liberados.</p>
            <?php else: ?>
                <?php foreach ($ingressos as $ingresso): ?>
                    <div class="tipo-ingresso">
                        <strong><?php echo htmlspecialchars($ingresso->nome); ?></strong> - 
                        R$ <?php echo number_format($ingresso->preco, 2, ',', '.'); ?>
                        
                        <?php if ($ingresso->quantidade_disponivel > 0): ?>
                            <div class="controle-quantidade">
                                <label for="qtd_<?php echo $ingresso->id; ?>">Qtd:</label>
                                <input type="number" id="qtd_<?php echo $ingresso->id; ?>" name="quantidade[<?php echo $ingresso->id; ?>]" value="0" min="0" max="<?php echo $ingresso->quantidade_disponivel; ?>">
                                <span>(<?php echo $ingresso->quantidade_disponivel; ?> disponíveis)</span>
                            </div>
                        <?php else: ?>
                            <strong class="esgotado">ESGOTADO</strong>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <br>
                <button type="submit">Adicionar ao Carrinho</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<?php
// 6. INCLUIR O RODAPÉ
include '../templates/footer.php';
?>