// Classe principal do Dashboard
class AdminDashboard {
    constructor() {
        this.initializeComponents();
        this.setupEventListeners();
        this.initializeCharts();
        this.startAutoRefresh();
    }

    // Inicializar componentes
    initializeComponents() {
        // Tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });

        // Popovers
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(popover => {
            new bootstrap.Popover(popover);
        });

        // Inicializar o modal de adicionar pergunta
        this.perguntaModal = new bootstrap.Modal(document.getElementById('addPerguntaModal'), {
            backdrop: 'static'
        });

        // Inicializar form de adicionar pergunta
        this.formAddPergunta = document.getElementById('formAddPergunta');
        if (this.formAddPergunta) {
            this.initializeQuestionForm();
        }
    }

    // Configurar event listeners
    setupEventListeners() {
        // Atualizar estatísticas
        const btnRefreshStats = document.getElementById('btnRefreshStats');
        if (btnRefreshStats) {
            btnRefreshStats.addEventListener('click', () => this.refreshStats());
        }

        // Mudar período do gráfico
        const chartPeriod = document.getElementById('chartPeriod');
        if (chartPeriod) {
            chartPeriod.addEventListener('change', () => this.updateChartData());
        }

        // Toggle sidebar em mobile
        const toggleSidebar = document.querySelector('.toggle-sidebar');
        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', () => this.toggleSidebar());
        }
    }

    // Inicializar gráficos
    initializeCharts() {
        const ctx = document.getElementById('quizStats');
        if (!ctx) return;

        this.mainChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Partidas Jogadas',
                    data: [],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#fff',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animations: {
                    tension: {
                        duration: 1000,
                        easing: 'linear'
                    }
                }
            }
        });

        // Carregar dados iniciais
        this.updateChartData();
    }

    // Atualizar dados do gráfico
    async updateChartData() {
        const period = document.getElementById('chartPeriod')?.value || '7';
        
        try {
            const response = await fetch(`get_chart_data.php?period=${period}`);
            const data = await response.json();
            
            if (data.sucesso) {
                this.mainChart.data.labels = data.labels;
                this.mainChart.data.datasets[0].data = data.dados;
                this.mainChart.update();
            }
        } catch (error) {
            console.error('Erro ao atualizar gráfico:', error);
            // Mostrar mensagem de erro sutil
            const chartContainer = document.querySelector('.chart-container');
            if (chartContainer) {
                chartContainer.insertAdjacentHTML('beforeend', `
                    <div class="chart-error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        Erro ao atualizar dados
                    </div>
                `);
                setTimeout(() => {
                    document.querySelector('.chart-error-message')?.remove();
                }, 3000);
            }
        }
    }

    // Atualizar estatísticas
    async refreshStats() {
        const btnRefresh = document.getElementById('btnRefreshStats');
        if (btnRefresh) {
            btnRefresh.disabled = true;
            btnRefresh.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i> Atualizando...';
        }

        try {
            const response = await fetch('get_stats.php');
            const data = await response.json();
            
            if (data.sucesso) {
                // Atualizar cards de estatísticas com animação
                Object.keys(data.stats).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        this.animateNumber(element, data.stats[key]);
                    }
                });

                // Atualizar gráfico
                this.updateChartData();

                // Mostrar mensagem de sucesso
                this.showToast('Estatísticas atualizadas com sucesso!', 'success');
            }
        } catch (error) {
            console.error('Erro ao atualizar estatísticas:', error);
            this.showToast('Erro ao atualizar estatísticas', 'error');
        } finally {
            if (btnRefresh) {
                btnRefresh.disabled = false;
                btnRefresh.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Atualizar Dados';
            }
        }
    }

    // Inicializar formulário de perguntas
    initializeQuestionForm() {
        const optionsContainer = document.getElementById('optionsContainer');
        const addOptionBtn = document.getElementById('addOptionBtn');

        if (addOptionBtn && optionsContainer) {
            // Adicionar opções iniciais
            for (let i = 0; i < 4; i++) {
                this.addNewOption();
            }

            // Event listener para adicionar mais opções
            addOptionBtn.addEventListener('click', () => this.addNewOption());

            // Event listener para o formulário
            this.formAddPergunta.addEventListener('submit', e => this.handleFormSubmit(e));
        }
    }

    // Adicionar nova opção de resposta
    addNewOption() {
        const optionsContainer = document.getElementById('optionsContainer');
        if (!optionsContainer) return;

        const optionCount = optionsContainer.children.length;
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-container mb-2';

        optionDiv.innerHTML = `
            <div class="input-group">
                <div class="input-group-text">
                    <input type="radio" name="resposta_correta" value="${optionCount}"
                           ${optionCount === 0 ? 'checked' : ''} required
                           class="form-check-input mt-0">
                </div>
                <input type="text" class="form-control" name="opcoes[]" 
                       placeholder="Digite a opção ${optionCount + 1}" required>
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
            removeBtn.addEventListener('click', () => {
                optionDiv.remove();
                this.updateOptionsNumbering();
            });
        }
    }

    // Atualizar numeração das opções
    updateOptionsNumbering() {
        const optionsContainer = document.getElementById('optionsContainer');
        if (!optionsContainer) return;

        const options = optionsContainer.getElementsByClassName('option-container');
        Array.from(options).forEach((option, index) => {
            const radio = option.querySelector('input[type="radio"]');
            const input = option.querySelector('input[type="text"]');
            
            radio.value = index;
            input.placeholder = `Digite a opção ${index + 1}`;
        });
    }

    // Animação de números
    animateNumber(element, finalValue) {
        const duration = 1000;
        const startValue = parseInt(element.textContent);
        const increment = (finalValue - startValue) / (duration / 16);
        let currentValue = startValue;

        const animate = () => {
            currentValue += increment;
            if (
                (increment > 0 && currentValue >= finalValue) ||
                (increment < 0 && currentValue <= finalValue)
            ) {
                element.textContent = finalValue;
                return;
            }

            element.textContent = Math.round(currentValue);
            requestAnimationFrame(animate);
        };

        animate();
    }

    // Mostrar toast
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, {
            delay: 3000,
            animation: true
        });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // Auto refresh a cada 5 minutos
    startAutoRefresh() {
        setInterval(() => {
            this.refreshStats();
        }, 5 * 60 * 1000);
    }

    // Toggle sidebar em mobile
    toggleSidebar() {
        document.querySelector('.admin-sidebar')?.classList.toggle('active');
    }
}

// Inicializar quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboard();
});