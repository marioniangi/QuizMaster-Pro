<?php
// Evitar que apareçam warnings
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Configuração de fuso horário
date_default_timezone_set('Africa/Luanda');

// URL base do projeto
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$pasta = dirname($_SERVER['PHP_SELF']);
define('BASE_URL', $protocolo . "://" . $host . $pasta . "/");

// Configurações do Quiz
define('PONTOS_RESPOSTA_CORRETA', 10);           // Pontos por resposta correta no modo clássico
define('PONTOS_RESPOSTA_RAPIDA', 15);            // Pontos por resposta correta no modo contra o tempo
define('PONTOS_RESPOSTA_DESAFIO', 20);           // Pontos por resposta correta no modo desafio
define('TEMPO_LIMITE_RESPOSTA', 30);             // Tempo limite em segundos para responder no modo contra o tempo
define('NUMERO_PERGUNTAS_PADRAO', 10);           // Número padrão de perguntas por partida
define('TEMPO_SESSAO', 3600);                    // Tempo de duração da sessão em segundos (1 hora)

// Configurações de E-mail (para recuperação de senha)
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_USERNAME', 'seu-email@gmail.com');
define('EMAIL_PASSWORD', 'sua-senha-app');
define('EMAIL_PORT', 587);
define('EMAIL_FROM', 'nao-responda@seuquiz.com');
define('EMAIL_FROM_NAME', 'Quiz Interativo');

// Configurações de Segurança
define('SALT_LENGTH', 22);                       // Tamanho do salt para senhas
define('MAX_LOGIN_ATTEMPTS', 3);                 // Máximo de tentativas de login
define('LOCKOUT_TIME', 900);                     // Tempo de bloqueio após exceder tentativas (15 minutos)
define('TOKEN_EXPIRATION', 3600);                // Tempo de expiração do token de redefinição de senha

// Configurações de Upload
define('UPLOAD_DIR', '../uploads/');             // Diretório para upload de imagens
define('MAX_FILE_SIZE', 5242880);                // Tamanho máximo de arquivo (5MB)
define('ALLOWED_EXTENSIONS', [                   // Extensões permitidas para upload
    'jpg',
    'jpeg',
    'png',
    'gif'
]);

// Níveis de Dificuldade
define('DIFICULDADES', [
    'facil' => 'Fácil',
    'medio' => 'Médio',
    'dificil' => 'Difícil'
]);

// Modos de Jogo
define('MODOS_JOGO', [
    'classico' => [
        'nome' => 'Clássico',
        'descricao' => 'Modo tradicional sem limite de tempo',
        'pontos_base' => PONTOS_RESPOSTA_CORRETA
    ],
    'tempo' => [
        'nome' => 'Contra o Tempo',
        'descricao' => 'Responda rápido para ganhar mais pontos',
        'pontos_base' => PONTOS_RESPOSTA_RAPIDA,
        'tempo_limite' => TEMPO_LIMITE_RESPOSTA
    ],
    'desafio' => [
        'nome' => 'Modo Desafio',
        'descricao' => 'Perguntas mais difíceis, mais pontos',
        'pontos_base' => PONTOS_RESPOSTA_DESAFIO
    ]
]);

// Função para gerar URLs seguras
function url_segura($url) {
    return htmlspecialchars(BASE_URL . $url, ENT_QUOTES, 'UTF-8');
}

// Função para formatar mensagens de erro
function mensagem_erro($mensagem) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>' . $mensagem . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Função para formatar mensagens de sucesso
function mensagem_sucesso($mensagem) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>' . $mensagem . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Função para validar e-mail
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para criar log
function registrar_log($tipo, $mensagem, $dados = []) {
    $data = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $usuario = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'Visitante';
    
    $log = "[{$data}] [{$ip}] [{$usuario}] [{$tipo}]: {$mensagem}";
    if (!empty($dados)) {
        $log .= " - Dados: " . json_encode($dados);
    }
    
    $arquivo_log = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '.log';
    error_log($log . PHP_EOL, 3, $arquivo_log);
}

// Função para limpar string
function limpar_string($string) {
    return preg_replace('/[^A-Za-z0-9\- ]/', '', $string);
}

// Função para gerar token seguro
function gerar_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Função para verificar se é uma requisição AJAX
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Função para redirecionar com mensagem
function redirecionar($url, $mensagem = '', $tipo = 'success') {
    if (!empty($mensagem)) {
        $_SESSION['mensagem'] = [
            'texto' => $mensagem,
            'tipo' => $tipo
        ];
    }
    header("Location: " . url_segura($url));
    exit();
}

// Inicializar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar e criar diretório de logs se não existir
$dir_logs = dirname(__DIR__) . '/logs';
if (!is_dir($dir_logs)) {
    mkdir($dir_logs, 0755, true);
}

// Verificar e criar diretório de uploads se não existir
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>