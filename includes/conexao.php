<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Seu usuário do MySQL
define('DB_PASS', '');         // Sua senha do MySQL
define('DB_NAME', 'quiz_db');  // Nome do banco de dados
define('DB_CHARSET', 'utf8mb4');

try {
    // Estabelecer conexão com o banco de dados
    $conexao = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch(PDOException $e) {
    // Registrar erro detalhado no log
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    // Se o banco de dados não existir, tentar criá-lo
    if ($e->getCode() == 1049) { // 1049 é o código para "banco de dados desconhecido"
        try {
            // Conectar sem selecionar banco de dados
            $conexao = new PDO(
                "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS
            );
            
            // Criar o banco de dados
            $conexao->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Selecionar o banco de dados
            $conexao->exec("USE " . DB_NAME);
            
            // Criar tabelas necessárias
            $conexao->exec("
                CREATE TABLE IF NOT EXISTS configuracoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chave VARCHAR(50) NOT NULL UNIQUE,
                    valor TEXT,
                    descricao TEXT,
                    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS perguntas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    pergunta TEXT NOT NULL,
                    categoria VARCHAR(50) NOT NULL,
                    dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
                    pontos INT DEFAULT 10,
                    feedback TEXT,
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS respostas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    pergunta_id INT NOT NULL,
                    resposta TEXT NOT NULL,
                    correta BOOLEAN DEFAULT FALSE,
                    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS jogadores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
                    pontuacao_total INT DEFAULT 0,
                    jogos_completados INT DEFAULT 0,
                    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                    ultima_partida DATETIME
                );

                CREATE TABLE IF NOT EXISTS partidas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    jogador_id INT NOT NULL,
                    modo ENUM('classico', 'tempo', 'desafio') DEFAULT 'classico',
                    pontuacao INT DEFAULT 0,
                    acertos INT DEFAULT 0,
                    total_perguntas INT NOT NULL,
                    data_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
                    data_fim DATETIME,
                    status ENUM('iniciada', 'finalizada', 'cancelada') DEFAULT 'iniciada',
                    FOREIGN KEY (jogador_id) REFERENCES jogadores(id)
                );
            ");

            error_log("Banco de dados e tabelas criados com sucesso!");
            
        } catch(PDOException $e2) {
            error_log("Erro ao criar banco de dados: " . $e2->getMessage());
            die("Erro ao configurar banco de dados. Por favor, contate o administrador.");
        }
    } else {
        die("Erro de conexão com o banco de dados. Por favor, contate o administrador.");
    }
}

// Função para validar e limpar dados de entrada
function limparDados($dados) {
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados);
    return $dados;
}
?>