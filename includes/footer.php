</div><!-- Fechamento do container principal aberto no header -->

    <!-- Footer -->
    <footer class="footer mt-auto py-3" style="background: linear-gradient(45deg, var(--cor-primaria), var(--cor-secundaria));">
        <div class="container">
            <div class="row">
                <!-- Coluna da Esquerda -->
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="text-white mb-3">Quiz Interativo</h5>
                    <p class="text-white-50">
                        Teste seus conhecimentos de forma divertida e educativa. 
                        Aprenda enquanto compete com outros jogadores!
                    </p>
                </div>

                <!-- Coluna do Meio -->
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="text-white mb-3">Links Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo url_segura(''); ?>" class="text-white-50 text-decoration-none">Início</a></li>
                        <li><a href="<?php echo url_segura('ranking.php'); ?>" class="text-white-50 text-decoration-none">Ranking</a></li>
                        <li><a href="<?php echo url_segura('sobre.php'); ?>" class="text-white-50 text-decoration-none">Sobre</a></li>
                        <?php if(isset($_SESSION['admin_id'])): ?>
                        <li><a href="<?php echo url_segura('admin/'); ?>" class="text-white-50 text-decoration-none">Painel Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Coluna da Direita -->
                <div class="col-md-4">
                    <h5 class="text-white mb-3">Conecte-se</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white-50 text-decoration-none">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50 text-decoration-none">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50 text-decoration-none">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50 text-decoration-none">
                            <i class="fab fa-linkedin fa-lg"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Linha de Copyright -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="border-top border-white-50 pt-3">
                        <p class="text-white-50 text-center mb-0">
                            &copy; <?php echo date('Y'); ?> Quiz Interativo. Todos os direitos reservados.
                            <br>
                            <small>Desenvolvido como projeto acadêmico para ISPTEC</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <!-- Bootstrap JS Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (necessário para algumas funcionalidades) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert2 para alertas bonitos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts personalizados -->
    <?php if(isset($pagina_admin) && $pagina_admin): ?>
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
    <?php endif; ?>
    
    <script src="<?php echo BASE_URL; ?>assets/js/quiz.js"></script>

    <!-- Script para mostrar mensagens de feedback -->
    <?php if(isset($_SESSION['mensagem'])): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $_SESSION['mensagem']['tipo']; ?>',
            title: '<?php echo $_SESSION['mensagem']['texto']; ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    </script>
    <?php 
    unset($_SESSION['mensagem']);
    endif; 
    ?>

    <!-- Scripts específicos da página -->
    <?php if(isset($scripts_pagina)): ?>
    <?php foreach($scripts_pagina as $script): ?>
    <script src="<?php echo BASE_URL . $script; ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Script para mostrar tooltips do Bootstrap -->
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

</body>
</html>