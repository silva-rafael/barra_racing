<?php
require_once '../../config/database.php';
require_once '../../lib/Auth.php';
require_once '../../lib/Evento.php';

$auth = new Auth($pdo);
$auth->protegerAdmin();

$eventoObj = new Evento($pdo);
$eventos = $eventoObj->listarTodosEventos();
?>

<?php // include '../../templates/admin_header.php'; // Crie este header se precisar de um menu de admin ?>
<?php include '../../templates/header.php'; ?>

<div class="container-admin">
    <h2>Gerenciar Eventos</h2>
    <a href="criar_evento.php" class="btn-novo">Criar Novo Evento</a>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Data</th>
                <th>Local</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventos as $evento): ?>
            <tr>
                <td><?php echo htmlspecialchars($evento->nome); ?></td>
                <td><?php echo date('d/m/Y', strtotime($evento->data_evento)); ?></td>
                <td><?php echo htmlspecialchars($evento->local); ?></td>
                <td><?php echo ucfirst($evento->status); ?></td>
                <td>
                    <a href="editar_evento.php?id=<?php echo $evento->id; ?>">Editar</a> |
                    <a href="ver_ingressos.php?id=<?php echo $evento->id; ?>">Ingressos</a>
                    <a href="participantes_evento.php?id=<?php echo $evento->id; ?>" class="btn-acao btn-participantes">Participantes</a>
                    <!-- A ação de cancelar exigiria um form com POST para segurança -->
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../templates/footer.php'; ?>