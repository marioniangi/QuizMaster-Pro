<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

header('Content-Type: application/json');

if (!isset($_SESSION['jogador_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
    exit;
}

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['partida_id']) || !isset($dados['pergunta_id']) || 
        !isset($dados['resposta_id']) || !isset($dados['tempo_resposta'])) {
        throw new Exception('Dados incompletos para registrar resposta');
    }

    $conexao->beginTransaction();

    // Verificar se a resposta está correta
    $stmt = $conexao->prepare("
        SELECT r.correta, p.pontos 
        FROM respostas r
        JOIN perguntas p ON r.pergunta_id = p.id
        WHERE r.id = ? AND r.pergunta_id = ?
    ");

    $stmt->execute([
        $dados['resposta_id'],
        $dados['pergunta_id']
    ]);

    $resposta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resposta) {
        throw new Exception('Resposta não encontrada');
    }

    // Converter para booleano
    $esta_correta = (bool)$resposta['correta'];
    $pontos = $esta_correta ? (int)$resposta['pontos'] : 0;

    // Registrar a resposta
    $stmt = $conexao->prepare("
        INSERT INTO respostas_jogador 
        (partida_id, jogador_id, pergunta_id, resposta_id, tempo_resposta, correta)
        VALUES 
        (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $dados['partida_id'],
        $_SESSION['jogador_id'],
        $dados['pergunta_id'],
        $dados['resposta_id'],
        $dados['tempo_resposta'],
        $esta_correta
    ]);

    $conexao->commit();

    // Preparar resposta
    $resposta_json = [
        'sucesso' => true,
        'correta' => $esta_correta,
        'pontos' => $pontos
    ];

    // Adicionar feedback apropriado
    if ($esta_correta) {
        $resposta_json['feedback'] = 'Resposta correta! Muito bem!';
    } else {
        $resposta_json['feedback'] = 'Resposta incorreta. Tente novamente!';
    }

    echo json_encode($resposta_json);

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