<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$erro = '';

// Processar o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos.";
    } else {
        try {
            // Buscar usuário
            $stmt = $conexao->prepare("SELECT * FROM administradores WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($senha, $admin['senha'])) {
                // Login bem sucedido
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nome'] = $admin['nome'];
                $_SESSION['admin_email'] = $admin['email'];
                
                registrar_log('info', 'Login realizado com sucesso', ['admin_id' => $admin['id']]);
                
                header('Location: index.php');
                exit();
            } else {
                $erro = "Email ou senha incorretos.";
                registrar_log('alerta', 'Tentativa de login falhou', ['email' => $email]);
            }
        } catch (PDOException $e) {
            registrar_log('erro', 'Erro no login: ' . $e->getMessage());
            $erro = "Erro ao processar login. Tente novamente.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #fff;
            border-bottom: none;
            text-align: center;
            padding: 2rem 1rem;
        }
        .card-header h3 {
            margin: 0;
            color: #333;
        }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
        }
        .input-group-text {
            background-color: transparent;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h3>Painel Administrativo</h3>
                <p class="text-muted mb-0">Entre com suas credenciais</p>
            </div>
            <div class="card-body">
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Logout realizado com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="Digite seu e-mail" required 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="senha" 
                                   placeholder="Digite sua senha" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="recuperar_senha.php" class="text-decoration-none">
                        <i class="fas fa-key me-1"></i>Esqueceu a senha?
                    </a>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <small class="text-muted">Quiz Interativo &copy; <?php echo date('Y'); ?></small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para mostrar/ocultar senha -->
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
    </script>
</body>
</html>