<?php
require_once '../config/database.php';
require_once '../lib/Auth.php';

$auth = new Auth($pdo);
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    if ($auth->registrar($nome, $email, $senha)) {
        header('Location: index.php?status=registrado');
        exit();
    } else {
        $erro = 'Este e-mail já está em uso. Tente outro.';
    }
}
?>

<?php include '../templates/header.php'; ?>

<div class="container">
    <h2>Registro de Novo Usuário</h2>
    <?php if ($erro): ?>
        <p class="error"><?php echo $erro; ?></p>
    <?php endif; ?>

    <form action="registro.php" method="post">
        <input type="text" name="nome" placeholder="Seu nome completo" required>
        <input type="email" name="email" placeholder="Seu e-mail" required>
        <input type="password" name="senha" placeholder="Crie uma senha" required>
        <button type="submit">Registrar</button>
    </form>
    <p>Já tem uma conta? <a href="index.php">Faça o login</a>.</p>
</div>

<?php include '../templates/footer.php'; ?>