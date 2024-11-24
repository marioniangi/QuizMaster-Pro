<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

header('Content-Type: application/json');

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['modo'])) {
        throw new Exception('Modo de jogo não especificado');
    }

    $conexao->beginTransaction();

    // Criar nova partida
    $stmt = $conexao->prepare("
        INSERT INTO partidas 
        (jogador_id, modo, data_inicio, status) 
        VALUES 
        (?, ?, NOW(), 'em_andamento')
    ");

    $stmt->execute([
        $_SESSION['jogador_id'],
        $dados['modo']
    ]);

    $partida_id = $conexao->lastInsertId();

    // Buscar perguntas
    $dificuldade = $dados['modo'] === 'desafio' ? 'dificil' : 'medio';

    // Primeiro, obter 5 IDs de perguntas aleatórias
    $stmt = $conexao->prepare("
        SELECT id
        FROM perguntas
        WHERE dificuldade = ?
        ORDER BY RAND()
        LIMIT 5
    ");
    $stmt->execute([$dificuldade]);
    $perguntaIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $perguntas = [];
    foreach ($perguntaIds as $pid) {
        // Buscar detalhes da pergunta e respostas
        $stmt = $conexao->prepare("
            SELECT 
                p.id,
                p.pergunta,
                p.categoria,
                p.pontos,
                r.id as resposta_id,
                r.resposta,
                r.correta
            FROM perguntas p
            JOIN respostas r ON p.id = r.pergunta_id
            WHERE p.id = ?
        ");
        
        $stmt->execute([$pid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $respostas = array_map(function($row) {
                return [
                    'id' => (int)$row['resposta_id'],
                    'texto' => $row['resposta'],
                    'correta' => $row['correta'] == '1',
                    'pontos' => (int)$row['pontos']
                ];
            }, $rows);
            
            shuffle($respostas); // Embaralhar respostas
            
            $perguntas[] = [
                'id' => (int)$rows[0]['id'],
                'texto' => $rows[0]['pergunta'],
                'categoria' => $rows[0]['categoria'],
                'respostas' => $respostas
            ];
        }
    }

    if (empty($perguntas)) {
        throw new Exception('Não há perguntas disponíveis para este modo');
    }

    // Embaralhar a ordem das perguntas
    shuffle($perguntas);

    $conexao->commit();

    echo json_encode([
        'sucesso' => true,
        'partida_id' => $partida_id,
        'perguntas' => $perguntas
    ]);

} catch (Exception $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    error_log("Erro ao criar partida: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}
?>