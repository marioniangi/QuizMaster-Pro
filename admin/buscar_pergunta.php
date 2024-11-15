<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

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
            COUNT(DISTINCT CASE WHEN pr.correta = 1 THEN pr.id END) as total_acertos,
            (
                SELECT MAX(data_partida)
                FROM partidas_respostas pr2
                JOIN respostas r2 ON pr2.resposta_id = r2.id
                WHERE r2.pergunta_id = p.id
            ) as ultimo_uso
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

    // Buscar respostas
    $stmt = $conexao->prepare("
        SELECT 
            r.*,
            COUNT(DISTINCT pr.id) as total_escolhas,
            COALESCE(
                COUNT(DISTINCT CASE WHEN pr.data_partida >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN pr.id END),
                0
            ) as escolhas_ultimo_mes
        FROM respostas r
        LEFT JOIN partidas_respostas pr ON r.id = pr.resposta_id
        WHERE r.pergunta_id = :pergunta_id
        GROUP BY r.id
        ORDER BY r.id
    ");

    $stmt->execute([':pergunta_id' => $id]);
    $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular estatísticas extras
    if ($pergunta['total_usos'] > 0) {
        $pergunta['taxa_acerto'] = round(($pergunta['total_acertos'] / $pergunta['total_usos']) * 100, 1);
    } else {
        $pergunta['taxa_acerto'] = 0;
    }

    if ($pergunta['ultimo_uso']) {
        $pergunta['dias_desde_ultimo_uso'] = floor((time() - strtotime($pergunta['ultimo_uso'])) / (60 * 60 * 24));
    } else {
        $pergunta['dias_desde_ultimo_uso'] = null;
    }

    // Formatação de dados sensíveis para exibição
    foreach ($respostas as &$resposta) {
        // Calcular porcentagem de escolha para cada resposta
        if ($pergunta['total_usos'] > 0) {
            $resposta['porcentagem_escolha'] = round(($resposta['total_escolhas'] / $pergunta['total_usos']) * 100, 1);
        } else {
            $resposta['porcentagem_escolha'] = 0;
        }

        // Adicionar tendência de último mês
        if ($resposta['escolhas_ultimo_mes'] > 0) {
            $resposta['tendencia_mensal'] = round(($resposta['escolhas_ultimo_mes'] / $resposta['total_escolhas']) * 100, 1);
        } else {
            $resposta['tendencia_mensal'] = 0;
        }

        // Limpar dados internos que não devem ser expostos
        unset($resposta['data_criacao']);
    }
    unset($resposta); // Quebrar referência

    // Adicionar respostas ao objeto da pergunta
    $pergunta['opcoes'] = $respostas;

    // Buscar tags relacionadas (se existirem)
    $stmt = $conexao->prepare("
        SELECT t.id, t.nome
        FROM tags t
        JOIN perguntas_tags pt ON t.id = pt.tag_id
        WHERE pt.pergunta_id = :pergunta_id
        ORDER BY t.nome
    ");

    $stmt->execute([':pergunta_id' => $id]);
    $pergunta['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Registrar visualização
    registrar_log('info', 'Pergunta visualizada', [
        'id' => $id,
        'admin_id' => $_SESSION['admin_id']
    ]);

    // Preparar resposta de sucesso
    $resposta = [
        'sucesso' => true,
        'pergunta' => $pergunta,
        'meta' => [
            'tempo_busca' => number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 4),
            'timestamp' => date('Y-m-d H:i:s'),
            'cache' => false
        ]
    ];

} catch (PDOException $e) {
    registrar_log('erro', 'Erro no banco de dados ao buscar pergunta: ' . $e->getMessage());
    $resposta['mensagem'] = 'Erro ao buscar a pergunta. Por favor, tente novamente.';
    
} catch (Exception $e) {
    $resposta['mensagem'] = $e->getMessage();
    
} finally {
    // Enviar resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// Função para verificar se uma pergunta está em cache
function verificar_cache($id) {
    $arquivo_cache = "../cache/perguntas/{$id}.json";
    
    if (file_exists($arquivo_cache)) {
        $tempo_cache = 300; // 5 minutos
        if (time() - filemtime($arquivo_cache) < $tempo_cache) {
            return json_decode(file_get_contents($arquivo_cache), true);
        }
    }
    
    return false;
}

// Função para salvar pergunta em cache
function salvar_cache($id, $dados) {
    $diretorio_cache = "../cache/perguntas";
    
    if (!is_dir($diretorio_cache)) {
        mkdir($diretorio_cache, 0755, true);
    }
    
    $arquivo_cache = "{$diretorio_cache}/{$id}.json";
    file_put_contents($arquivo_cache, json_encode($dados));
}

// Limpar cache antigo (probabilidade de 5%)
if (rand(1, 20) === 1) {
    $diretorio_cache = "../cache/perguntas";
    if (is_dir($diretorio_cache)) {
        $arquivos = glob("{$diretorio_cache}/*.json");
        foreach ($arquivos as $arquivo) {
            if (time() - filemtime($arquivo) > 3600) { // 1 hora
                unlink($arquivo);
            }
        }
    }
}