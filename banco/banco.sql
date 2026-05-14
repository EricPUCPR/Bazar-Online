DROP DATABASE IF EXISTS bazar; 
CREATE DATABASE IF NOT EXISTS bazar DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bazar;

CREATE TABLE `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome` varchar(100) NOT NULL,
    `email` varchar(150) NOT NULL UNIQUE,
    `telefone` varchar(20) NOT NULL,
    `endereco` varchar(200) NOT NULL,
    `data_nascimento` date NOT NULL,
    `senha` varchar(255) NOT NULL,
    `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
    `recuperacao_token` varchar(255) DEFAULT NULL,
    `recuperacao_expira` datetime DEFAULT NULL,
    `email_verificado` tinyint(1) NOT NULL DEFAULT 0,
    `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE `activity_log` (
    `id_log` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` int(11) DEFAULT NULL,
    `descricao` varchar(255) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(512) DEFAULT NULL,
    `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE `roupas` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tipo` varchar(100) NOT NULL,
  `tamanho` varchar(10) DEFAULT NULL,
  `sexo` enum('Masculino','Feminino','Unissex') DEFAULT NULL,
  `estado` enum('Novo','Semi-Novo','Usado') DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  CONSTRAINT `roupas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER $$

-- ============================================================
-- PROCEDURES DE USUÁRIO
-- ============================================================

-- Cria um novo usuário já verificado (chamada: verificar_cadastro.php)
-- O e-mail foi validado pelo código antes da inserção, portanto email_verificado=1.
CREATE PROCEDURE proc_usuario_criar(
    IN p_nome VARCHAR(100),
    IN p_email VARCHAR(150),
    IN p_telefone VARCHAR(20),
    IN p_endereco VARCHAR(200),
    IN p_nascimento DATE,
    IN p_senha VARCHAR(255)
)
SQL SECURITY DEFINER
BEGIN
    IF EXISTS (SELECT 1 FROM usuarios WHERE email = p_email) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Este e-mail já está cadastrado no sistema.';
    ELSE
        INSERT INTO usuarios (nome, email, telefone, endereco, data_nascimento, senha, email_verificado, criado_em)
        VALUES (p_nome, p_email, p_telefone, p_endereco, p_nascimento, p_senha, 1, NOW());
        SELECT LAST_INSERT_ID() AS id, 'Sucesso' AS resultado;
    END IF;
END $$

-- Busca usuário por email para login (chamada: autentica.php)
CREATE PROCEDURE proc_usuario_logar(
    IN p_email VARCHAR(150)
)
SQL SECURITY DEFINER
BEGIN
    SELECT id, nome, email, senha, email_verificado FROM usuarios WHERE email = p_email LIMIT 1;
END $$

-- Retorna dados do perfil do usuário (chamada: PaginaUsuario.php)
CREATE PROCEDURE proc_usuario_perfil(
    IN p_id INT
)
SQL SECURITY DEFINER
BEGIN
    SELECT nome, email, telefone, endereco, data_nascimento FROM usuarios WHERE id = p_id LIMIT 1;
END $$

-- Deleta usuário por id (chamada: PaginaUsuario.php)
CREATE PROCEDURE proc_usuario_deletar(
    IN p_id INT
)
SQL SECURITY DEFINER
BEGIN
    DELETE FROM usuarios WHERE id = p_id;
    SELECT 'Sucesso' AS resultado;
END $$

-- Checa se email já existe como conta ATIVA (chamada: CadastroUsuarios.php)
CREATE PROCEDURE proc_usuario_email_existe(
    IN p_email VARCHAR(150)
)
SQL SECURITY DEFINER
BEGIN
    SELECT id FROM usuarios WHERE email = p_email AND email_verificado = 1 LIMIT 1;
END $$

-- Salva token de recuperação de senha (chamada: Solicitacao.php)
CREATE PROCEDURE proc_usuario_salvar_token_recuperacao(
    IN p_email VARCHAR(150),
    IN p_token VARCHAR(255),
    IN p_expira DATETIME
)
SQL SECURITY DEFINER
BEGIN
    -- Retorna 0 rows se email não existe (Solicitacao.php verifica isso)
    SELECT id FROM usuarios WHERE email = p_email LIMIT 1;

    UPDATE usuarios
    SET recuperacao_token = p_token,
        recuperacao_expira = p_expira
    WHERE email = p_email;
END $$

-- Valida token de recuperação e retorna nome+email (chamada: pagina_recuperacao_de_senha.php)
CREATE PROCEDURE proc_usuario_validar_token_recuperacao(
    IN p_token VARCHAR(255)
)
SQL SECURITY DEFINER
BEGIN
    SELECT email, nome FROM usuarios
    WHERE recuperacao_token = p_token AND recuperacao_expira > NOW()
    LIMIT 1;
END $$

-- Salva nova senha e invalida o token (chamada: pagina_recuperacao_de_senha.php)
CREATE PROCEDURE proc_usuario_salvar_nova_senha(
    IN p_token VARCHAR(255),
    IN p_senha_hash VARCHAR(255)
)
SQL SECURITY DEFINER
BEGIN
    UPDATE usuarios
    SET senha = p_senha_hash,
        recuperacao_token = NULL,
        recuperacao_expira = NULL
    WHERE recuperacao_token = p_token;
    SELECT ROW_COUNT() AS afetadas;
END $$

-- Cria roupa (chamada: CadastroRoupas.php)
CREATE PROCEDURE proc_roupa_criar(
    IN p_tipo VARCHAR(100),
    IN p_tamanho VARCHAR(10),
    IN p_sexo ENUM('Masculino','Feminino','Unissex'),
    IN p_estado ENUM('Novo','Semi-Novo','Usado'),
    IN p_usuario_dono INT
)
SQL SECURITY DEFINER
BEGIN
    INSERT INTO roupas (tipo, tamanho, sexo, estado, id_usuario)
    VALUES (p_tipo, p_tamanho, p_sexo, p_estado, p_usuario_dono);
    SELECT 'Sucesso' AS resultado;
END $$

-- Registra um log de atividade (chamada: Vários arquivos PHP)
-- p_id_usuario pode ser NULL para eventos sem usuário autenticado (ex: falha de login)
CREATE PROCEDURE proc_log_atividade(
    IN p_id_usuario INT,
    IN p_descricao  VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent VARCHAR(512)
)
SQL SECURITY DEFINER
BEGIN
    INSERT INTO activity_log (id_usuario, descricao, ip_address, user_agent, data_hora)
    VALUES (p_id_usuario, p_descricao, p_ip_address, p_user_agent, NOW());
END $$
DELIMITER ;

-- A criação de usuários agora é dinâmica e ocorre nos scripts banco.sh / banco.ps1 baseando-se no .env

DELIMITER $$

-- Lista todos os logs de atividade (chamada: admin/pegadas.php)
CREATE PROCEDURE proc_log_listar()
SQL SECURITY DEFINER
BEGIN
    SELECT
        al.id_log,
        al.id_usuario,
        COALESCE(u.nome, '—') AS nome_usuario,
        COALESCE(u.email, '—') AS email_usuario,
        al.descricao,
        al.ip_address,
        al.user_agent,
        al.data_hora
    FROM activity_log al
    LEFT JOIN usuarios u ON al.id_usuario = u.id
    ORDER BY al.data_hora DESC;
END $$

DELIMITER ;
