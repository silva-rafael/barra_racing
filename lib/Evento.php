<?php
// lib/Evento.php

class Evento
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas as modalidades para usar em formulários.
     */
    public function listarModalidades()
    {
        $stmt = $this->pdo->query("SELECT * FROM modalidades ORDER BY nome");
        return $stmt->fetchAll();
    }

    /**
     * Cria um novo evento e seus tipos de ingresso.
     */
    public function criar(array $dadosEvento, array $tiposIngresso)
    {
        // Usar transação para garantir que tudo seja inserido ou nada
        $this->pdo->beginTransaction();

        try {
            // 1. Inserir o evento
            $sqlEvento = "INSERT INTO eventos (nome, descricao, data_evento, local, modalidade_id, banner_url) 
                          VALUES (:nome, :descricao, :data_evento, :local, :modalidade_id, :banner_url)";
            $stmtEvento = $this->pdo->prepare($sqlEvento);
            $stmtEvento->execute([
                ':nome' => $dadosEvento['nome'],
                ':descricao' => $dadosEvento['descricao'],
                ':data_evento' => $dadosEvento['data_evento'],
                ':local' => $dadosEvento['local'],
                ':modalidade_id' => $dadosEvento['modalidade_id'],
                ':banner_url' => $dadosEvento['banner_url'] ?? null
            ]);
            $eventoId = $this->pdo->lastInsertId();

            // 2. Inserir os tipos de ingresso
            $sqlIngresso = "INSERT INTO tipos_ingressos (evento_id, nome, preco, quantidade_total) 
                            VALUES (:evento_id, :nome, :preco, :quantidade_total)";
            $stmtIngresso = $this->pdo->prepare($sqlIngresso);

            foreach ($tiposIngresso as $ingresso) {
                $stmtIngresso->execute([
                    ':evento_id' => $eventoId,
                    ':nome' => $ingresso['nome'],
                    ':preco' => $ingresso['preco'],
                    ':quantidade_total' => $ingresso['quantidade_total']
                ]);
            }

            // Se tudo deu certo, confirma a transação
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            // Se algo deu errado, desfaz tudo
            $this->pdo->rollBack();
            // Opcional: logar o erro $e->getMessage()
            return false;
        }
    }

    /**
     * Lista todos os eventos futuros.
     */
    public function listarEventosFuturos()
    {
        // O JOIN busca o nome da modalidade em vez de apenas o ID
        $sql = "SELECT e.*, m.nome AS modalidade_nome 
                FROM eventos e
                JOIN modalidades m ON e.modalidade_id = m.id
                WHERE e.data_evento >= CURDATE() AND e.status = 'agendado'
                ORDER BY e.data_evento ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca um evento específico pelo ID.
     */
    public function buscarPorId($id)
    {
        $sql = "SELECT e.*, m.nome AS modalidade_nome 
                FROM eventos e
                JOIN modalidades m ON e.modalidade_id = m.id
                WHERE e.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Busca os tipos de ingresso de um evento.
     */
    public function buscarTiposIngresso($evento_id)
    {
        $sql = "SELECT *, (quantidade_total - quantidade_vendida) as quantidade_disponivel 
                FROM tipos_ingressos 
                WHERE evento_id = :evento_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':evento_id' => $evento_id]);
        return $stmt->fetchAll();
    }

    /**
     * Lista todos os eventos para a área de administração.
     */
    public function listarTodosEventos()
    {
        $sql = "SELECT e.*, m.nome AS modalidade_nome 
                FROM eventos e
                JOIN modalidades m ON e.modalidade_id = m.id
                ORDER BY e.data_evento DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function buscarParaEdicao($id)
    {
        $evento = $this->buscarPorId($id);
        if (!$evento) {
            return null; // Retorna nulo se o evento não for encontrado
        }

        $ingressos = $this->buscarTiposIngresso($id);

        return [
            'evento' => $evento,
            'ingressos' => $ingressos
        ];
    }

    /**
     * Atualiza um evento e seus tipos de ingresso.
     * Nota: Esta versão simplificada atualiza ingressos existentes.
     * Uma versão mais complexa poderia adicionar/remover tipos de ingresso.
     */
    public function atualizar(array $dadosEvento, array $tiposIngresso, $eventoId, ?string $bannerUrl)
    {
        // ...
        try {
            // 1. Atualizar a tabela de eventos
            $sqlEvento = "UPDATE eventos SET 
                        nome = :nome, 
                        descricao = :descricao, 
                        data_evento = :data_evento, 
                        local = :local, 
                        modalidade_id = :modalidade_id,
                        status = :status,
                        banner_url = :banner_url  -- Adicionado aqui
                    WHERE id = :id";

            $stmtEvento = $this->pdo->prepare($sqlEvento);
            $stmtEvento->execute([
                ':nome' => $dadosEvento['nome'],
                ':descricao' => $dadosEvento['descricao'],
                ':data_evento' => $dadosEvento['data_evento'],
                ':local' => $dadosEvento['local'],
                ':modalidade_id' => $dadosEvento['modalidade_id'],
                ':status' => $dadosEvento['status'],
                ':banner_url' => $bannerUrl, // Adicionado aqui
                ':id' => $eventoId
            ]);

            // 2. Atualizar os tipos de ingresso
            $sqlIngresso = "UPDATE tipos_ingressos SET 
                                nome = :nome, 
                                preco = :preco, 
                                quantidade_total = :quantidade_total
                            WHERE id = :id AND evento_id = :evento_id";

            $stmtIngresso = $this->pdo->prepare($sqlIngresso);

            foreach ($tiposIngresso as $ingresso) {
                // Validação para garantir que a quantidade total não seja menor que a já vendida
                $stmtCheck = $this->pdo->prepare("SELECT quantidade_vendida FROM tipos_ingressos WHERE id = ?");
                $stmtCheck->execute([$ingresso['id']]);
                $vendidos = $stmtCheck->fetchColumn();

                if ($ingresso['quantidade_total'] < $vendidos) {
                    throw new Exception("A quantidade total para o ingresso '{$ingresso['nome']}' não pode ser menor que a quantidade já vendida ({$vendidos}).");
                }

                $stmtIngresso->execute([
                    ':nome' => $ingresso['nome'],
                    ':preco' => $ingresso['preco'],
                    ':quantidade_total' => $ingresso['quantidade_total'],
                    ':id' => $ingresso['id'],
                    ':evento_id' => $eventoId
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            // Retorna a mensagem de erro para ser exibida ao usuário
            return $e->getMessage();
        }
    }
}
