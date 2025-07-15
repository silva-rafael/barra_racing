<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../lib/Auth.php';
require_once '../lib/Compra.php';
require_once '../lib/ProofUploader.php';

$auth = new Auth($pdo);
$auth->proteger(); // Apenas usuários logados

$compraObj = new Compra($pdo);
$mensagem = '';
$tipoMensagem = '';
$usuarioId = $_SESSION['user_id'];

// Valida o ID da compra
$compraId = filter_input(INPUT_GET, 'compra_id', FILTER_VALIDATE_INT);
if (!$compraId) {
    header("Location: meus_ingressos.php");
    exit();
}

// Verifica se o usuário é o dono da compra
$sqlCheckOwner = "SELECT id FROM compras WHERE id = :compra_id AND usuario_id = :usuario_id";
$stmtCheck = $pdo->prepare($sqlCheckOwner);
$stmtCheck->execute([':compra_id' => $compraId, ':usuario_id' => $usuarioId]);
if ($stmtCheck->rowCount() === 0) {
    header("Location: meus_ingressos.php?status=nao_autorizado");
    exit();
}

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $comprovanteUrl = uploadComprovante($_FILES['comprovante']);
            
            if ($compraObj->anexarComprovante($compraId, $usuarioId, $comprovanteUrl)) {
                header("Location: meus_ingressos.php?status=comprovante_enviado");
                exit();
            } else {
                throw new Exception("Não foi possível salvar a referência do comprovante.");
            }
        } else {
            throw new Exception("Nenhum arquivo válido foi enviado.");
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = 'error';
    }
}

include '../templates/header.php';
?>
<div class="container">
    <h2>Enviar Comprovante de Pagamento</h2>
    <p>Para o pedido de compra <strong>#<?php echo $compraId; ?></strong>.</p>
    <p>Por favor, envie uma imagem (JPG, PNG) ou PDF do seu comprovante.</p>
    <hr style="border-color: var(--border-color); margin-bottom: 2rem;">

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <form class="form-container" method="post" enctype="multipart/form-data">
        <label for="comprovante">Selecione o arquivo do comprovante</label>
        <input type="file" id="comprovante" name="comprovante" accept="image/jpeg, image/png, application/pdf" required>
        <br><br>
        <button type="submit">Enviar Arquivo</button>
    </form>
</div>
<?php include '../templates/footer.php'; ?>