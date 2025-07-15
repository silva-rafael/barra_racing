<?php
// Força o início da sessão em todas as páginas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Defina as variáveis diretamente aqui, usando a superglobal $_SESSION
$estaLogado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. Barra Racing</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- CSS Principal -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header>
    <nav class="container">
        <!-- Use caminhos absolutos para os links também -->
        <a href="/index.php" class="logo">Dr. Barra Racing</a>
        <ul>
            <li><a href="/index.php">Eventos</a></li>
            <?php if ($estaLogado): ?>
                <li><a href="/meus_ingressos.php">Meus Ingressos</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="/admin/gerenciar_eventos.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="/logout.php">Sair</a></li>
            <?php else: ?>
                <li><a href="/login.php">Login</a></li>
                <li><a href="/registro.php">Registrar</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>