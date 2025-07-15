<?php
require_once '../config/database.php';
require_once '../lib/Auth.php';
require_once '../lib/Compra.php';

$auth = new Auth($pdo);
$auth->proteger();

$compraObj = new Compra($pdo);
$compras = $compraObj->listarComprasPorUsuario($_SESSION['user_id']);
$status = $_GET['status'] ?? '';
?>

<?php include '../templates/header.php'; ?>

<div class="container">
    <h2>Meus Ingressos</h2>

    <?php if ($status === 'sucesso'): ?>
        <p class="success">Compra realizada com sucesso! ID do Pedido: <?php echo htmlspecialchars($_GET['compra_id']); ?></p>
    <?php endif; ?>

    <?php if (empty($compras)): ?>
        <p>Você ainda não comprou nenhum ingresso.</p>
        <a href="index.php">Ver eventos disponíveis</a>
    <?php else: ?>
        <?php foreach ($compras as $compra_id => $compra): ?>
            <div class="compra-card">
                <h3>Pedido #<?php echo $compra_id; ?></h3>
                <p><strong>Data da Compra:</strong> <?php echo date('d/m/Y H:i', strtotime($compra['info']['data_compra'])); ?></p>

                <table>
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Tipo de Ingresso</th>
                            <th>Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compra['itens'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item->evento_nome); ?></td>
                                <td><?php echo htmlspecialchars($item->ingresso_nome); ?></td>
                                <td><?php echo $item->quantidade; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="total-compra"><strong>Total do Pedido:</strong> R$ <?php echo number_format($compra['info']['valor_total'], 2, ',', '.'); ?></p>
                ?>
                <div style="text-align: right; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                    <?php if ($compra['info']['status_pagamento'] === 'pendente'): ?>
                        <?php if (empty($compra['info']['comprovante_url'])): ?>
                            <a href="enviar_comprovante.php?compra_id=<?php echo $compra_id; ?>" class="btn-acao btn-editar">
                                Enviar Comprovante
                            </a>
                        <?php else: ?>
                            <span class="status-badge status-pendente">Comprovante enviado. Aguardando aprovação.</span>
                        <?php endif; ?>
                    <?php elseif ($compra['info']['status_pagamento'] === 'pago'): ?>
                        <span class="status-badge status-pago">Pagamento Aprovado!</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../templates/footer.php'; ?>