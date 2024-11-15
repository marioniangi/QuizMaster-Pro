<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Processar tentativa de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? limparDados($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    $erros = [];
    
    // Validar campos
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido.';
    }
    
    if (empty($senha)) {
        $erros[] = 'Senha é obrigatória.';
    }

    // Se não houver erros de validação
    if (empty($erros)) {
        try {
            // Verificar tentativas de login
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $conexao->prepare("
                SELECT COUNT(*) as tentativas, 
                       MAX(data_tentativa) as ultima_tentativa
                FROM tentativas_login 
                WHERE ip = :ip AND data_tentativa > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([':ip' => $ip]);
            $tentativas = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tentativas['tentativas'] >= MAX_LOGIN_ATTEMPTS) {
                $tempo_bloqueio = strtotime($tentativas['ultima_tentativa']) + LOCKOUT_TIME - time();
                if ($tempo_bloqueio > 0) {
                    $erros[] = "Muitas tentativas de login. Tente novamente em " . 
                              ceil($tempo_bloqueio / 60) . " minutos.";
                    registrar_log('alerta', 'Tentativa de login bloqueada por excesso de tentativas', [
                        'ip' => $ip,
                        'email' => $email
                    ]);
                }
            }

            if (empty($erros)) {
                // Buscar administrador
                $stmt = $conexao->prepare("
                    SELECT id, nome, email, senha, status 
                    FROM administradores 
                    WHERE email = :email
                ");
                $stmt->execute([':email' => $email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($senha, $admin['senha'])) {
                    // Verificar status
                    if ($admin['status'] !== 'ativo') {
                        $erros[] = 'Sua conta está desativada. Entre em contato com o administrador.';
                    } else {
                        // Login bem-sucedido
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_nome'] = $admin['nome'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_ultimo_acesso'] = time();

                        // Limpar tentativas de login
                        $stmt = $conexao->prepare("
                            DELETE FROM tentativas_login WHERE ip = :ip
                        ");
                        $stmt->execute([':ip' => $ip]);

                        // Registrar log de acesso
                        registrar_log('info', 'Login bem-sucedido', [
                            'admin_id' => $admin['id'],
                            'ip' => $ip
                        ]);

                        // Redirecionar para o dashboard
                        header('Location: index.php');
                        exit();
                    }
                } else {
                    // Registrar tentativa de login
                    $stmt = $conexao->prepare("
                        INSERT INTO tentativas_login (ip, email, data_tentativa)
                        VALUES (:ip, :email, NOW())
                    ");
                    $stmt->execute([
                        ':ip' => $ip,
                        ':email' => $email
                    ]);

                    $erros[] = 'E-mail ou senha incorretos.';
                }
            }
        } catch (PDOException $e) {
            registrar_log('erro', 'Erro no login: ' . $e->getMessage());
            $erros[] = 'Erro ao processar login. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Administrativo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            display: block;
        }
        .login-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--admin-primary);
        }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            font-weight: 500;
        }
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Quiz Logo" class="login-logo">
            
            <!-- Título -->
            <h2 class="login-title">Painel Administrativo</h2>
            
            <!-- Mensagens de Erro -->
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php foreach($erros as $erro): ?>
                        <p class="mb-0"><i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?></p>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

                    <!-- Mensagem de Logout -->
        <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Você saiu do sistema com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
            
            <!-- Formulário de Login -->
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo isset($email) ? $email : ''; ?>" required>
                        <div class="invalid-feedback">
                            Por favor, insira um e-mail válido.
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Senha</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="senha" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="invalid-feedback">
                            Por favor, insira sua senha.
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </button>
            </form>
            
            <!-- Link Esqueceu a Senha -->
            <div class="forgot-password">
                <a href="recuperar_senha.php" class="text-decoration-none">
                    <i class="fas fa-key me-1"></i>Esqueceu a senha?
                </a>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <small>Quiz Interativo &copy; <?php echo date('Y'); ?></small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para toggle de senha -->
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const senhaInput = document.querySelector('input[name="senha"]');
            const icon = this.querySelector('i');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Validação do formulário
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>