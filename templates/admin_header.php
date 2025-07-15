<?php
// O início da sessão e a verificação de login já são feitos
// na página que inclui este header (ex: gerenciar_eventos.php),
// então não precisamos repetir aqui.

// No entanto, é bom ter as variáveis disponíveis para o menu.
$estaLogado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$nomeUsuario = htmlspecialchars($_SESSION['user_nome'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dr. Barra Racing</title>
    
    <!-- Link para o mesmo CSS, mas podemos adicionar estilos específicos depois -->
    <!-- Usamos ../ para voltar um nível da pasta /templates para a raiz do projeto -->
    <link rel="stylesheet" href="../public/css/style.css">
    
    <!-- Adicionando um bloco de estilo específico para o admin -->
    <style>
        body {
            background-color: #f8f9fa; /* Um fundo um pouco diferente para o admin */
        }
        .admin-header {
            background-color: #343a40; /* Cor escura para o header do admin */
            color: white;
            padding: 15px 0;
        }
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .admin-nav .logo {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffc107; /* Cor de destaque para o logo */
        }
        .admin-nav .logo span {
            font-weight: normal;
            color: #fff;
        }
        .admin-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 25px;
        }
        .admin-nav ul a {
            color: #f8f9fa;
            font-weight: 500;
            transition: color 0.2s;
        }
        .admin-nav ul a:hover, .admin-nav ul a.active {
            color: #ffc107; /* Cor de destaque no hover */
            text-decoration: none;
        }
        .user-info a {
            color: #adb5bd;
        }
    </style>
</head>
<body>

<header class="admin-header">
    <nav class="admin-nav">
        <div>
            <a href="../public/admin/gerenciar_eventos.php" class="logo"><span>Dr. Barra</span> Racing  প্রশাসন</a>
        </div>
        
        <ul>
            <li><a href="../public/admin/gerenciar_eventos.php">Gerenciar Eventos</a></li>
            <li><a href="../public/admin/criar_evento.php">Criar Evento</a></li>
            <!-- Adicione outros links de admin aqui no futuro -->
        </ul>

        <div class="user-info">
            <span>Olá, <?php echo $nomeUsuario; ?> | </span>
            <a href="../public/index.php" target="_blank">Ver Site</a> | 
            <a href="../public/logout.php">Sair</a>
        </div>
    </nav>
</header>

<main>
<!-- O conteúdo principal da página de admin virá aqui -->