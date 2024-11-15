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
    // Validar ID da pergunta
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('ID da pergunta inválido.');
    }

    $id = (int)$_POST['id'];

    // Verificar se a pergunta existe
    $stmt = $conexao->prepare("SELECT id FROM perguntas WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        throw new Exception('Pergunta não encontrada.');
    }

    // Validar dados obrigatórios
    $campos_obrigatorios = ['pergunta', 'categoria', 'dificuldade', 'opcoes', 'resposta_correta'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("O campo {$campo} é obrigatório.");
        }
    }

    // Limpar e validar dados
    $pergunta = limparDados($_POST['pergunta']);
    $categoria = limparDados($_POST['categoria']);
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

    // Registrar estado anterior para log
    $stmt = $conexao->prepare("
        SELECT p.*, GROUP_CONCAT(r.resposta) as respostas
        FROM perguntas p
        LEFT JOIN respostas r ON p.id = r.pergunta_id
        WHERE p.id = :id
        GROUP BY p.id
    ");
    $stmt->execute([':id' => $id]);
    $estado_anterior = $stmt->fetch(PDO::FETCH_ASSOC);

    // Iniciar transação
    $conexao->beginTransaction();

    // Atualizar pergunta
    $stmt = $conexao->prepare("
        UPDATE perguntas 
        SET pergunta = :pergunta,
            categoria = :categoria,
            dificuldade = :dificuldade,
            pontos = :pontos,
            feedback = :feedback
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id,
        ':pergunta' => $pergunta,
        ':categoria' => $categoria,
        ':dificuldade' => $dificuldade,
        ':pontos' => $pontos,
        ':feedback' => $feedback
    ]);

    // Excluir respostas antigas
    $stmt = $conexao->prepare("DELETE FROM respostas WHERE pergunta_id = :pergunta_id");
    $stmt->execute([':pergunta_id' => $id]);

    // Inserir novas respostas
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
            ':pergunta_id' => $id,
            ':resposta' => $opcao,
            ':correta' => ($index == $resposta_correta) ? 1 : 0
        ]);
    }

    // Commit da transação
    $conexao->commit();

    // Registrar log com as alterações
    $alteracoes = [
        'id' => $id,
        'alteracoes' => array_diff_assoc([
            'pergunta' => $pergunta,
            'categoria' => $categoria,
            'dificuldade' => $dificuldade,
            'pontos' => $pontos
        ], [
            'pergunta' => $estado_anterior['pergunta'],
            'categoria' => $estado_anterior['categoria'],
            'dificuldade' => $estado_anterior['dificuldade'],
            'pontos' => $estado_anterior['pontos']
        ])
    ];

    registrar_log('sucesso', 'Pergunta atualizada com sucesso', $alteracoes);

    // Preparar resposta de sucesso
    $resposta = [
        'sucesso' => true,
        'mensagem' => 'Pergunta atualizada com sucesso!',
        'id' => $id
    ];

} catch (PDOException $e) {
    $conexao->rollBack();
    registrar_log('erro', 'Erro no banco de dados ao atualizar pergunta: ' . $e->getMessage());
    $resposta['mensagem'] = 'Erro ao atualizar a pergunta. Por favor, tente novamente.';
    
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