<?php
// ATIVAR EXIBIÇÃO DE ERROS (opcional, bom para depuração)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. INCLUIR DEPENDÊNCIAS ESSENCIAIS
// Caminhos relativos a partir da pasta /public/
require_once '../config/database.php';
require_once '../lib/Auth.php';   // Necessário para o header funcionar
require_once '../lib/Evento.php'; // Necessário para listar os eventos

// 2. INSTANCIAR AS CLASSES
// Embora a lógica de $estaLogado esteja no header,
// é uma boa prática instanciar Auth para manter a consistência.
$auth = new Auth($pdo);
$eventoObj = new Evento($pdo);

// 3. BUSCAR OS DADOS PRINCIPAIS DA PÁGINA
// Pega a lista de eventos futuros para exibir
$eventos = $eventoObj->listarEventosFuturos();

// 4. VERIFICAR MENSAGENS DE STATUS (via GET)
$status = $_GET['status'] ?? '';

// 5. INCLUIR O CABEÇALHO DA PÁGINA
// O header.php agora cuida de iniciar a sessão e definir as variáveis de login
include '../templates/header.php';
?>

<!-- O HTML da página começa aqui -->
<div class="container">
    <h1>Próximos Eventos - Dr. Barra Racing</h1>

    <?php
    // Exibe uma mensagem de erro se um usuário não-admin tentou acessar a área de admin
    if ($status === 'nao_autorizado'):
    ?>
        <p class="error">Você não tem permissão para acessar essa página.</p>
    <?php endif; ?>

    <div class="lista-eventos">
        <?php if (empty($eventos)): ?>
            <p>Nenhum evento agendado no momento. Fique de olho para futuras novidades!</p>
        <?php else: ?>
            <?php foreach ($eventos as $evento): ?>
                <div class="card-evento">
                    <?php if (!empty($evento->banner_url) && file_exists($evento->banner_url)): // Verifica se o banner existe ?>
                        <img src="<?php echo htmlspecialchars($evento->banner_url); ?>" alt="Banner do Evento <?php echo htmlspecialchars($evento->nome); ?>" style="width:100%; height:auto; border-radius: 8px 8px 0 0;">
                    <?php endif; ?>
                    
                    <div class="card-evento-body">
                        <h3><?php echo htmlspecialchars($evento->nome); ?></h3>
                        <p><strong>Modalidade:</strong> <?php echo htmlspecialchars($evento->modalidade_nome); ?></p>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y \à\s H:i', strtotime($evento->data_evento)); ?></p>
                        <p><strong>Local:</strong> <?php echo htmlspecialchars($evento->local); ?></p>
                        
                        <!-- Caminho absoluto para o link, usando a estrutura do seu projeto -->
                        <a href="/evento.php?id=<?php echo $evento->id; ?>" class="btn-comprar">
                            Ver Detalhes e Comprar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// 6. INCLUIR O RODAPÉ DA PÁGINA
include '../templates/footer.php';
?>