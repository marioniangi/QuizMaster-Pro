-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24-Nov-2024 às 19:58
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
(3, 'Mário Niangi', 'niangi@quiz.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2024-11-20 07:47:30');

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
  `data_ultimo_jogo` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `ultima_partida` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `jogadores`
--

INSERT INTO `jogadores` (`id`, `nome`, `pontuacao_total`, `melhor_pontuacao`, `jogos_completados`, `data_ultimo_jogo`, `status`, `data_cadastro`, `ultima_partida`) VALUES
(2, 'Mario', 426, 105, 8, '2024-11-18 01:43:02', 'ativo', '2024-11-18 00:42:13', NULL),
(3, 'Niangi', 90, 50, 3, '2024-11-18 01:38:06', 'ativo', '2024-11-18 01:40:14', NULL),
(4, 'Mariana', 40, 40, 1, '2024-11-18 01:35:25', 'ativo', '2024-11-18 02:34:43', NULL),
(5, 'Ana', 0, 0, 0, '2024-11-18 01:41:51', 'ativo', '2024-11-18 02:41:51', NULL),
(6, 'Roberto', 110, 40, 3, '2024-11-19 23:01:39', 'ativo', '2024-11-18 06:21:34', NULL),
(7, 'Marta', 15, 15, 1, '2024-11-18 05:47:52', 'ativo', '2024-11-18 06:47:28', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `tipo` enum('info','erro','alerta','sucesso') NOT NULL,
  `mensagem` text NOT NULL,
  `dados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados`)),
  `data_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `tempo_total` int(11) DEFAULT NULL,
  `status` enum('em_andamento','finalizada','cancelada') NOT NULL DEFAULT 'em_andamento',
  `data_partida` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `data_fim` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `partidas`
--

INSERT INTO `partidas` (`id`, `jogador_id`, `modo`, `pontuacao`, `acertos`, `total_perguntas`, `tempo_total`, `status`, `data_partida`, `data_inicio`, `data_fim`) VALUES
(5, 2, 'desafio', 40, 2, 5, NULL, 'finalizada', '2024-11-18 00:31:09', '2024-11-18 01:31:09', '2024-11-18 01:31:09'),
(6, 2, 'classico', 105, 8, 10, NULL, 'finalizada', '2024-11-18 00:35:47', '2024-11-18 01:35:47', '2024-11-18 01:35:47'),
(13, 2, 'tempo', 78, 3, 15, NULL, 'finalizada', '2024-11-18 00:51:12', '2024-11-18 01:51:12', '2024-11-18 01:51:12'),
(14, 3, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 00:59:06', '2024-11-18 01:59:06', NULL),
(15, 3, 'classico', 50, 5, 5, NULL, 'finalizada', '2024-11-18 00:59:56', '2024-11-18 01:59:56', '2024-11-18 01:59:56'),
(16, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:00:17', '2024-11-18 02:00:17', NULL),
(17, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:01:08', '2024-11-18 02:01:08', NULL),
(18, 2, 'desafio', 20, 1, 5, NULL, 'finalizada', '2024-11-18 01:04:21', '2024-11-18 02:04:21', '2024-11-18 02:04:21'),
(19, 2, 'tempo', 103, 4, 15, NULL, 'finalizada', '2024-11-18 01:05:17', '2024-11-18 02:05:17', '2024-11-18 02:05:17'),
(22, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:11:03', '2024-11-18 02:11:03', NULL),
(23, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:12:57', '2024-11-18 02:12:57', NULL),
(24, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:14:06', '2024-11-18 02:14:06', NULL),
(25, 3, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:14:16', '2024-11-18 02:14:16', NULL),
(26, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:15:30', '2024-11-18 02:15:30', NULL),
(27, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:18:11', '2024-11-18 02:18:11', NULL),
(28, 2, 'desafio', 20, 1, 5, NULL, 'finalizada', '2024-11-18 01:19:04', '2024-11-18 02:19:04', '2024-11-18 02:19:04'),
(30, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:23:23', '2024-11-18 02:23:23', NULL),
(33, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:32:01', '2024-11-18 02:32:01', NULL),
(34, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:33:01', '2024-11-18 02:33:01', NULL),
(35, 3, 'desafio', 40, 2, 5, NULL, 'finalizada', '2024-11-18 01:33:22', '2024-11-18 02:33:22', '2024-11-18 02:33:22'),
(36, 2, 'desafio', 40, 2, 5, NULL, 'finalizada', '2024-11-18 01:33:58', '2024-11-18 02:33:58', '2024-11-18 02:33:58'),
(37, 4, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:34:55', '2024-11-18 02:34:55', NULL),
(38, 4, 'desafio', 40, 2, 5, NULL, 'finalizada', '2024-11-18 01:35:25', '2024-11-18 02:35:25', '2024-11-18 02:35:25'),
(39, 3, 'tempo', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:37:53', '2024-11-18 02:37:53', NULL),
(40, 3, 'tempo', 0, 0, 5, NULL, 'finalizada', '2024-11-18 01:38:06', '2024-11-18 02:38:06', '2024-11-18 02:38:06'),
(41, 3, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:38:15', '2024-11-18 02:38:15', NULL),
(42, 3, 'tempo', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:38:20', '2024-11-18 02:38:20', NULL),
(43, 3, 'tempo', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:39:26', '2024-11-18 02:39:26', NULL),
(44, 3, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:39:54', '2024-11-18 02:39:54', NULL),
(45, 3, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:40:19', '2024-11-18 02:40:19', NULL),
(46, 4, 'tempo', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 01:40:41', '2024-11-18 02:40:41', NULL),
(49, 2, 'desafio', 20, 1, 5, NULL, 'finalizada', '2024-11-18 01:43:02', '2024-11-18 02:43:02', '2024-11-18 02:43:02'),
(55, 6, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:22:14', '2024-11-18 06:22:14', NULL),
(56, 6, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:22:27', '2024-11-18 06:22:27', NULL),
(57, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:22:39', '2024-11-18 06:22:39', NULL),
(58, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:22:50', '2024-11-18 06:22:50', NULL),
(61, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:23:27', '2024-11-18 06:23:27', NULL),
(64, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:25:35', '2024-11-18 06:25:35', NULL),
(65, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:25:47', '2024-11-18 06:25:47', NULL),
(70, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:28:11', '2024-11-18 06:28:11', NULL),
(71, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:28:59', '2024-11-18 06:28:59', NULL),
(72, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:29:28', '2024-11-18 06:29:28', NULL),
(73, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:30:15', '2024-11-18 06:30:15', NULL),
(74, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:31:15', '2024-11-18 06:31:15', NULL),
(75, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:32:18', '2024-11-18 06:32:18', NULL),
(76, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:33:00', '2024-11-18 06:33:00', NULL),
(77, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:33:26', '2024-11-18 06:33:26', NULL),
(81, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:36:23', '2024-11-18 06:36:23', NULL),
(82, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:37:12', '2024-11-18 06:37:12', NULL),
(83, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:39:53', '2024-11-18 06:39:53', NULL),
(84, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:39:59', '2024-11-18 06:39:59', NULL),
(85, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:40:29', '2024-11-18 06:40:29', NULL),
(86, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:41:58', '2024-11-18 06:41:58', NULL),
(87, 6, 'desafio', 40, 2, 5, NULL, 'finalizada', '2024-11-18 05:42:15', '2024-11-18 06:42:15', '2024-11-18 06:42:15'),
(88, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:43:33', '2024-11-18 06:43:33', NULL),
(89, 6, 'desafio', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:44:16', '2024-11-18 06:44:16', NULL),
(90, 6, 'desafio', 40, 2, 5, NULL, 'finalizada', '2024-11-18 05:44:32', '2024-11-18 06:44:32', '2024-11-18 06:44:32'),
(91, 7, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-18 05:47:30', '2024-11-18 06:47:30', NULL),
(92, 7, 'classico', 15, 1, 5, NULL, 'finalizada', '2024-11-18 05:47:52', '2024-11-18 06:47:52', '2024-11-18 06:47:52'),
(93, 6, 'classico', 0, 0, 0, NULL, 'em_andamento', '2024-11-19 23:00:55', '2024-11-20 00:00:55', NULL),
(94, 6, 'classico', 30, 2, 5, NULL, 'finalizada', '2024-11-19 23:01:39', '2024-11-20 00:01:39', '2024-11-20 00:01:39');

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
-- Estrutura da tabela `perguntas_excluidas`
--

CREATE TABLE `perguntas_excluidas` (
  `id` int(11) NOT NULL,
  `pergunta_id` int(11) NOT NULL,
  `pergunta` text NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `dificuldade` enum('facil','medio','dificil') NOT NULL,
  `pontos` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `data_criacao` datetime NOT NULL,
  `data_exclusao` datetime NOT NULL,
  `motivo_exclusao` text DEFAULT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(109, 18, 'Faz escolha localmente ótima em cada etapa', 1),
(110, 18, 'Usa muita memória', 0),
(111, 18, 'É sempre o mais rápido', 0),
(112, 18, 'Nunca funciona', 0),
(149, 22, 'Unified Modeling Language', 1),
(150, 22, 'Universal Making Logic', 0),
(151, 22, 'Unit Management Level', 0),
(152, 22, 'User Machine Learning', 0),
(161, 26, 'Melhorar código sem alterar comportamento', 1),
(162, 26, 'Corrigir bugs', 0),
(163, 26, 'Adicionar novas funcionalidades', 0),
(164, 26, 'Reescrever todo o código', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `respostas_excluidas`
--

CREATE TABLE `respostas_excluidas` (
  `id` int(11) NOT NULL,
  `resposta_id` int(11) NOT NULL,
  `pergunta_excluida_id` int(11) NOT NULL,
  `resposta` text NOT NULL,
  `correta` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `respostas_jogador`
--

CREATE TABLE `respostas_jogador` (
  `id` int(11) NOT NULL,
  `partida_id` int(11) NOT NULL,
  `jogador_id` int(11) NOT NULL,
  `pergunta_id` int(11) NOT NULL,
  `resposta_id` int(11) NOT NULL,
  `tempo_resposta` int(11) NOT NULL,
  `correta` tinyint(1) NOT NULL DEFAULT 0,
  `data_resposta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `respostas_jogador`
--

INSERT INTO `respostas_jogador` (`id`, `partida_id`, `jogador_id`, `pergunta_id`, `resposta_id`, `tempo_resposta`, `correta`, `data_resposta`) VALUES
(1, 34, 3, 8, 31, 0, 0, '2024-11-18 02:33:02'),
(2, 34, 3, 14, 53, 6, 1, '2024-11-18 02:33:08'),
(3, 34, 3, 20, 79, 10, 0, '2024-11-18 02:33:12'),
(4, 34, 3, 13, 49, 15, 1, '2024-11-18 02:33:17'),
(5, 34, 3, 26, 103, 18, 0, '2024-11-18 02:33:20'),
(6, 37, 4, 8, 31, 2, 0, '2024-11-18 02:34:58'),
(7, 37, 4, 20, 80, 7, 0, '2024-11-18 02:35:03'),
(8, 37, 4, 25, 99, 11, 0, '2024-11-18 02:35:07'),
(9, 37, 4, 26, 101, 27, 1, '2024-11-18 02:35:23'),
(10, 37, 4, 13, 49, 28, 1, '2024-11-18 02:35:24'),
(11, 39, 3, 23, 90, 2, 0, '2024-11-18 02:37:56'),
(12, 39, 3, 11, 44, 6, 0, '2024-11-18 02:37:59'),
(13, 39, 3, 6, 23, 7, 0, '2024-11-18 02:38:01'),
(14, 39, 3, 5, 19, 11, 0, '2024-11-18 02:38:04'),
(15, 39, 3, 12, 48, 12, 0, '2024-11-18 02:38:06'),
(16, 44, 3, 13, 49, 0, 1, '2024-11-18 02:39:54'),
(17, 44, 3, 19, 74, 3, 0, '2024-11-18 02:39:58'),
(18, 45, 3, 17, 65, 1, 1, '2024-11-18 02:40:20'),
(19, 46, 4, 12, 48, 1, 0, '2024-11-18 02:40:42'),
(20, 55, 6, 18, 70, 1, 0, '2024-11-18 06:22:16'),
(21, 56, 6, 18, 71, 0, 0, '2024-11-18 06:22:28'),
(22, 57, 6, 7, 28, 0, 0, '2024-11-18 06:22:40'),
(23, 58, 6, 20, 77, 0, 1, '2024-11-18 06:22:51'),
(24, 61, 6, 19, 75, 0, 0, '2024-11-18 06:23:28'),
(25, 64, 6, 8, 30, 1, 0, '2024-11-18 06:25:36'),
(26, 70, 6, 7, 27, 0, 0, '2024-11-18 06:28:11'),
(27, 71, 6, 14, 56, 0, 0, '2024-11-18 06:29:00'),
(28, 72, 6, 26, 103, 1, 0, '2024-11-18 06:29:29'),
(29, 73, 6, 7, 26, 0, 0, '2024-11-18 06:30:16'),
(30, 74, 6, 25, 98, 0, 0, '2024-11-18 06:31:16'),
(31, 75, 6, 14, 56, 0, 0, '2024-11-18 06:32:19'),
(32, 76, 6, 20, 78, 0, 0, '2024-11-18 06:33:01'),
(33, 77, 6, 7, 26, 0, 0, '2024-11-18 06:33:27'),
(34, 77, 6, 14, 53, 2, 1, '2024-11-18 06:33:29'),
(35, 77, 6, 8, 32, 4, 0, '2024-11-18 06:33:31'),
(36, 81, 6, 13, 49, 0, 1, '2024-11-18 06:36:24'),
(37, 81, 6, 13, 49, 0, 1, '2024-11-18 06:36:25'),
(38, 81, 6, 7, 27, 7, 0, '2024-11-18 06:36:32'),
(39, 82, 6, 8, 32, 0, 0, '2024-11-18 06:37:13'),
(40, 82, 6, 7, 26, 4, 0, '2024-11-18 06:37:17'),
(41, 82, 6, 13, 52, 9, 0, '2024-11-18 06:37:22'),
(42, 85, 6, 8, 31, 1, 0, '2024-11-18 06:40:30'),
(43, 86, 6, 8, 32, 1, 0, '2024-11-18 06:41:59'),
(44, 86, 6, 13, 52, 4, 0, '2024-11-18 06:42:03'),
(45, 86, 6, 19, 73, 6, 1, '2024-11-18 06:42:05'),
(46, 86, 6, 7, 25, 13, 1, '2024-11-18 06:42:11'),
(47, 86, 6, 14, 55, 15, 0, '2024-11-18 06:42:13'),
(48, 88, 6, 20, 80, 0, 0, '2024-11-18 06:43:34'),
(49, 89, 6, 14, 53, 1, 1, '2024-11-18 06:44:17'),
(50, 89, 6, 20, 80, 6, 0, '2024-11-18 06:44:23'),
(51, 89, 6, 26, 101, 10, 1, '2024-11-18 06:44:26'),
(52, 89, 6, 8, 31, 12, 0, '2024-11-18 06:44:29'),
(53, 89, 6, 7, 26, 15, 0, '2024-11-18 06:44:31'),
(54, 91, 7, 4, 13, 0, 1, '2024-11-18 06:47:31'),
(55, 91, 7, 6, 23, 11, 0, '2024-11-18 06:47:41'),
(56, 91, 7, 24, 95, 14, 0, '2024-11-18 06:47:44'),
(57, 91, 7, 17, 68, 17, 0, '2024-11-18 06:47:47'),
(58, 91, 7, 23, 92, 19, 0, '2024-11-18 06:47:50'),
(59, 93, 6, 18, 109, 0, 1, '2024-11-20 00:00:56'),
(60, 93, 6, 12, 47, 10, 0, '2024-11-20 00:01:06'),
(61, 93, 6, 6, 24, 13, 0, '2024-11-20 00:01:09'),
(62, 93, 6, 5, 17, 24, 1, '2024-11-20 00:01:20'),
(63, 93, 6, 24, 96, 39, 0, '2024-11-20 00:01:35');

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
-- Índices para tabela `logs`
--
ALTER TABLE `logs`
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
-- Índices para tabela `perguntas_excluidas`
--
ALTER TABLE `perguntas_excluidas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `respostas`
--
ALTER TABLE `respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pergunta_id` (`pergunta_id`);

--
-- Índices para tabela `respostas_excluidas`
--
ALTER TABLE `respostas_excluidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pergunta_excluida_id` (`pergunta_excluida_id`);

--
-- Índices para tabela `respostas_jogador`
--
ALTER TABLE `respostas_jogador`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partida_id` (`partida_id`),
  ADD KEY `jogador_id` (`jogador_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `jogadores`
--
ALTER TABLE `jogadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT de tabela `partidas_respostas`
--
ALTER TABLE `partidas_respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perguntas`
--
ALTER TABLE `perguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `perguntas_excluidas`
--
ALTER TABLE `perguntas_excluidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `respostas`
--
ALTER TABLE `respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT de tabela `respostas_excluidas`
--
ALTER TABLE `respostas_excluidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `respostas_jogador`
--
ALTER TABLE `respostas_jogador`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

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

--
-- Limitadores para a tabela `respostas_excluidas`
--
ALTER TABLE `respostas_excluidas`
  ADD CONSTRAINT `respostas_excluidas_ibfk_1` FOREIGN KEY (`pergunta_excluida_id`) REFERENCES `perguntas_excluidas` (`id`);

--
-- Limitadores para a tabela `respostas_jogador`
--
ALTER TABLE `respostas_jogador`
  ADD CONSTRAINT `respostas_jogador_ibfk_1` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`),
  ADD CONSTRAINT `respostas_jogador_ibfk_2` FOREIGN KEY (`jogador_id`) REFERENCES `jogadores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
