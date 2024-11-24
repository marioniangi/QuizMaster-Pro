</div><!-- Fechamento do container principal aberto no header -->

    

    <!-- Scripts -->
    <!-- Bootstrap JS Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (necessário para algumas funcionalidades) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert2 para alertas bonitos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts personalizados -->
    <?php if(isset($pagina_admin) && $pagina_admin): ?>
    <script src="<?php echo BASE_URL; ?>../assets/js/admin.js"></script>
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