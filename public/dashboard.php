<?php
require_once '../config/database.php';
require_once '../lib/Auth.php';

$auth = new Auth($pdo);

// A linha mais importante: protege a página!
$auth->proteger();

$nomeUsuario = $auth->getNomeUsuario();
?>

<?php include '../templates/header.php'; ?>

<div class="container">
    <h2>Dashboard</h2>
    <p>Olá, <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>! Você está na área protegida.</p>
    <p>Somente usuários logados podem ver esta página.</p>
    <br>
    <a href="logout.php">Sair do Sistema</a>
</div>

<?php include '../templates/footer.php'; ?>