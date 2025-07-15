<?php
// 1. ATIVAR EXIBIÇÃO DE ERROS (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. INCLUIR DEPENDÊNCIAS
// O arquivo login.php está dentro de /public/.
// As pastas 'config' e 'lib' estão um nível ACIMA, por isso usamos '../'
require_once '../config/database.php';
require_once '../lib/Auth.php';

// 3. INICIAR A LÓGICA
$auth = new Auth($pdo);
$erro = '';

// Se o usuário já estiver logado, redireciona para a página principal (a lista de eventos)
// e não para o dashboard, a menos que seja essa a sua intenção.
if ($auth->estaLogado()) {
    header('Location: index.php'); // Redireciona para a lista de eventos, não para o dashboard
    exit();
}

// 4. PROCESSAR O FORMULÁRIO DE LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($auth->login($email, $senha)) {
        // Após o login, o usuário é direcionado para a página principal.
        header('Location: index.php');
        exit();
    } else {
        $erro = 'E-mail ou senha inválidos!';
    }
}

// 5. MENSAGENS DE STATUS (vindo do registro, logout, etc.)
$status = $_GET['status'] ?? '';

// 6. INCLUIR O CABEÇALHO HTML
include '../templates/header.php';
?>

<div class="container">
    <h2>Login</h2>
    
    <?php if ($erro): ?>
        <p class="error"><?php echo $erro; ?></p>
    <?php endif; ?>

    <?php if ($status === 'registrado'): ?>
        <p class="success">Registro realizado com sucesso! Faça o login para continuar.</p>
    <?php endif; ?>

    <?php if ($status === 'negado'): ?>
        <p class="error">Acesso negado. Por favor, faça o login.</p>
    <?php endif; ?>
    
    <?php if ($status === 'logout'): ?>
        <p class="success">Você saiu do sistema com sucesso.</p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="email" name="email" placeholder="Seu e-mail" required>
        <input type="password" name="senha" placeholder="Sua senha" required>
        <button type="submit">Entrar</button>
    </form>
    <p>Não tem uma conta? <a href="registro.php">Registre-se aqui</a>.</p>
</div>

<?php 
// 7. INCLUIR O RODAPÉ HTML
include '../templates/footer.php'; 
?>