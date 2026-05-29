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
-- Banco de dados: `sistema`
--

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

--
-- Despejando dados para a tabela `roupas`
--

INSERT INTO `roupas` (`id`, `titulo`, `tipo`, `tamanho`, `sexo`, `estado`, `local_doacao`, `foto_path`, `pausado`, `id_usuario`, `criado_em`) VALUES
(4, 'blusa de frio', 'nsei', 'M', 'Masculino', 'Semi-Novo', 'curitiba', 'assets/uploads/roupas/c073bc1110fd09c544ef467f3c91e48f.png', 1, 1, '2026-05-20 23:01:16'),
(5, 'nase', 'camisa', 'M', 'Masculino', 'Semi-Novo', 'colombo', 'assets/uploads/roupas/18f099f5b7375add54b701951fea6ebf.png', 1, 21, '2026-05-20 23:07:11'),
(6, 'ola', 'cueca', 'P', 'Unissex', 'Novo', 'sao paulo', 'assets/uploads/roupas/56768606d55b6abd5adc3098c7091cbb.png', 1, 21, '2026-05-20 23:12:52'),
(7, 'blusa de frio', 'blusa', 'P', 'Unissex', 'Semi-Novo', 'Curitiba', 'assets/uploads/roupas/d90ff168c3b586f365e1b8b24c64b5c4.webp', 0, 1, '2026-05-21 21:48:34'),
(8, 'Saia', 'saia', 'M', 'Feminino', 'Semi-Novo', 'Curitiba', 'assets/uploads/roupas/0af271bbca34970464152bc4ef1b95e9.webp', 0, 1, '2026-05-21 21:48:57'),
(9, 'Calca Cargo', 'Calca', 'G', 'Masculino', 'Novo', 'Curitiba', 'assets/uploads/roupas/6096deab3835457cc7834cbcf003911e.jpg', 0, 1, '2026-05-21 21:49:32'),
(11, 'calca masculina', 'calca', 'GG', 'Masculino', 'Novo', 'Curitiba', 'assets/uploads/roupas/7557bb8932da544db90b31608e9eedb6.jpg', 1, 1, '2026-05-21 21:51:13');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_roupas_ativas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_roupas_ativas` (
`id` int(11)
,`titulo` varchar(120)
,`tipo` varchar(100)
,`tamanho` varchar(10)
,`sexo` enum('Masculino','Feminino','Unissex')
,`estado` enum('Novo','Semi-Novo','Usado')
,`local_doacao` varchar(180)
,`foto_path` varchar(255)
,`criado_em` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para view `view_roupas_ativas`
--
DROP TABLE IF EXISTS `view_roupas_ativas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_roupas_ativas`  AS SELECT `roupas`.`id` AS `id`, `roupas`.`titulo` AS `titulo`, `roupas`.`tipo` AS `tipo`, `roupas`.`tamanho` AS `tamanho`, `roupas`.`sexo` AS `sexo`, `roupas`.`estado` AS `estado`, `roupas`.`local_doacao` AS `local_doacao`, `roupas`.`foto_path` AS `foto_path`, `roupas`.`criado_em` AS `criado_em` FROM `roupas` WHERE `roupas`.`pausado` = 0 ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `roupas`
--
ALTER TABLE `roupas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `roupas`
--
ALTER TABLE `roupas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;