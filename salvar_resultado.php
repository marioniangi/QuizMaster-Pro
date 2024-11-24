<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

header('Content-Type: application/json');

try {
    // Log para debug
    $input = file_get_contents('php://input');
    error_log("Dados brutos recebidos: " . $input);
    
    $dados = json_decode($input, true);
    error_log("Dados decodificados: " . print_r($dados, true));
    
    if (!isset($dados['pontuacao']) || !isset($dados['acertos']) || !isset($dados['total_perguntas']) || !isset($dados['modo'])) {
        throw new Exception("Dados incompletos");
    }

    // Validar valores
    $pontuacao = intval($dados['pontuacao']);
    $acertos = intval($dados['acertos']);
    $total_perguntas = intval($dados['total_perguntas']);
    $modo = $dados['modo'];
    $jogador_id = isset($_SESSION['jogador_id']) ? intval($_SESSION['jogador_id']) : 0;

    if ($jogador_id <= 0) {
        throw new Exception("Jogador não identificado");
    }

    $conexao->beginTransaction();

    // 1. Inserir partida
    $sql_partida = "INSERT INTO partidas (jogador_id, modo, pontuacao, acertos, total_perguntas, data_fim, status) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'finalizada')";
    
    $stmt = $conexao->prepare($sql_partida);
    $stmt->execute([
        $jogador_id,
        $modo,
        $pontuacao,
        $acertos,
        $total_perguntas
    ]);

    // 2. Atualizar estatísticas do jogador
    $sql_jogador = "UPDATE jogadores 
                    SET pontuacao_total = pontuacao_total + ?,
                        melhor_pontuacao = GREATEST(melhor_pontuacao, ?),
                        jogos_completados = jogos_completados + 1
                    WHERE id = ?";
    
    $stmt = $conexao->prepare($sql_jogador);
    $stmt->execute([
        $pontuacao,
        $pontuacao,
        $jogador_id
    ]);

    // 3. Pegar ranking
    $sql_ranking = "SELECT COUNT(*) + 1 as posicao
                    FROM jogadores
                    WHERE pontuacao_total > (
                        SELECT pontuacao_total 
                        FROM jogadores 
                        WHERE id = ?
                    )";
    
    $stmt = $conexao->prepare($sql_ranking);
    $stmt->execute([$jogador_id]);
    $ranking = $stmt->fetch(PDO::FETCH_ASSOC)['posicao'];

    $conexao->commit();

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