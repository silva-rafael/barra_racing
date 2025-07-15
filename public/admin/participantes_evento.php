<?php
// Ativar erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. INCLUIR DEPENDÊNCIAS
require_once '../../config/database.php';
require_once '../../lib/Auth.php';
require_once '../../lib/Evento.php';
require_once '../../lib/Compra.php';

// 2. PROTEGER E PREPARAR
$auth = new Auth($pdo);
$auth->protegerAdmin();

$eventoObj = new Evento($pdo);
$compraObj = new Compra($pdo);
$mensagem = '';
$tipoMensagem = '';

// 3. VALIDAR O ID DO EVENTO
$eventoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventoId) {
    header("Location: gerenciar_eventos.php");
    exit();
}

// 4. PROCESSAR AÇÕES (MARCAR COMO PAGO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_pago'])) {
    $compraId = filter_input(INPUT_POST, 'compra_id', FILTER_VALIDATE_INT);
    if ($compraId && $compraObj->atualizarStatusPagamento($compraId, 'pago')) {
        $mensagem = "Status da compra #$compraId atualizado para PAGO com sucesso!";
        $tipoMensagem = 'success';
    } else {
        $mensagem = "Erro ao atualizar o status da compra.";
        $tipoMensagem = 'error';
    }
}

// 5. BUSCAR DADOS PARA EXIBIÇÃO
$evento = $eventoObj->buscarPorId($eventoId);
if (!$evento) {
    header("Location: gerenciar_eventos.php?status=nao_encontrado");
    exit();
}
$participantes = $compraObj->listarParticipantesPorEvento($eventoId);

include '../../templates/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Participantes do Evento: <?php echo htmlspecialchars($evento->nome); ?></h2>
        <a href="gerenciar_eventos.php">« Voltar para a lista de eventos</a>
    </div>
    <p>Lista de todas as compras de ingressos para este evento.</p>
    <hr style="border-color: var(--border-color); margin-bottom: 2rem;">

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <div class="form-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Pedido ID</th>
                    <th>Participante</th>
                    <th>Email</th>
                    <th>Ingressos</th>
                    <th>Status Pag.</th>
                    <th>Comprovante</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($participantes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Nenhum ingresso foi comprado para este evento ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($participantes as $p): ?>
                        <tr>
                            <td>#<?php echo $p->compra_id; ?></td>
                            <td><?php echo htmlspecialchars($p->usuario_nome); ?></td>
                            <td><?php echo htmlspecialchars($p->usuario_email); ?></td>
                            <td><?php echo htmlspecialchars($p->ingressos_comprados); ?></td>
                            <td>
                                <!-- Badge de Status -->
                                <span class="status-badge status-<?php echo $p->status_pagamento; ?>">
                                    <?php echo ucfirst($p->status_pagamento); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p->status_pagamento === 'pendente'): ?>
                                    <form action="participantes_evento.php?id=<?php echo $eventoId; ?>" method="post" style="margin: 0;">
                                        <input type="hidden" name="compra_id" value="<?php echo $p->compra_id; ?>">
                                        <button type="submit" name="marcar_pago" class="btn-acao btn-participantes" style="width: auto;">Marcar como Pago</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p->comprovante_url)): ?>
                                    <a href="<?php echo htmlspecialchars($p->comprovante_url); ?>" target="_blank" class="btn-acao btn-editar">
                                        Ver Arquivo
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Adicione este estilo no final, dentro de <style> se quiser, ou no seu CSS
?>
<style>
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px;
        border: 1px solid var(--border-color);
        text-align: left;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        color: white;
        font-size: 0.8em;
        text-transform: uppercase;
    }

    .status-pago {
        background-color: #4CAF50;
    }

    .status-pendente {
        background-color: #FFC107;
        color: #333;
    }

    .status-cancelado {
        background-color: #F44336;
    }
</style>

<?php include '../../templates/footer.php'; ?>