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
    if (!isset($dados['partida_id']) || !isset($dados['pergunta_id']) || 
        !isset($dados['resposta_id']) || !isset($dados['tempo_resposta'])) {
        throw new Exception('Dados incompletos');
    }

    $conexao->beginTransaction();

    // Registrar resposta
    $stmt = $conexao->prepare("
        INSERT INTO respostas_jogador 
        (partida_id, jogador_id, pergunta_id, resposta_id, tempo_resposta, data_resposta)
        VALUES 
        (:partida_id, :jogador_id, :pergunta_id, :resposta_id, :tempo_resposta, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        ':partida_id' => $dados['partida_id'],
        ':jogador_id' => $_SESSION['jogador_id'],
        ':pergunta_id' => $dados['pergunta_id'],
        ':resposta_id' => $dados['resposta_id'],
        ':tempo_resposta' => $dados['tempo_resposta']
    ]);

    // Verificar se a resposta está correta
    $stmt = $conexao->prepare("
        SELECT correta, pontos 
        FROM respostas 
        WHERE id = :resposta_id AND pergunta_id = :pergunta_id
    ");

    $stmt->execute([
        ':resposta_id' => $dados['resposta_id'],
        ':pergunta_id' => $dados['pergunta_id']
    ]);

    $resposta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $conexao->commit();

    echo json_encode([
        'sucesso' => true,
        'correta' => $resposta['correta'] == 1,
        'pontos' => (int)$resposta['pontos']
    ]);

} catch (Exception $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    error_log("Erro ao registrar resposta: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}
?>