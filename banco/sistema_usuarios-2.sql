-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 22/05/2026 às 01:31
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_usuarios`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `acao` varchar(80) NOT NULL,
  `detalhes` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `usuario_id`, `nome`, `email`, `acao`, `detalhes`, `criado_em`) VALUES
(1, 1, 'Ana ', 'analing75@gmail.com', 'Finalização de doação', 'Usuário finalizou uma doação e compartilhou dados de contato.', '2026-05-13 16:31:54'),
(2, 1, 'Ana ', NULL, 'Logout', 'Usuário encerrou a sessão.', '2026-05-13 17:41:15'),
(3, 20, 'André Matos', 'andre.dsmat2019@gmail.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-20 16:23:57'),
(4, 20, NULL, NULL, 'Validação de e-mail', 'Usuário validou o e-mail da conta.', '2026-05-20 16:24:37'),
(5, 20, 'André Matos', 'andre.dsmat2019@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-20 16:25:10'),
(6, 1, 'André Matos', 'andre.dsmat2019@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-20 17:42:35'),
(7, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-20 21:11:29'),
(8, 21, NULL, NULL, 'Validação de e-mail', 'Usuário validou o e-mail da conta.', '2026-05-20 21:11:43'),
(9, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-20 21:12:31'),
(10, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-20 21:14:17'),
(11, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Exclusão de anúncio', 'Admin excluiu um anúncio de roupa.', '2026-05-20 21:25:52'),
(12, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 21:54:40'),
(13, 20, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-20 21:57:27'),
(14, 20, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 22:12:33'),
(15, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-20 22:16:42'),
(16, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 22:26:27'),
(17, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-20 22:26:53'),
(18, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 22:48:42'),
(19, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Exclusão de anúncio', 'Admin excluiu um anúncio de roupa.', '2026-05-20 22:59:32'),
(20, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Exclusão de anúncio', 'Admin excluiu um anúncio de roupa.', '2026-05-20 23:00:27'),
(21, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Finalização de doação', 'Usuário finalizou uma doação e compartilhou dados de contato.', '2026-05-20 23:02:24'),
(22, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 23:03:13'),
(23, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-20 23:05:35'),
(24, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 23:07:33'),
(25, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-20 23:10:57'),
(26, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Finalização de doação', 'Usuário finalizou uma doação e compartilhou dados de contato.', '2026-05-20 23:13:10'),
(27, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-20 23:14:06'),
(28, 21, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-20 23:24:33'),
(29, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 15:37:57'),
(30, 22, 'marcoantonio', 'Marco.a.markowicz@gmail.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-21 18:53:25'),
(31, 22, NULL, NULL, 'Validação de e-mail', 'Usuário validou o e-mail da conta.', '2026-05-21 18:54:03'),
(32, 22, 'marcoantonio', 'Marco.a.markowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-21 18:55:44'),
(33, 22, 'marcoantonio', 'Marco.a.markowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 18:56:54'),
(34, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-21 20:22:15'),
(35, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 20:23:19'),
(36, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:10:04'),
(37, 23, 'André Matos', 'andre.dsmat2019@gmail.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-21 21:11:58'),
(38, 23, NULL, NULL, 'Validação de e-mail', 'Usuário validou o e-mail da conta.', '2026-05-21 21:12:15'),
(39, 23, 'André Matos', 'andre.dsmat2019@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-21 21:12:42'),
(40, 23, 'André Matos', 'andre.dsmat2019@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:16:43'),
(41, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-21 21:19:46'),
(42, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:19:50'),
(43, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:45:25'),
(44, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:47:05'),
(45, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Exclusão de anúncio', 'Admin excluiu um anúncio de roupa.', '2026-05-21 21:50:48'),
(46, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:52:59'),
(47, 23, 'André Matos', 'andre.dsmat2019@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-21 21:53:43'),
(48, 23, 'André Matos', 'andre.dsmat2019@gmail.com', 'Finalização de doação', 'Usuário finalizou uma doação e compartilhou dados de contato.', '2026-05-21 21:54:06'),
(49, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-21 21:55:13'),
(50, 22, NULL, NULL, 'Remoção de usuário', 'Admin removeu um usuário pelo painel.', '2026-05-21 21:55:30'),
(51, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 21:55:34'),
(52, 24, 'marcoantoniooo', 'marco.a.markowicz@gmial.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-21 21:56:35'),
(53, 25, 'ana clara', 'marco.a.markowicz@gmail.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-21 21:59:12'),
(54, 25, NULL, NULL, 'Validação de e-mail', 'Usuário validou o e-mail da conta.', '2026-05-21 21:59:42'),
(55, 25, 'ana clara', 'marco.a.markowicz@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-21 22:01:57'),
(56, 25, 'ana clara', 'marco.a.markowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-21 22:09:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `roupas`
--

CREATE TABLE `roupas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(120) DEFAULT NULL,
  `tipo` varchar(100) NOT NULL,
  `tamanho` varchar(10) DEFAULT NULL,
  `sexo` enum('Masculino','Feminino','Unissex') DEFAULT NULL,
  `estado` enum('Novo','Semi-Novo','Usado') DEFAULT NULL,
  `local_doacao` varchar(180) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `pausado` tinyint(1) NOT NULL DEFAULT 0,
  `id_usuario` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `recuperacao_token` varchar(255) DEFAULT NULL,
  `recuperacao_expira` datetime DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `confirmacao_token` varchar(128) DEFAULT NULL,
  `confirmacao_expira` datetime DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `telegram_chat_id` varchar(30) DEFAULT NULL,
  `pergunta_seguranca` varchar(255) DEFAULT NULL,
  `resposta_seguranca_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `endereco`, `data_nascimento`, `senha`, `criado_em`, `recuperacao_token`, `recuperacao_expira`, `email_verificado`, `confirmacao_token`, `confirmacao_expira`, `is_admin`, `telegram_chat_id`, `pergunta_seguranca`, `resposta_seguranca_hash`) VALUES
(1, 'marco antonio', 'marcomarkowicz@gmail.com', NULL, 'Nao Sei', '2005-06-07', '$2y$10$vIMZusCuLiyHFcUZfW7ZMu2CXHIYt3DetZh6oJF8c6l077CJkN.2C', '2026-05-20 21:11:29', NULL, NULL, 1, NULL, NULL, 1, '8081602036', 'Qual o nome do seu primeiro animal?', '$2y$10$x17b3mqOLrmJO5LKfuDR1e6M/b7mJUADlVl/MvVddn8px0zoJjFcq'),
(23, 'André Matos', 'andre.dsmat2019@gmail.com', '(41) 98775-8630', 'Rua Nao Sei', '2007-11-16', '$2y$10$XGdWuG0SGopezBRoMIEYcORrodf/K6Ss17J/En5.hfp2YgmGoyI4.', '2026-05-21 21:11:58', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, NULL),
(24, 'marcoantoniooo', 'marco.a.markowicz@gmial.com', '(41) 92894-0303', 'nsei', '2005-06-07', '$2y$10$nQ3rKSfG8nuHBj6FhxlZqOfmkr50tamX9GOI8bVIwecxodvSn7Wpm', '2026-05-21 21:56:35', NULL, NULL, 0, '499cb8a5f862735c398b384bc228a4fc6325a83804fe74744b7ae80d6d639d44', '2026-05-22 23:56:35', 0, NULL, NULL, NULL),
(25, 'ana clara', 'marco.a.markowicz@gmail.com', '(98) 71239-7120', 'nhdiujaijoiwj1@A', '2008-05-08', '$2y$10$zlXGIOwpVXAFSCu8YdPYNeqzVYn5M.alfN6iJ5aPWJF2zold7VFd2', '2026-05-21 21:59:12', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_logs_recentes`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_logs_recentes` (
`id` int(11)
,`usuario_id` int(11)
,`nome` varchar(100)
,`email` varchar(150)
,`acao` varchar(80)
,`detalhes` varchar(255)
,`criado_em` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_usuarios_admin`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_usuarios_admin` (
`id` int(11)
,`nome` varchar(100)
,`email` varchar(150)
,`is_admin` tinyint(1)
);

-- --------------------------------------------------------

--
-- Estrutura para view `view_logs_recentes`
--
DROP TABLE IF EXISTS `view_logs_recentes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_logs_recentes`  AS SELECT `logs_sistema`.`id` AS `id`, `logs_sistema`.`usuario_id` AS `usuario_id`, `logs_sistema`.`nome` AS `nome`, `logs_sistema`.`email` AS `email`, `logs_sistema`.`acao` AS `acao`, `logs_sistema`.`detalhes` AS `detalhes`, `logs_sistema`.`criado_em` AS `criado_em` FROM `logs_sistema` ORDER BY `logs_sistema`.`criado_em` DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `view_usuarios_admin`
--
DROP TABLE IF EXISTS `view_usuarios_admin`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_usuarios_admin`  AS SELECT `usuarios`.`id` AS `id`, `usuarios`.`nome` AS `nome`, `usuarios`.`email` AS `email`, `usuarios`.`is_admin` AS `is_admin` FROM `usuarios` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `roupas`
--
ALTER TABLE `roupas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `roupas`
--
ALTER TABLE `roupas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;