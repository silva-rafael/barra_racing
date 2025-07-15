<?php
// lib/Auth.php

class Auth
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Registra um novo usuário no banco de dados.
     */
    public function registrar($nome, $email, $senha)
    {
        // Criptografa a senha com o algoritmo mais seguro disponível no PHP
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senhaHash
            ]);
            return true;
        } catch (PDOException $e) {
            // Se o e-mail já existir, o SGBD retornará um erro de violação de chave única
            if ($e->getCode() == 23000) {
                return false; // Email já existe
            }
            throw $e; // Lança a exceção para outros erros
        }
    }

    /**
     * Tenta fazer o login do usuário.
     */
    public function login($email, $senha)
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        $usuario = $stmt->fetch();

        // Verifica se o usuário existe e se a senha está correta
        if ($usuario && password_verify($senha, $usuario->senha)) {
            // Regenera o ID da sessão para prevenir ataques de Session Fixation
            session_regenerate_id(true);

            // Armazena informações na sessão
            $_SESSION['user_id'] = $usuario->id;
            $_SESSION['user_nome'] = $usuario->nome;
            $_SESSION['is_admin'] = (bool)$usuario->is_admin;
            $_SESSION['logged_in'] = true;

            return true;
        }

        return false;
    }

    // Adicione este novo método à classe Auth
    public function protegerAdmin()
    {
        // Primeiro, verifica se está logado
        if (!$this->estaLogado()) {
            header("Location: ../login.php?status=negado");
            exit();
        }
        // Depois, verifica se é admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            // Se não for admin, pode redirecionar para uma página de "acesso negado" ou para a home
            header("Location: ../index.php?status=nao_autorizado");
            exit();
        }
    }

    /**
     * Verifica se o usuário está logado.
     */
    public function estaLogado()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Faz o logout do usuário.
     */
    public function logout()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Protege uma página, redirecionando para a página de login se o usuário não estiver logado.
     */
    public function proteger()
    {
        if (!$this->estaLogado()) {
            header("Location: login.php?status=negado");
            exit();
        }
    }

    /**
     * Retorna o nome do usuário logado.
     */
    public function getNomeUsuario()
    {
        return $_SESSION['user_nome'] ?? 'Visitante';
    }
}
