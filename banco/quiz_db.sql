-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 15-Nov-2024 às 18:28
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `quiz_db`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `administradores`
--

INSERT INTO `administradores` (`id`, `nome`, `email`, `senha`, `data_criacao`) VALUES
(1, 'Administrador', 'admin@quiz.com', '$2y$10$8tK5BxJ.HZ6T7ZeD0xZ1.uQR5qYwjUPP1Wl.a7ETQnN0KZ5r9WS4e', '2024-11-11 18:41:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`, `descricao`, `data_atualizacao`) VALUES
(1, 'perguntas_por_partida', '10', 'Número de perguntas por partida', '2024-11-12 05:52:18'),
(2, 'tempo_resposta', '30', 'Tempo em segundos para responder no modo tempo', '2024-11-12 05:52:18'),
(3, 'pontos_resposta_normal', '10', 'Pontos base para respostas corretas no modo normal', '2024-11-12 05:52:18'),
(4, 'pontos_resposta_tempo', '15', 'Pontos base para respostas corretas no modo tempo', '2024-11-12 05:52:18'),
(5, 'pontos_resposta_desafio', '20', 'Pontos base para respostas corretas no modo desafio', '2024-11-12 05:52:18'),
(6, 'permitir_pular_pergunta', '0', 'Permitir que jogadores pulem perguntas', '2024-11-12 05:52:18'),
(7, 'mostrar_ranking_global', '1', 'Mostrar ranking global na página inicial', '2024-11-12 05:52:18');

-- --------------------------------------------------------

--
-- Estrutura da tabela `jogadores`
--

CREATE TABLE `jogadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `pontuacao_total` int(11) DEFAULT 0,
  `melhor_pontuacao` int(11) DEFAULT 0,
  `jogos_completados` int(11) DEFAULT 0,
  `data_ultimo_jogo` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `partidas`
--

CREATE TABLE `partidas` (
  `id` int(11) NOT NULL,
  `jogador_id` int(11) NOT NULL,
  `modo` enum('classico','tempo','desafio') DEFAULT 'classico',
  `pontuacao` int(11) DEFAULT 0,
  `acertos` int(11) DEFAULT 0,
  `total_perguntas` int(11) NOT NULL,
  `status` enum('em_andamento','finalizada','cancelada') NOT NULL DEFAULT 'em_andamento',
  `data_partida` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `data_fim` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `partidas_respostas`
--

CREATE TABLE `partidas_respostas` (
  `id` int(11) NOT NULL,
  `partida_id` int(11) NOT NULL,
  `pergunta_id` int(11) NOT NULL,
  `resposta_id` int(11) NOT NULL,
  `tempo_resposta` int(11) DEFAULT NULL,
  `correta` tinyint(1) DEFAULT 0,
  `data_resposta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `perguntas`
--

CREATE TABLE `perguntas` (
  `id` int(11) NOT NULL,
  `pergunta` text NOT NULL,
  `dificuldade` enum('facil','medio','dificil') DEFAULT 'medio',
  `categoria` varchar(50) NOT NULL,
  `pontos` int(11) DEFAULT 10,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `perguntas`
--

INSERT INTO `perguntas` (`id`, `pergunta`, `dificuldade`, `categoria`, `pontos`, `data_criacao`) VALUES
(1, 'Qual estrutura de controle é usada para repetição em programação?', 'facil', 'Programação', 10, '2024-11-12 06:17:11'),
(2, 'Em programação orientada a objetos, o que é uma classe?', 'facil', 'Programação', 10, '2024-11-12 06:17:11'),
(3, 'Qual é o operador de atribuição em muitas linguagens de programação?', 'facil', 'Programação', 10, '2024-11-12 06:17:11'),
(4, 'O que é recursão em programação?', 'medio', 'Programação', 15, '2024-11-12 06:17:11'),
(5, 'O que é polimorfismo em POO?', 'medio', 'Programação', 15, '2024-11-12 06:17:11'),
(6, 'O que é uma expressão lambda?', 'medio', 'Programação', 15, '2024-11-12 06:17:11'),
(7, 'Qual é a diferença entre thread e processo?', 'dificil', 'Programação', 20, '2024-11-12 06:17:11'),
(8, 'O que é o padrão de projeto Singleton?', 'dificil', 'Programação', 20, '2024-11-12 06:17:11'),
(9, 'Qual protocolo é usado para navegação web?', 'facil', 'Redes', 10, '2024-11-12 06:17:11'),
(10, 'O que significa a sigla IP?', 'facil', 'Redes', 10, '2024-11-12 06:17:11'),
(11, 'Qual é a função do protocolo TCP?', 'medio', 'Redes', 15, '2024-11-12 06:17:11'),
(12, 'O que é um endereço MAC?', 'medio', 'Redes', 15, '2024-11-12 06:17:11'),
(13, 'O que é um ataque DDoS?', 'dificil', 'Redes', 20, '2024-11-12 06:17:11'),
(14, 'O que é NAT em redes?', 'dificil', 'Redes', 20, '2024-11-12 06:17:11'),
(15, 'Qual é a complexidade de busca em um array não ordenado?', 'facil', 'Algoritmos', 10, '2024-11-12 06:17:11'),
(16, 'O que é um algoritmo de ordenação?', 'facil', 'Algoritmos', 10, '2024-11-12 06:17:11'),
(17, 'Qual é a complexidade do algoritmo QuickSort no caso médio?', 'medio', 'Algoritmos', 15, '2024-11-12 06:17:11'),
(18, 'O que é um algoritmo guloso (greedy)?', 'medio', 'Algoritmos', 15, '2024-11-12 06:17:11'),
(19, 'O que é programação dinâmica?', 'dificil', 'Algoritmos', 20, '2024-11-12 06:17:11'),
(20, 'O que é o problema do caixeiro viajante?', 'dificil', 'Algoritmos', 20, '2024-11-12 06:17:11'),
(21, 'O que é um requisito funcional?', 'facil', 'Engenharia de Software', 10, '2024-11-12 06:17:11'),
(22, 'O que significa a sigla UML?', 'facil', 'Engenharia de Software', 10, '2024-11-12 06:17:11'),
(23, 'O que é o método ágil Scrum?', 'medio', 'Engenharia de Software', 15, '2024-11-12 06:17:11'),
(24, 'O que é integração contínua (CI)?', 'medio', 'Engenharia de Software', 15, '2024-11-12 06:17:11'),
(25, 'O que é o padrão arquitetural MVC?', 'dificil', 'Engenharia de Software', 20, '2024-11-12 06:17:11'),
(26, 'O que é refatoração de código?', 'dificil', 'Engenharia de Software', 20, '2024-11-12 06:17:11');

-- --------------------------------------------------------

--
-- Estrutura da tabela `respostas`
--

CREATE TABLE `respostas` (
  `id` int(11) NOT NULL,
  `pergunta_id` int(11) NOT NULL,
  `resposta` text NOT NULL,
  `correta` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `respostas`
--

INSERT INTO `respostas` (`id`, `pergunta_id`, `resposta`, `correta`) VALUES
(1, 1, 'Loop/Laço', 1),
(2, 1, 'Condição', 0),
(3, 1, 'Função', 0),
(4, 1, 'Variável', 0),
(5, 2, 'Um modelo para criar objetos', 1),
(6, 2, 'Uma variável', 0),
(7, 2, 'Um loop', 0),
(8, 2, 'Uma função', 0),
(9, 3, '=', 1),
(10, 3, '==', 0),
(11, 3, '+', 0),
(12, 3, '-', 0),
(13, 4, 'Uma função que chama a si mesma', 1),
(14, 4, 'Um loop infinito', 0),
(15, 4, 'Um tipo de variável', 0),
(16, 4, 'Um erro de programa', 0),
(17, 5, 'Capacidade de um objeto se comportar de diferentes formas', 1),
(18, 5, 'Um tipo de variável', 0),
(19, 5, 'Um erro de compilação', 0),
(20, 5, 'Uma função recursiva', 0),
(21, 6, 'Uma função anônima', 1),
(22, 6, 'Um tipo de loop', 0),
(23, 6, 'Uma variável global', 0),
(24, 6, 'Um operador matemático', 0),
(25, 7, 'Thread compartilha recursos, processo é independente', 1),
(26, 7, 'São a mesma coisa', 0),
(27, 7, 'Processo é mais rápido que thread', 0),
(28, 7, 'Thread usa mais memória que processo', 0),
(29, 8, 'Garante uma única instância de uma classe', 1),
(30, 8, 'Um tipo de herança', 0),
(31, 8, 'Um método estático', 0),
(32, 8, 'Uma classe abstrata', 0),
(33, 9, 'HTTP/HTTPS', 1),
(34, 9, 'FTP', 0),
(35, 9, 'SMTP', 0),
(36, 9, 'SSH', 0),
(37, 10, 'Internet Protocol', 1),
(38, 10, 'Internet Program', 0),
(39, 10, 'Internal Protocol', 0),
(40, 10, 'Internet Port', 0),
(41, 11, 'Garante entrega confiável de dados', 1),
(42, 11, 'Gerencia endereços IP', 0),
(43, 11, 'Controla o acesso à rede', 0),
(44, 11, 'Envia emails', 0),
(45, 12, 'Identificador único de hardware de rede', 1),
(46, 12, 'Um tipo de protocolo', 0),
(47, 12, 'Um sistema operacional', 0),
(48, 12, 'Um tipo de cabo', 0),
(49, 13, 'Ataque distribuído de negação de serviço', 1),
(50, 13, 'Um tipo de firewall', 0),
(51, 13, 'Um protocolo de rede', 0),
(52, 13, 'Um sistema de backup', 0),
(53, 14, 'Tradução de endereços de rede', 1),
(54, 14, 'Um tipo de protocolo', 0),
(55, 14, 'Um sistema de segurança', 0),
(56, 14, 'Um tipo de cabo', 0),
(57, 15, 'O(n)', 1),
(58, 15, 'O(1)', 0),
(59, 15, 'O(log n)', 0),
(60, 15, 'O(n²)', 0),
(61, 16, 'Algoritmo que organiza elementos em ordem', 1),
(62, 16, 'Algoritmo que busca elementos', 0),
(63, 16, 'Algoritmo que soma elementos', 0),
(64, 16, 'Algoritmo que remove elementos', 0),
(65, 17, 'O(n log n)', 1),
(66, 17, 'O(n)', 0),
(67, 17, 'O(n²)', 0),
(68, 17, 'O(1)', 0),
(69, 18, 'Faz escolha localmente ótima em cada etapa', 1),
(70, 18, 'Usa muita memória', 0),
(71, 18, 'É sempre o mais rápido', 0),
(72, 18, 'Nunca funciona', 0),
(73, 19, 'Resolve problemas dividindo em subproblemas', 1),
(74, 19, 'É um tipo de linguagem', 0),
(75, 19, 'Usa apenas recursão', 0),
(76, 19, 'É o mesmo que força bruta', 0),
(77, 20, 'Encontrar menor rota visitando todos os pontos', 1),
(78, 20, 'Calcular troco', 0),
(79, 20, 'Ordenar números', 0),
(80, 20, 'Buscar em árvore', 0),
(81, 21, 'Descreve o que o sistema deve fazer', 1),
(82, 21, 'Define a performance do sistema', 0),
(83, 21, 'Especifica o hardware necessário', 0),
(84, 21, 'Define o prazo do projeto', 0),
(85, 22, 'Unified Modeling Language', 1),
(86, 22, 'Universal Making Logic', 0),
(87, 22, 'Unit Management Level', 0),
(88, 22, 'User Machine Learning', 0),
(89, 23, 'Framework ágil para gestão de projetos', 1),
(90, 23, 'Uma linguagem de programação', 0),
(91, 23, 'Um tipo de banco de dados', 0),
(92, 23, 'Um sistema operacional', 0),
(93, 24, 'Integração automática de código no repositório', 1),
(94, 24, 'Um tipo de teste', 0),
(95, 24, 'Uma metodologia ágil', 0),
(96, 24, 'Um padrão de projeto', 0),
(97, 25, 'Model-View-Controller', 1),
(98, 25, 'Main-Vector-Class', 0),
(99, 25, 'Multiple-Version-Control', 0),
(100, 25, 'Module-Validation-Core', 0),
(101, 26, 'Melhorar código sem alterar comportamento', 1),
(102, 26, 'Corrigir bugs', 0),
(103, 26, 'Adicionar novas funcionalidades', 0),
(104, 26, 'Reescrever todo o código', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `tentativas_login`
--

CREATE TABLE `tentativas_login` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `data_tentativa` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices para tabela `jogadores`
--
ALTER TABLE `jogadores`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jogador_id` (`jogador_id`),
  ADD KEY `idx_data_inicio` (`data_inicio`),
  ADD KEY `idx_pontuacao` (`pontuacao`);

--
-- Índices para tabela `partidas_respostas`
--
ALTER TABLE `partidas_respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partida_id` (`partida_id`),
  ADD KEY `pergunta_id` (`pergunta_id`),
  ADD KEY `resposta_id` (`resposta_id`);

--
-- Índices para tabela `perguntas`
--
ALTER TABLE `perguntas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `respostas`
--
ALTER TABLE `respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pergunta_id` (`pergunta_id`);

--
-- Índices para tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `jogadores`
--
ALTER TABLE `jogadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `partidas_respostas`
--
ALTER TABLE `partidas_respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perguntas`
--
ALTER TABLE `perguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `respostas`
--
ALTER TABLE `respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `partidas`
--
ALTER TABLE `partidas`
  ADD CONSTRAINT `partidas_ibfk_1` FOREIGN KEY (`jogador_id`) REFERENCES `jogadores` (`id`);

--
-- Limitadores para a tabela `partidas_respostas`
--
ALTER TABLE `partidas_respostas`
  ADD CONSTRAINT `partidas_respostas_ibfk_1` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidas_respostas_ibfk_2` FOREIGN KEY (`pergunta_id`) REFERENCES `perguntas` (`id`),
  ADD CONSTRAINT `partidas_respostas_ibfk_3` FOREIGN KEY (`resposta_id`) REFERENCES `respostas` (`id`);

--
-- Limitadores para a tabela `respostas`
--
ALTER TABLE `respostas`
  ADD CONSTRAINT `respostas_ibfk_1` FOREIGN KEY (`pergunta_id`) REFERENCES `perguntas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
