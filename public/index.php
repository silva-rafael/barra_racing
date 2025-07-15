<?php
// ... (toda a sua lógica PHP no início permanece a mesma)
require_once '../config/database.php';
require_once '../lib/Auth.php';
require_once '../lib/Evento.php';

$auth = new Auth($pdo);
$eventoObj = new Evento($pdo);
$eventos = $eventoObj->listarEventosFuturos();
$status = $_GET['status'] ?? '';

// O header agora é incluído DEPOIS da seção hero
// include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<?php include '../templates/header.php'; // Inclui o <head> e abre o <body> ?>

<!-- Seção Hero para impacto visual -->
<div class="hero-section">
    <h1>ADRENALINA E VELOCIDADE</h1>
    <p>Os eventos mais radicais de Freestyle, Drift e Motocross. Garanta seu ingresso!</p>
</div>

<main>
    <div class="container">
        <h2 style="font-size: 2.5em; text-align: center;">Próximos Eventos</h2>

        <?php if ($status === 'nao_autorizado'): ?>
            <p class="error">Você não tem permissão para acessar essa página.</p>
        <?php endif; ?>

        <div class="lista-eventos">
            <?php if (empty($eventos)): ?>
                <p>Nenhum evento agendado no momento. Fique de olho para futuras novidades!</p>
            <?php else: ?>
                <?php foreach ($eventos as $evento): ?>
                    <div class="card-evento">
                        <div class="card-evento-img" style="background-image: url('<?php echo htmlspecialchars($evento->banner_url ?: '/img/hero.jpg'); ?>');">
                            <h3><?php echo htmlspecialchars($evento->nome); ?></h3>
                        </div>
                        <div class="card-evento-body">
                            <p>
                                <strong>Modalidade:</strong> <?php echo htmlspecialchars($evento->modalidade_nome); ?><br>
                                <strong>Data:</strong> <?php echo date('d/m/Y \à\s H:i', strtotime($evento->data_evento)); ?><br>
                                <strong>Local:</strong> <?php echo htmlspecialchars($evento->local); ?>
                            </p>
                            <a href="/evento.php?id=<?php echo $evento->id; ?>" class="btn-comprar">
                                Ver Detalhes
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Incluir o rodapé da página
include '../templates/footer.php';
?>
</html>