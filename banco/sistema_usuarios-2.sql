-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 22/05/2026 às 03:18
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
(19, 8, 'marco', NULL, 'Logout', 'Usuário encerrou a sessão.', '2026-05-22 00:23:06'),
(20, 22, 'André Matos', 'andre.dsmat2019@gmail.com', 'Criação de conta', 'Usuário iniciou cadastro e validação de e-mail.', '2026-05-22 00:23:54'),
(21, 22, NULL, NULL, 'Validação de e-mail', 'Usuário validou o e-mail da conta.', '2026-05-22 00:24:08'),
(22, 22, 'André Matos', 'andre.dsmat2019@gmail.com', 'Login', 'Login de usuário validado por código de e-mail.', '2026-05-22 00:24:42'),
(23, 22, 'André Matos', 'andre.dsmat2019@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-22 00:25:01'),
(24, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Login admin', 'Login admin validado por código de e-mail.', '2026-05-22 00:25:24'),
(25, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Exclusão de anúncio', 'Admin excluiu um anúncio de roupa.', '2026-05-22 00:25:30'),
(26, 1, 'marco antonio', 'marcomarkowicz@gmail.com', 'Logout', 'Usuário encerrou a sessão.', '2026-05-22 00:34:04');

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
(1, 'marco antonio', 'marcomarkowicz@gmail.com', NULL, 'Nao Sei', '2005-06-07', '$2y$10$6PjzNG/0n7K7QWV9uv5PretOoqLoDMrhl0H2YDoNTESm8Fyr2nYJO', '2026-05-20 21:11:29', NULL, NULL, 1, NULL, NULL, 1, '8081602036', 'Qual o nome do seu primeiro animal?', '$2y$10$RvyTyDTM2WnaALIE5beoW.ZhjOby3DydKl.Y.3VxBwlaShEKJL8tW'),
(22, 'André Matos', 'andre.dsmat2019@gmail.com', '(41) 98775-8630', 'Rua Nao Sei', '2007-11-16', '$2y$10$xG7Q0q.FPVdK/T/nJdDFhu8/CFzz7dh5mKPBMIvuAmZjMaXp3kUjG', '2026-05-22 00:23:54', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `roupas`
--
ALTER TABLE `roupas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
