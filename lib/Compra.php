<?php
// lib/Compra.php

class Compra
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Finaliza uma compra, registrando no banco de dados e atualizando o estoque.
     * Utiliza uma transação para garantir a consistência dos dados.
     * 
     * @param int $usuarioId ID do usuário que está comprando.
     * @param array $itensCarrinho Array de itens, cada um contendo 'id', 'quantidade', 'preco'.
     * @param float $valorTotal Valor total da compra.
     * @return int|false Retorna o ID da compra em caso de sucesso, ou false em caso de falha.
     */
    public function finalizarCompra($usuarioId, $itensCarrinho, $valorTotal)
    {
        $this->pdo->beginTransaction();

        try {
            // Etapa 1: Verificar se ainda há ingressos disponíveis (prevenção de concorrência)
            foreach ($itensCarrinho as $item) {
                $sqlCheck = "SELECT (quantidade_total - quantidade_vendida) as disponivel 
                             FROM tipos_ingressos WHERE id = :id";
                $stmtCheck = $this->pdo->prepare($sqlCheck);
                $stmtCheck->execute([':id' => $item['id']]);
                $disponivel = $stmtCheck->fetchColumn();

                if ($disponivel < $item['quantidade']) {
                    // Se alguém comprou enquanto o usuário estava no carrinho, cancela a transação
                    $this->pdo->rollBack();
                    return false; // Retorna falha por falta de estoque
                }
            }

            // Etapa 2: Inserir o registro principal da compra
            $sqlCompra = "INSERT INTO compras (usuario_id, valor_total, status_pagamento) 
              VALUES (:usuario_id, :valor_total, 'pendente')"; // Simplicando, já definindo como 'pago'
            $stmtCompra = $this->pdo->prepare($sqlCompra);
            $stmtCompra->execute([
                ':usuario_id' => $usuarioId,
                ':valor_total' => $valorTotal
            ]);
            $compraId = $this->pdo->lastInsertId();

            // Etapa 3: Inserir os itens da compra e atualizar o estoque
            $sqlItem = "INSERT INTO compras_itens (compra_id, tipo_ingresso_id, quantidade, preco_unitario) 
                        VALUES (:compra_id, :tipo_ingresso_id, :quantidade, :preco_unitario)";
            $stmtItem = $this->pdo->prepare($sqlItem);

            $sqlUpdateEstoque = "UPDATE tipos_ingressos SET quantidade_vendida = quantidade_vendida + :quantidade 
                                 WHERE id = :tipo_ingresso_id";
            $stmtUpdate = $this->pdo->prepare($sqlUpdateEstoque);

            foreach ($itensCarrinho as $item) {
                // Inserir item
                $stmtItem->execute([
                    ':compra_id' => $compraId,
                    ':tipo_ingresso_id' => $item['id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco_unitario' => $item['preco']
                ]);
                // Atualizar estoque
                $stmtUpdate->execute([
                    ':quantidade' => $item['quantidade'],
                    ':tipo_ingresso_id' => $item['id']
                ]);
            }

            // Se tudo ocorreu bem, confirma a transação
            $this->pdo->commit();
            return $compraId;
        } catch (Exception $e) {
            // Se qualquer erro ocorrer, desfaz todas as operações
            $this->pdo->rollBack();
            // Opcional: logar o erro para depuração -> error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Lista todas as compras e seus itens para um usuário específico.
     */
    public function listarComprasPorUsuario($usuarioId)
    {
        $sql = "SELECT 
                    c.id as compra_id,
                    c.data_compra,
                    c.valor_total,
                    e.nome as evento_nome,
                    e.data_evento,
                    ti.nome as ingresso_nome,
                    ci.quantidade,
                    ci.preco_unitario
                FROM compras c
                JOIN compras_itens ci ON c.id = ci.compra_id
                JOIN tipos_ingressos ti ON ci.tipo_ingresso_id = ti.id
                JOIN eventos e ON ti.evento_id = e.id
                WHERE c.usuario_id = :usuario_id
                ORDER BY c.data_compra DESC, e.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        // Agrupa os resultados por compra_id para facilitar a exibição
        $compras = [];
        while ($row = $stmt->fetch()) {
            $compras[$row->compra_id]['info'] = [
                'data_compra' => $row->data_compra,
                'valor_total' => $row->valor_total
            ];
            $compras[$row->compra_id]['itens'][] = $row;
        }

        return $compras;
    }

    /**
     * Lista todos os participantes e detalhes de suas compras para um evento específico.
     * @param int $eventoId O ID do evento.
     * @return array Uma lista de compras com informações do usuário e dos ingressos.
     */
    public function listarParticipantesPorEvento(int $eventoId): array
    {
        $sql = "SELECT 
            c.id as compra_id,
            c.data_compra,
            c.valor_total,
            c.status_pagamento,
            c.comprovante_url, -- ADICIONE ESTA LINHA
            u.id as usuario_id,
            u.nome as usuario_nome,
            u.email as usuario_email,
            GROUP_CONCAT(CONCAT(ci.quantidade, 'x ', ti.nome) SEPARATOR ', ') as ingressos_comprados
            FROM compras c
            JOIN usuarios u ON c.usuario_id = u.id
            JOIN compras_itens ci ON c.id = ci.compra_id
            JOIN tipos_ingressos ti ON ci.tipo_ingresso_id = ti.id
            WHERE ti.evento_id = :evento_id
            GROUP BY c.id
            ORDER BY c.data_compra DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Atualiza o status de pagamento de uma compra específica.
     * @param int $compraId O ID da compra a ser atualizada.
     * @param string $novoStatus O novo status ('pago', 'cancelado').
     * @return bool True em caso de sucesso, false em caso de falha.
     */
    public function atualizarStatusPagamento(int $compraId, string $novoStatus): bool
    {
        // Validação simples para garantir que o status é um dos permitidos
        if (!in_array($novoStatus, ['pago', 'cancelado', 'pendente'])) {
            return false;
        }

        $sql = "UPDATE compras SET status_pagamento = :novo_status WHERE id = :compra_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':novo_status' => $novoStatus,
            ':compra_id' => $compraId
        ]);
    }

    /**
     * Anexa o caminho de um comprovante a uma compra existente.
     * @param int $compraId ID da compra.
     * @param int $usuarioId ID do usuário (para segurança).
     * @param string $comprovanteUrl O caminho do arquivo.
     * @return bool
     */
    public function anexarComprovante(int $compraId, int $usuarioId, string $comprovanteUrl): bool
    {
        $sql = "UPDATE compras 
                SET comprovante_url = :comprovante_url 
                WHERE id = :compra_id AND usuario_id = :usuario_id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':comprovante_url' => $comprovanteUrl,
            ':compra_id' => $compraId,
            ':usuario_id' => $usuarioId
        ]);
    }
}
