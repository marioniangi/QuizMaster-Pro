<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Iniciar resposta
$resposta = [
    'sucesso' => false,
    'mensagem' => '',
    'pergunta' => null
];

try {
    // Verificar ID da pergunta
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID da pergunta inválido.');
    }

    $id = (int)$_GET['id'];

    // Buscar pergunta com suas respostas e estatísticas
    $stmt = $conexao->prepare("
        SELECT 
            p.*,
            COUNT(DISTINCT pr.id) as total_usos,
            COALESCE(
                COUNT(DISTINCT CASE WHEN pr.correta = 1 THEN pr.id END),
                0
            ) as total_acertos
        FROM perguntas p
        LEFT JOIN respostas r ON p.id = r.pergunta_id
        LEFT JOIN partidas_respostas pr ON r.id = pr.resposta_id
        WHERE p.id = :id
        GROUP BY p.id
    ");

    $stmt->execute([':id' => $id]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        throw new Exception('Pergunta não encontrada.');
    }

    // Buscar respostas separadamente
    $stmt = $conexao->prepare("
        SELECT 
            id,
            resposta as texto,
            correta
        FROM respostas 
        WHERE pergunta_id = :pergunta_id
        ORDER BY id
    ");

    $stmt->execute([':pergunta_id' => $id]);
    $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular estatísticas
    $pergunta['total_usos'] = (int)$pergunta['total_usos'];
    $pergunta['total_acertos'] = (int)$pergunta['total_acertos'];
    
    if ($pergunta['total_usos'] > 0) {
        $pergunta['taxa_acerto'] = round(($pergunta['total_acertos'] / $pergunta['total_usos']) * 100, 1);
    } else {
        $pergunta['taxa_acerto'] = 0;
    }

    // Adicionar respostas ao objeto da pergunta
    $pergunta['opcoes'] = array_map(function($resposta) {
        return [
            'id' => (int)$resposta['id'],
            'texto' => $resposta['texto'],
            'correta' => (bool)$resposta['correta']
        ];
    }, $respostas);

    // Preparar resposta de sucesso
    $resposta = [
        'sucesso' => true,
        'pergunta' => $pergunta
    ];

} catch (PDOException $e) {
    error_log('Erro no banco de dados ao buscar pergunta: ' . $e->getMessage());
    $resposta['mensagem'] = 'Erro ao buscar a pergunta. Por favor, tente novamente.';
    
} catch (Exception $e) {
    $resposta['mensagem'] = $e->getMessage();
}

// Enviar resposta como JSON
header('Content-Type: application/json');
echo json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);