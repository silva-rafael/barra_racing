<?php
require_once '../config/database.php';
require_once '../lib/Auth.php';
require_once '../lib/Evento.php';
require_once '../lib/Compra.php';

$auth = new Auth($pdo);
$auth->proteger(); // Apenas usuários logados podem acessar o carrinho

$eventoObj = new Evento($pdo);
$compraObj = new Compra($pdo);

$carrinho = [];
$valorTotal = 0;
$erro = '';

// Se a ação for 'finalizar' (usuário clicou em "Finalizar Compra")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'finalizar') {
    $carrinhoFinal = unserialize(base64_decode($_POST['carrinho_data']));
    $valorTotalFinal = $_POST['valor_total'];
    $usuarioId = $_SESSION['user_id'];

    $compraId = $compraObj->finalizarCompra($usuarioId, $carrinhoFinal, $valorTotalFinal);

    if ($compraId) {
        // Sucesso! Redireciona para a página de ingressos com uma mensagem.
        header("Location: meus_ingressos.php?status=sucesso&compra_id=" . $compraId);
        exit();
    } else {
        // Falha (provavelmente por falta de estoque)
        $erro = "Não foi possível finalizar a compra. Alguns ingressos podem ter se esgotado. Por favor, tente novamente.";
        // Recarrega os dados do carrinho para exibição do erro
        $carrinho = $carrinhoFinal;
        $valorTotal = $valorTotalFinal;
    }
} 
// Se for o primeiro acesso à página (vindo de evento.php)
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventoId = $_POST['evento_id'];
    $quantidades = $_POST['quantidade'];

    foreach ($quantidades as $ingressoId => $qtd) {
        if ($qtd > 0) {
            // Busca detalhes do ingresso no banco para garantir o preço correto
            $stmt = $pdo->prepare("SELECT * FROM tipos_ingressos WHERE id = :id AND evento_id = :evento_id");
            $stmt->execute([':id' => $ingressoId, ':evento_id' => $eventoId]);
            $ingresso = $stmt->fetch();

            if ($ingresso) {
                $carrinho[] = [
                    'id' => $ingresso->id,
                    'nome' => $ingresso->nome,
                    'quantidade' => (int)$qtd,
                    'preco' => (float)$ingresso->preco,
                    'subtotal' => (int)$qtd * (float)$ingresso->preco
                ];
                $valorTotal += (int)$qtd * (float)$ingresso->preco;
            }
        }
    }
    if (empty($carrinho)) {
        header("Location: evento.php?id=$eventoId&status=carrinho_vazio");
        exit();
    }
} else {
    // Se o usuário acessar a URL diretamente sem dados, redireciona
    header("Location: index.php");
    exit();
}

include '../templates/header.php';
?>

<div class="container">
    <h2>Resumo da Compra</h2>
    <?php if ($erro): ?><p class="error"><?php echo $erro; ?></p><?php endif; ?>

    <?php if (!empty($carrinho)): ?>
        <table>
            <thead>
                <tr>
                    <th>Ingresso</th>
                    <th>Quantidade</th>
                    <th>Preço Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carrinho as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td><?php echo $item['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Valor Total</th>
                    <th>R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>

        <form action="carrinho.php" method="post">
            <!-- Passamos os dados do carrinho de forma segura para o próximo passo -->
            <input type="hidden" name="acao" value="finalizar">
            <input type="hidden" name="carrinho_data" value="<?php echo base64_encode(serialize($carrinho)); ?>">
            <input type="hidden" name="valor_total" value="<?php echo $valorTotal; ?>">
            <button type="submit">Finalizar Compra</button>
        </form>
    <?php else: ?>
        <p>Seu carrinho está vazio.</p>
        <a href="index.php">Ver eventos</a>
    <?php endif; ?>
</div>

<?php include '../templates/footer.php'; ?>