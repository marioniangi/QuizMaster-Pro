<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['jogador_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
    exit;
}

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados recebidos
    if (!isset($dados['pontuacao']) || !isset($dados['acertos']) || 
        !isset($dados['total_perguntas']) || !isset($dados['partida_id'])) {
        throw new Exception('Dados incompletos para salvar o resultado');
    }

    // Validar tipos de dados
    $partida_id = (int)$dados['partida_id'];
    $pontuacao = (int)$dados['pontuacao'];
    $acertos = (int)$dados['acertos'];
    $total_perguntas = (int)$dados['total_perguntas'];
    
    // Validar valores
    if ($pontuacao < 0 || $acertos < 0 || $total_perguntas < 0 || 
        $acertos > $total_perguntas) {
        throw new Exception('Valores inválidos nos dados do resultado');
    }

    $conexao->beginTransaction();

    // Primeiro, vamos verificar se a partida existe
    $stmt = $conexao->prepare("
        SELECT id 
        FROM partidas 
        WHERE id = ? AND jogador_id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$partida_id, $_SESSION['jogador_id']]);
    if (!$stmt->fetch()) {
        // Se a partida não existir, vamos criar
        $stmt = $conexao->prepare("
            INSERT INTO partidas 
            (id, jogador_id, pontuacao, acertos, total_perguntas, data_inicio) 
            VALUES 
            (:partida_id, :jogador_id, 0, 0, :total_perguntas, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            ':partida_id' => $partida_id,
            ':jogador_id' => $_SESSION['jogador_id'],
            ':total_perguntas' => $total_perguntas
        ]);
    }

    // Atualizar partida
    $stmt = $conexao->prepare("
        UPDATE partidas 
        SET pontuacao = :pontuacao,
            acertos = :acertos,
            total_perguntas = :total_perguntas,
            data_fim = CURRENT_TIMESTAMP
        WHERE id = :partida_id 
        AND jogador_id = :jogador_id
    ");

    $result = $stmt->execute([
        ':partida_id' => $partida_id,
        ':jogador_id' => $_SESSION['jogador_id'],
        ':pontuacao' => $pontuacao,
        ':acertos' => $acertos,
        ':total_perguntas' => $total_perguntas
    ]);

    if (!$result) {
        throw new Exception('Erro ao atualizar a partida');
    }

    // Atualizar estatísticas do jogador
    $stmt = $conexao->prepare("
        UPDATE jogadores 
        SET pontuacao_total = COALESCE(pontuacao_total, 0) + :pontuacao,
            melhor_pontuacao = GREATEST(COALESCE(melhor_pontuacao, 0), :pontuacao),
            jogos_completados = COALESCE(jogos_completados, 0) + 1,
            ultima_partida = CURRENT_TIMESTAMP
        WHERE id = :jogador_id
    ");

    $result = $stmt->execute([
        ':pontuacao' => $pontuacao,
        ':jogador_id' => $_SESSION['jogador_id']
    ]);

    if (!$result) {
        throw new Exception('Erro ao atualizar estatísticas do jogador');
    }

    // Buscar posição no ranking
    $stmt = $conexao->prepare("
        SELECT COUNT(*) + 1 as posicao
        FROM jogadores
        WHERE pontuacao_total > (
            SELECT COALESCE(pontuacao_total, 0)
            FROM jogadores 
            WHERE id = :jogador_id
        )
    ");
    
    $stmt->execute([':jogador_id' => $_SESSION['jogador_id']]);
    $ranking = $stmt->fetch(PDO::FETCH_ASSOC)['posicao'];

    $conexao->commit();

    // Retornar sucesso
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Resultado salvo com sucesso!',
        'ranking' => $ranking,
        'dados' => [
            'pontuacao' => $pontuacao,
            'acertos' => $acertos,
            'total_perguntas' => $total_perguntas
        ]
    ]);

} catch (PDOException $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    error_log("Erro de banco de dados ao salvar resultado: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao salvar resultado no banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    error_log("Erro ao salvar resultado: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}
?>