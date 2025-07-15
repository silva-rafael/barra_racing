<?php
// Ativar erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../lib/Auth.php';
require_once '../../lib/Evento.php';
require_once '../../lib/ImageUploader.php';

$auth = new Auth($pdo);
$auth->protegerAdmin(); // Apenas administradores podem editar

$eventoObj = new Evento($pdo);
$mensagem = '';
$tipoMensagem = ''; // 'success' ou 'error'

// 1. Obter o ID do evento da URL
$eventoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventoId) {
    header("Location: gerenciar_eventos.php");
    exit();
}

// 2. Processar o formulário quando enviado

// No início do arquivo
require_once '../../lib/ImageUploader.php';

// ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Pega o caminho do banner atual (se houver)
        $currentBanner = $_POST['current_banner_url'] ?? null;
        $bannerUrl = $currentBanner; // Assume que manteremos o banner atual

        // Se um NOVO arquivo foi enviado, processa o upload
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            // A função de upload agora também recebe o caminho do banner antigo para poder apagá-lo
            $newBannerUrl = uploadBanner($_FILES['banner'], $currentBanner);
            if ($newBannerUrl) {
                $bannerUrl = $newBannerUrl;
            }
        }

        // Atualiza o evento com o caminho do banner novo (ou do antigo se nada mudou)
        $dadosEvento = [
            // ... outros campos ...
            'status' => $_POST['status']
            // Não adicione o 'banner_url' aqui. Faremos isso na query.
        ];

        // ...

        // ATUALIZE SEU MÉTODO Evento->atualizar() para aceitar o banner_url
        // Exemplo da chamada (você precisa ajustar o método na classe):
        $resultado = $eventoObj->atualizar($dadosEvento, $tiposIngresso, $eventoId, $bannerUrl);
        
        // ... resto da lógica de mensagem ...

    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipoMensagem = 'error';
    }
}

// 3. Buscar dados para preencher o formulário
$dadosEdicao = $eventoObj->buscarParaEdicao($eventoId);
if (!$dadosEdicao) {
    // Se o evento não existir, redireciona de volta
    header("Location: gerenciar_eventos.php?status=nao_encontrado");
    exit();
}

$evento = $dadosEdicao['evento'];
$ingressos = $dadosEdicao['ingressos'];
$modalidades = $eventoObj->listarModalidades();

// Incluir o cabeçalho
include '../../templates/header.php';
?>

<div class="container">
    <h2>Editar Evento: <?php echo htmlspecialchars($evento->nome); ?></h2>
    <a href="gerenciar_eventos.php">« Voltar para a lista de eventos</a><br><br>

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <form action="editar_evento.php?id=<?php echo $eventoId; ?>" method="post" enctype="multipart/form-data">

        <h4>Dados do Evento</h4>
        <label for="nome">Nome do Evento</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($evento->nome); ?>" required>

        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($evento->descricao); ?></textarea>

        <label for="data_evento">Data e Hora</label>
        <input type="datetime-local" id="data_evento" name="data_evento" value="<?php echo date('Y-m-d\TH:i', strtotime($evento->data_evento)); ?>" required>

        <label for="local">Local</label>
        <input type="text" id="local" name="local" value="<?php echo htmlspecialchars($evento->local); ?>" required>

        <label for="modalidade_id">Modalidade</label>
        <select id="modalidade_id" name="modalidade_id" required>
            <?php foreach ($modalidades as $modalidade): ?>
                <option value="<?php echo $modalidade->id; ?>" <?php echo ($modalidade->id == $evento->modalidade_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($modalidade->nome); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="agendado" <?php echo ($evento->status == 'agendado') ? 'selected' : ''; ?>>Agendado</option>
            <option value="realizado" <?php echo ($evento->status == 'realizado') ? 'selected' : ''; ?>>Realizado</option>
            <option value="cancelado" <?php echo ($evento->status == 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
        </select>

        <hr>
        <h4>Tipos de Ingresso</h4>

        <?php if (empty($ingressos)): ?>
            <p>Este evento não possui tipos de ingresso cadastrados.</p>
        <?php else: ?>
            <?php foreach ($ingressos as $ingresso): ?>
                <div class="ingresso-edit-group">
                    <h5>Editando Ingresso ID: <?php echo $ingresso->id; ?></h5>
                    <input type="hidden" name="ingressos[<?php echo $ingresso->id; ?>][id]" value="<?php echo $ingresso->id; ?>">

                    <label for="ingresso_nome_<?php echo $ingresso->id; ?>">Nome do Ingresso (Ex: Pista, VIP)</label>
                    <input type="text" id="ingresso_nome_<?php echo $ingresso->id; ?>" name="ingressos[<?php echo $ingresso->id; ?>][nome]" value="<?php echo htmlspecialchars($ingresso->nome); ?>" required>

                    <label for="ingresso_preco_<?php echo $ingresso->id; ?>">Preço (R$)</label>
                    <input type="number" step="0.01" id="ingresso_preco_<?php echo $ingresso->id; ?>" name="ingressos[<?php echo $ingresso->id; ?>][preco]" value="<?php echo htmlspecialchars($ingresso->preco); ?>" required>

                    <label for="ingresso_qtd_<?php echo $ingresso->id; ?>">Quantidade Total</label>
                    <input type="number" id="ingresso_qtd_<?php echo $ingresso->id; ?>" name="ingressos[<?php echo $ingresso->id; ?>][quantidade_total]" value="<?php echo htmlspecialchars($ingresso->quantidade_total); ?>" required>
                    <small>Já foram vendidos: <?php echo $ingresso->quantidade_vendida; ?>. A quantidade total não pode ser menor que este número.</small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <br>
        <!-- Dentro do formulário, antes do botão de salvar -->

        <h4>Banner Atual</h4>
        <?php if ($evento->banner_url): ?>
            <img src="<?php echo htmlspecialchars($evento->banner_url); ?>" alt="Banner atual" style="max-width: 400px; height: auto; border-radius: 8px; margin-bottom: 10px;">
            <p><small>Para manter o banner atual, simplesmente não envie um novo arquivo.</small></p>
            <input type="hidden" name="current_banner_url" value="<?php echo htmlspecialchars($evento->banner_url); ?>">
        <?php else: ?>
            <p>Nenhum banner cadastrado para este evento.</p>
        <?php endif; ?>

        <label for="banner">Enviar Novo Banner (Opcional)</label>
        <input type="file" id="banner" name="banner" accept="image/*">
        <button type="submit">Salvar Alterações</button>
    </form>
</div>

<?php
// Incluir o rodapé
include '../../templates/footer.php';
?>