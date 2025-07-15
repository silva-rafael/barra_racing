<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'projeto_login'); // Nome do seu banco de dados
define('DB_USER', 'root'); // Seu usuário do MySQL
define('DB_PASS', ''); // Sua senha do MySQL

try {
    // Cria uma instância PDO
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

    // Define o modo de erro do PDO para exceção
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Define o modo de busca padrão para objetos, para facilitar o acesso
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (PDOException $e) {
    // Em caso de erro na conexão, mata o script e exibe a mensagem
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// O objeto $pdo estará disponível para qualquer arquivo que incluir este.
?>