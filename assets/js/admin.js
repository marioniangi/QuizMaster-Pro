// Classe principal do Painel Administrativo
class AdminPanel {
    constructor() {
        // Elementos do DOM
        this.sidebar = document.querySelector('.admin-sidebar');
        this.toggleButton = document.querySelector('.toggle-sidebar');
        this.deleteButtons = document.querySelectorAll('.btn-delete');
        this.editButtons = document.querySelectorAll('.btn-edit');
        this.searchInput = document.querySelector('#searchInput');
        this.filterSelect = document.querySelector('#filterSelect');
        
        // Bindings
        this.toggleSidebar = this.toggleSidebar.bind(this);
        this.confirmDelete = this.confirmDelete.bind(this);
        this.handleEdit = this.handleEdit.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
        this.handleFilter = this.handleFilter.bind(this);
        this.initializeEventListeners();
    }

    // Inicializa todos os event listeners
    initializeEventListeners() {
        // Toggle Sidebar
        if (this.toggleButton) {
            this.toggleButton.addEventListener('click', this.toggleSidebar);
        }

        // Delete Buttons
        this.deleteButtons.forEach(button => {
            button.addEventListener('click', () => this.confirmDelete(button.dataset.id));
        });

        // Edit Buttons
        this.editButtons.forEach(button => {
            button.addEventListener('click', () => this.handleEdit(button.dataset.id));
        });

        // Search Input
        if (this.searchInput) {
            this.searchInput.addEventListener('input', this.debounce(this.handleSearch, 500));
        }

        // Filter Select
        if (this.filterSelect) {
            this.filterSelect.addEventListener('change', this.handleFilter);
        }

        // Form Validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit);
        });
    }

    // Toggle da sidebar em dispositivos móveis
    toggleSidebar() {
        this.sidebar.classList.toggle('active');
    }

    // Confirma exclusão de item
    confirmDelete(id) {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#7f8c8d',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.deleteItem(id);
            }
        });
    }

    // Exclui item via AJAX
    async deleteItem(id) {
        try {
            const response = await fetch('excluir.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });

            const data = await response.json();

            if (data.sucesso) {
                Swal.fire({
                    icon: 'success',
                    title: 'Excluído!',
                    text: 'Item excluído com sucesso.',
                    showConfirmButton: false,
                    timer: 1500
                });

                // Remove o item da tabela
                document.querySelector(`tr[data-id="${id}"]`).remove();
            } else {
                throw new Error(data.mensagem);
            }
        } catch (erro) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Ocorreu um erro ao excluir o item.'
            });
        }
    }

    // Manipula edição de item
    async handleEdit(id) {
        try {
            const response = await fetch(`buscar_item.php?id=${id}`);
            const data = await response.json();

            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            // Preenche o modal de edição com os dados
            const modal = document.getElementById('editModal');
            const form = modal.querySelector('form');
            
            // Preenche os campos do formulário
            Object.keys(data.item).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data.item[key];
                }
            });

            // Mostra o modal
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

        } catch (erro) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao carregar dados para edição.'
            });
        }
    }

    // Manipula busca
    async handleSearch() {
        const searchTerm = this.searchInput.value;
        const filter = this.filterSelect ? this.filterSelect.value : '';

        try {
            const response = await fetch(`buscar.php?termo=${searchTerm}&filtro=${filter}`);
            const data = await response.json();

            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            this.updateTable(data.resultados);

        } catch (erro) {
            console.error('Erro na busca:', erro);
        }
    }

    // Manipula filtros
    handleFilter() {
        this.handleSearch();
    }

    // Atualiza tabela com resultados
    updateTable(resultados) {
        const tbody = document.querySelector('.admin-table tbody');
        tbody.innerHTML = '';

        resultados.forEach(item => {
            const tr = document.createElement('tr');
            tr.dataset.id = item.id;
            
            // Personalizar conforme sua estrutura de dados
            tr.innerHTML = `
                <td>${item.id}</td>
                <td>${item.nome}</td>
                <td>${item.categoria}</td>
                <td>
                    <button class="btn btn-sm btn-admin-primary btn-edit" data-id="${item.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-admin-danger btn-delete" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        // Reinicializar event listeners
        this.deleteButtons = document.querySelectorAll('.btn-delete');
        this.editButtons = document.querySelectorAll('.btn-edit');
        this.initializeEventListeners();
    }

    // Validação de formulário
    handleFormSubmit(event) {
        if (!event.target.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        event.target.classList.add('was-validated');
    }

    // Função debounce para busca
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Funções para o editor de perguntas
    initQuestionEditor() {
        const addOptionBtn = document.getElementById('addOptionBtn');
        const optionsContainer = document.getElementById('optionsContainer');

        if (addOptionBtn) {
            addOptionBtn.addEventListener('click', () => {
                const optionCount = optionsContainer.children.length;
                if (optionCount < 6) { // Máximo de 6 opções
                    const optionHtml = `
                        <div class="option-group mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="opcoes[]" required>
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <input type="radio" name="correta" value="${optionCount}" required>
                                    </div>
                                    <button type="button" class="btn btn-danger remove-option">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    optionsContainer.insertAdjacentHTML('beforeend', optionHtml);
                }

                if (optionsContainer.children.length >= 6) {
                    addOptionBtn.style.display = 'none';
                }
            });
        }

        // Delegação de eventos para remover opções
        if (optionsContainer) {
            optionsContainer.addEventListener('click', (e) => {
                if (e.target.closest('.remove-option')) {
                    e.target.closest('.option-group').remove();
                    addOptionBtn.style.display = 'block';
                    this.reorderOptions();
                }
            });
        }
    }

    // Reordena as opções após remoção
    reorderOptions() {
        const options = document.querySelectorAll('.option-group');
        options.forEach((option, index) => {
            const radio = option.querySelector('input[type="radio"]');
            radio.value = index;
        });
    }

    // Inicializa gráficos do dashboard
    initDashboardCharts() {
        // Exemplo de gráfico de estatísticas do quiz
        const ctx = document.getElementById('quizStats');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Partidas Jogadas',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }
}

// Inicialização quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    const adminPanel = new AdminPanel();
    
    // Inicializar componentes específicos baseado na página atual
    if (document.querySelector('.question-editor')) {
        adminPanel.initQuestionEditor();
    }
    
    if (document.querySelector('.dashboard')) {
        adminPanel.initDashboardCharts();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    initializeQuestionForm();
});

function initializeQuestionForm() {
    const optionsContainer = document.getElementById('optionsContainer');
    const addOptionBtn = document.getElementById('addOptionBtn');
    const addPerguntaModal = document.getElementById('addPerguntaModal');

    // Adicionar opções iniciais quando o modal é aberto
    if (addPerguntaModal) {
        addPerguntaModal.addEventListener('show.bs.modal', function() {
            if (optionsContainer) {
                optionsContainer.innerHTML = ''; // Limpar opções existentes
                // Adicionar 4 opções iniciais
                for (let i = 0; i < 4; i++) {
                    addNewOption();
                }
            }
        });
    }

    // Adicionar evento ao botão de adicionar opção
    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addNewOption();
        });
    }

    // Inicializar o formulário
    const form = document.getElementById('formAddPergunta');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
}

function addNewOption() {
    const optionsContainer = document.getElementById('optionsContainer');
    if (!optionsContainer) return;

    const optionCount = optionsContainer.children.length;
    const optionDiv = document.createElement('div');
    optionDiv.className = 'mb-3 option-container';

    optionDiv.innerHTML = `
        <div class="input-group">
            <div class="input-group-text">
                <input type="radio" name="resposta_correta" value="${optionCount}"
                    ${optionCount === 0 ? 'checked' : ''} required>
            </div>
            <input type="text" class="form-control" name="opcoes[]" 
                placeholder="Opção ${optionCount + 1}" required>
            ${optionCount > 3 ? `
                <button type="button" class="btn btn-danger btn-remove-option">
                    <i class="fas fa-times"></i>
                </button>
            ` : ''}
        </div>
    `;

    optionsContainer.appendChild(optionDiv);

    // Adicionar evento para remover opção
    const removeBtn = optionDiv.querySelector('.btn-remove-option');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            optionDiv.remove();
            updateOptionsNumbering();
        });
    }
}

function updateOptionsNumbering() {
    const optionsContainer = document.getElementById('optionsContainer');
    if (!optionsContainer) return;

    const options = optionsContainer.getElementsByClassName('option-container');
    Array.from(options).forEach((option, index) => {
        const radio = option.querySelector('input[type="radio"]');
        const input = option.querySelector('input[type="text"]');
        
        radio.value = index;
        input.placeholder = `Opção ${index + 1}`;
    });
}

async function handleFormSubmit(e) {
    e.preventDefault();

    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.sucesso) {
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: result.mensagem,
                timer: 1500,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            throw new Error(result.mensagem);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message || 'Erro ao salvar a pergunta'
        });
    }
}

// Manipulação da categoria
document.addEventListener('DOMContentLoaded', function() {
    const categoriaSelect = document.getElementById('categoria');
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            const novaCategoriaContainer = document.getElementById('novaCategoriaContainer');
            const novaCategoriaInput = novaCategoriaContainer?.querySelector('input');
            
            if (this.value === 'nova') {
                novaCategoriaContainer?.classList.remove('d-none');
                if (novaCategoriaInput) novaCategoriaInput.required = true;
            } else {
                novaCategoriaContainer?.classList.add('d-none');
                if (novaCategoriaInput) novaCategoriaInput.required = false;
            }
        });
    }
});