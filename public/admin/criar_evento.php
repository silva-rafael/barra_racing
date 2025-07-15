<?php
// Ativar exibição de erros para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. INCLUIR TODAS AS DEPENDÊNCIAS
require_once '../../config/database.php';
require_once '../../lib/Auth.php';
require_once '../../lib/Evento.php';
require_once '../../lib/ImageUploader.php'; // Incluímos nosso novo uploader

// 2. PROTEGER A PÁGINA E PREPARAR OBJETOS
$auth = new Auth($pdo);
$auth->protegerAdmin(); // Essencial: Apenas administradores podem criar eventos

$eventoObj = new Evento($pdo);
$mensagem = '';
$tipoMensagem = ''; // 'success' ou 'error'

// 3. PROCESSAR O FORMULÁRIO QUANDO ENVIADO (MÉTODO POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Usamos um bloco try...catch para capturar qualquer erro que a função de upload possa lançar
    try {
        $bannerUrl = null;
        // Verifica se um arquivo de banner foi enviado e se não houve erro
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            // Chama nossa função de upload segura
            $bannerUrl = uploadBanner($_FILES['banner']);
        }

        // Coleta os dados do evento do formulário
        $dadosEvento = [
            'nome' => $_POST['nome'],
            'descricao' => $_POST['descricao'],
            'data_evento' => $_POST['data_evento'],
            'local' => $_POST['local'],
            'modalidade_id' => $_POST['modalidade_id'],
            'banner_url' => $bannerUrl // Salva o caminho retornado pela função
        ];

        // Coleta os dados dos tipos de ingresso
        // Nota: Uma aplicação mais complexa usaria JS para adicionar/remover campos dinamicamente
        $tiposIngresso = [
            [
                'nome' => $_POST['ingresso_nome_1'], 
                'preco' => $_POST['ingresso_preco_1'], 
                'quantidade_total' => $_POST['ingresso_qtd_1']
            ],
            [
                'nome' => $_POST['ingresso_nome_2'], 
                'preco' => $_POST['ingresso_preco_2'], 
                'quantidade_total' => $_POST['ingresso_qtd_2']
            ]
        ];
        
        // Remove tipos de ingresso vazios antes de enviar para a classe
        $tiposIngresso = array_filter($tiposIngresso, fn($ing) => !empty($ing['nome']) && !empty($ing['preco']));

        // Chama o método da classe Evento para criar o registro no banco
        if ($eventoObj->criar($dadosEvento, $tiposIngresso)) {
            $mensagem = "Evento '" . htmlspecialchars($dadosEvento['nome']) . "' criado com sucesso!";
            $tipoMensagem = 'success';
        } else {
            $mensagem = "Ocorreu um erro desconhecido ao criar o evento. Tente novamente.";
            $tipoMensagem = 'error';
        }

    } catch (Exception $e) {
        // Se a função uploadBanner() ou outra parte lançar um erro, ele será capturado aqui
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = 'error';
    }
}

// 4. BUSCAR DADOS NECESSÁRIOS PARA EXIBIR O FORMULÁRIO (MÉTODO GET)
$modalidades = $eventoObj->listarModalidades();

// 5. INCLUIR O CABEÇALHO DA ÁREA DE ADMIN
include '../../templates/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Criar Novo Evento</h2>
        <a href="gerenciar_eventos.php" style="text-decoration: none;">« Voltar para a lista</a>
    </div>
    <hr style="border-color: var(--border-color); margin-bottom: 2rem;">

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <!-- O atributo enctype é OBRIGATÓRIO para formulários com upload de arquivos -->
    <form class="form-container" action="criar_evento.php" method="post" enctype="multipart/form-data">
        
        <h4>Dados Principais</h4>
        <label for="nome">Nome do Evento</label>
        <input type="text" id="nome" name="nome" placeholder="Ex: Etapa Final de Motocross" required>

        <label for="descricao">Descrição Completa do Evento</label>
        <textarea id="descricao" name="descricao" rows="5" placeholder="Detalhes sobre as atrações, horários, regras..." required></textarea>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label for="data_evento">Data e Hora</label>
                <input type="datetime-local" id="data_evento" name="data_evento" required>
            </div>
            <div>
                <label for="local">Local do Evento</label>
                <input type="text" id="local" name="local" placeholder="Ex: Arena Dr. Barra Racing" required>
            </div>
        </div>

        <label for="modalidade_id">Modalidade Principal</label>
        <select id="modalidade_id" name="modalidade_id" required>
            <option value="" disabled selected>-- Selecione uma modalidade --</option>
            <?php foreach ($modalidades as $modalidade): ?>
                <option value="<?php echo $modalidade->id; ?>">
                    <?php echo htmlspecialchars($modalidade->nome); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="banner">Banner do Evento (Recomendado: 1200x600px)</label>
        <input type="file" id="banner" name="banner" accept="image/jpeg, image/png, image/webp, image/gif">
        
        <hr style="border-color: var(--border-color); margin: 2rem 0;">
        <h4>Tipos de Ingresso</h4>

        <!-- Ingresso Tipo 1 -->
        <div style="background-color: #2a2a2a; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
            <h5>Ingresso 1</h5>
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px;">
                <input type="text" name="ingresso_nome_1" placeholder="Nome do Ingresso (Ex: Pista)" required>
                <input type="number" step="0.01" name="ingresso_preco_1" placeholder="Preço (R$)" required>
                <input type="number" name="ingresso_qtd_1" placeholder="Quantidade Total" required>
            </div>
        </div>
        
        <!-- Ingresso Tipo 2 -->
        <div style="background-color: #2a2a2a; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
            <h5>Ingresso 2 (Opcional)</h5>
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px;">
                <input type="text" name="ingresso_nome_2" placeholder="Nome do Ingresso (Ex: Camarote VIP)">
                <input type="number" step="0.01" name="ingresso_preco_2" placeholder="Preço (R$)">
                <input type="number" name="ingresso_qtd_2" placeholder="Quantidade Total">
            </div>
        </div>

        <br>
        <button type="submit">Criar Evento</button>
    </form>
</div>

<?php
// 6. INCLUIR O RODAPÉ
include '../../templates/footer.php';
?>