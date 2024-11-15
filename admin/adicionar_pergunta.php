<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Verificar se o usuário está logado
verificarLogin();

// Verificar se é uma requisição AJAX
if (!is_ajax()) {
    header('HTTP/1.1 400 Bad Request');
    exit('Requisição inválida');
}

// Iniciar resposta
$resposta = [
    'sucesso' => false,
    'mensagem' => '',
    'id' => null
];

try {
    // Validar dados obrigatórios
    $campos_obrigatorios = ['pergunta', 'categoria', 'dificuldade', 'opcoes', 'resposta_correta'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("O campo {$campo} é obrigatório.");
        }
    }

    // Limpar e validar dados
    $pergunta = limparDados($_POST['pergunta']);
    $categoria = limparDados($_POST['categoria'] === 'nova' ? $_POST['nova_categoria'] : $_POST['categoria']);
    $dificuldade = limparDados($_POST['dificuldade']);
    $pontos = isset($_POST['pontos']) ? (int)$_POST['pontos'] : 10;
    $feedback = isset($_POST['feedback']) ? limparDados($_POST['feedback']) : null;
    
    // Validar tamanho da pergunta
    if (strlen($pergunta) < 10) {
        throw new Exception('A pergunta deve ter no mínimo 10 caracteres.');
    }

    // Validar dificuldade
    if (!array_key_exists($dificuldade, DIFICULDADES)) {
        throw new Exception('Dificuldade inválida.');
    }

    // Validar pontos
    if ($pontos < 1 || $pontos > 100) {
        throw new Exception('A pontuação deve estar entre 1 e 100.');
    }

    // Validar opções
    $opcoes = $_POST['opcoes'];
    $resposta_correta = (int)$_POST['resposta_correta'];

    if (count($opcoes) < 2) {
        throw new Exception('É necessário pelo menos 2 opções de resposta.');
    }

    if ($resposta_correta >= count($opcoes)) {
        throw new Exception('Resposta correta inválida.');
    }

    // Iniciar transação
    $conexao->beginTransaction();

    // Inserir pergunta
    $stmt = $conexao->prepare("
        INSERT INTO perguntas (pergunta, categoria, dificuldade, pontos, feedback)
        VALUES (:pergunta, :categoria, :dificuldade, :pontos, :feedback)
    ");

    $stmt->execute([
        ':pergunta' => $pergunta,
        ':categoria' => $categoria,
        ':dificuldade' => $dificuldade,
        ':pontos' => $pontos,
        ':feedback' => $feedback
    ]);

    $pergunta_id = $conexao->lastInsertId();

    // Inserir opções
    $stmt = $conexao->prepare("
        INSERT INTO respostas (pergunta_id, resposta, correta)
        VALUES (:pergunta_id, :resposta, :correta)
    ");

    foreach ($opcoes as $index => $opcao) {
        $opcao = limparDados($opcao);
        if (empty($opcao)) {
            throw new Exception('Todas as opções devem ser preenchidas.');
        }

        $stmt->execute([
            ':pergunta_id' => $pergunta_id,
            ':resposta' => $opcao,
            ':correta' => ($index == $resposta_correta) ? 1 : 0
        ]);
    }

    // Commit da transação
    $conexao->commit();

    // Registrar log
    registrar_log('sucesso', 'Pergunta adicionada com sucesso', [
        'id' => $pergunta_id,
        'categoria' => $categoria,
        'dificuldade' => $dificuldade
    ]);

    // Preparar resposta de sucesso
    $resposta = [
        'sucesso' => true,
        'mensagem' => 'Pergunta adicionada com sucesso!',
        'id' => $pergunta_id
    ];

} catch (PDOException $e) {
    $conexao->rollBack();
    registrar_log('erro', 'Erro no banco de dados ao adicionar pergunta: ' . $e->getMessage());
    $resposta['mensagem'] = 'Erro ao salvar a pergunta. Por favor, tente novamente.';
    
} catch (Exception $e) {
    if (isset($conexao) && $conexao->inTransaction()) {
        $conexao->rollBack();
    }
    $resposta['mensagem'] = $e->getMessage();
    
} finally {
    // Enviar resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($resposta);
}