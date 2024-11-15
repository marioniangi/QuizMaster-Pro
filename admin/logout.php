<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se há um usuário logado
if (isset($_SESSION['admin_id'])) {
    // Registrar o logout no log
    registrar_log('info', 'Logout realizado', [
        'admin_id' => $_SESSION['admin_id'],
        'admin_nome' => $_SESSION['admin_nome'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'tempo_sessao' => time() - $_SESSION['admin_ultimo_acesso']
    ]);

    try {
        // Atualizar último acesso no banco de dados
        $stmt = $conexao->prepare("
            UPDATE administradores 
            SET ultimo_acesso = NOW() 
            WHERE id = :admin_id
        ");
        
        $stmt->execute([':admin_id' => $_SESSION['admin_id']]);

        // Registrar histórico de sessão
        $stmt = $conexao->prepare("
            INSERT INTO historico_sessoes (
                admin_id,
                data_inicio,
                data_fim,
                ip,
                user_agent
            ) VALUES (
                :admin_id,
                FROM_UNIXTIME(:inicio),
                NOW(),
                :ip,
                :user_agent
            )
        ");

        $stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':inicio' => $_SESSION['admin_ultimo_acesso'],
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);

    } catch (PDOException $e) {
        registrar_log('erro', 'Erro ao registrar logout: ' . $e->getMessage());
    }
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie da sessão se existir
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login com mensagem
header('Location: login.php?logout=1');
exit();
?>