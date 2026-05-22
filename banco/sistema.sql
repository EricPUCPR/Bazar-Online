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
(3, 'Calca', 'Calca', 'G', 'Masculino', 'Novo', 'parana', 'assets/uploads/roupas/86f72b65a92d0217eda51084a4d3d3a5.jpg', 0, 1, '2026-05-22 00:25:54'),
(4, 'Calca', 'Calca', 'G', 'Masculino', 'Novo', 'São Paulo', 'assets/uploads/roupas/c1a8397ccfa7e2a2e62dcf4622fd5e85.jpg', 0, 1, '2026-05-22 00:26:20'),
(5, '01', '01', 'PP', 'Masculino', 'Novo', '01', 'assets/uploads/roupas/865359fc127b3968680b69f1255c2e94.webp', 0, 1, '2026-05-22 00:26:45'),
(6, 'Saia', 'Saia', 'XG', 'Masculino', 'Novo', 'Centro', 'assets/uploads/roupas/20aa4709ed5ec6dac732c9bc7b48d3b0.webp', 0, 1, '2026-05-22 00:27:03');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
