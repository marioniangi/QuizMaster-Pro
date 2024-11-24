<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido'
    ]);
    exit;
}

// Iniciar resposta
$resposta = [
    'sucesso' => false,
    'mensagem' => '',
    'id' => null
];

try {
    // Validar dados obrigatórios
    if (empty($_POST['pergunta'])) {
        throw new Exception('A pergunta é obrigatória.');
    }
    if (empty($_POST['categoria'])) {
        throw new Exception('A categoria é obrigatória.');
    }
    if (empty($_POST['dificuldade'])) {
        throw new Exception('A dificuldade é obrigatória.');
    }
    if (!isset($_POST['opcoes']) || !is_array($_POST['opcoes']) || count($_POST['opcoes']) < 2) {
        throw new Exception('São necessárias pelo menos 2 opções de resposta.');
    }
    if (!isset($_POST['resposta_correta']) || !is_numeric($_POST['resposta_correta'])) {
        throw new Exception('É necessário indicar a resposta correta.');
    }

    // Limpar e validar dados
    $pergunta = trim($_POST['pergunta']);
    $categoria = trim($_POST['categoria'] === 'nova' ? $_POST['nova_categoria'] : $_POST['categoria']);
    $dificuldade = trim($_POST['dificuldade']);
    $pontos = isset($_POST['pontos']) ? (int)$_POST['pontos'] : 10;
    $resposta_correta = (int)$_POST['resposta_correta'];
    $opcoes = array_map('trim', $_POST['opcoes']);

    // Validações adicionais
    if (strlen($pergunta) < 10) {
        throw new Exception('A pergunta deve ter no mínimo 10 caracteres.');
    }

    if (!in_array($dificuldade, ['facil', 'medio', 'dificil'])) {
        throw new Exception('Dificuldade inválida.');
    }

    if ($pontos < 1 || $pontos > 100) {
        throw new Exception('A pontuação deve estar entre 1 e 100.');
    }

    if ($resposta_correta >= count($opcoes)) {
        throw new Exception('Resposta correta inválida.');
    }

    // Verificar se não há opções vazias
    foreach ($opcoes as $opcao) {
        if (empty($opcao)) {
            throw new Exception('Todas as opções devem ser preenchidas.');
        }
    }

    // Iniciar transação
    $conexao->beginTransaction();

    // Inserir pergunta
    $stmt = $conexao->prepare("
        INSERT INTO perguntas (pergunta, categoria, dificuldade, pontos, data_criacao)
        VALUES (:pergunta, :categoria, :dificuldade, :pontos, NOW())
    ");

    $stmt->execute([
        ':pergunta' => $pergunta,
        ':categoria' => $categoria,
        ':dificuldade' => $dificuldade,
        ':pontos' => $pontos
    ]);

    $pergunta_id = $conexao->lastInsertId();

    // Inserir opções
    $stmt = $conexao->prepare("
        INSERT INTO respostas (pergunta_id, resposta, correta)
        VALUES (:pergunta_id, :resposta, :correta)
    ");

    foreach ($opcoes as $index => $opcao) {
        $stmt->execute([
            ':pergunta_id' => $pergunta_id,
            ':resposta' => $opcao,
            ':correta' => ($index == $resposta_correta) ? 1 : 0
        ]);
    }

    // Commit da transação
    $conexao->commit();

    // Preparar resposta de sucesso
    $resposta = [
        'sucesso' => true,
        'mensagem' => 'Pergunta adicionada com sucesso!',
        'id' => $pergunta_id
    ];

} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    error_log('Erro PDO ao adicionar pergunta: ' . $e->getMessage());
    $resposta['mensagem'] = 'Erro ao salvar a pergunta. Por favor, tente novamente.';
    
} catch (Exception $e) {
    // Rollback em caso de erro
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    $resposta['mensagem'] = $e->getMessage();
}

// Enviar resposta
echo json_encode($resposta);
exit;