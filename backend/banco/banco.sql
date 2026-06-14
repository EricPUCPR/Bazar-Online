-- =============================================================
--  Bazar Online — Schema único
--  Banco: bazar
--  Executado por: banco.sh / banco.ps1
--
--  Tabelas:
--    usuarios       — contas de usuários e admins
--    logs_sistema   — auditoria de eventos
--    roupas         — anúncios de doação
--
--  Execute com um usuário root / admin do MySQL.
--  Os scripts de automação gerenciam o usuário da aplicação.
-- =============================================================

CREATE DATABASE IF NOT EXISTS `bazar`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `bazar`;

-- ── Tabela de usuários ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`                      INT            NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome`                    VARCHAR(100)   NOT NULL,
    `email`                   VARCHAR(150)   NOT NULL UNIQUE,
    `telefone`                VARCHAR(20)    DEFAULT NULL,
    `endereco`                VARCHAR(200)   DEFAULT NULL,
    `data_nascimento`         DATE           DEFAULT NULL,
    `senha`                   VARCHAR(255)   NOT NULL,
    `criado_em`               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Recuperação de senha
    `recuperacao_token`       VARCHAR(255)   DEFAULT NULL,
    `recuperacao_expira`      DATETIME       DEFAULT NULL,

    -- Confirmação de e-mail
    `email_verificado`        TINYINT(1)     NOT NULL DEFAULT 0,
    `confirmacao_token`       VARCHAR(128)   DEFAULT NULL,
    `confirmacao_expira`      DATETIME       DEFAULT NULL,

    -- Controle de acesso
    `is_admin`                TINYINT(1)     NOT NULL DEFAULT 0,

    -- Autenticação admin alternativa
    `telegram_chat_id`        VARCHAR(50)    DEFAULT NULL,
    `pergunta_seguranca`      VARCHAR(255)   DEFAULT NULL,
    `resposta_seguranca_hash` VARCHAR(255)   DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabela de logs do sistema ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `logs_sistema` (
    `id`          INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`  INT           DEFAULT NULL,
    `nome`        VARCHAR(100)  DEFAULT NULL,
    `email`       VARCHAR(150)  DEFAULT NULL,
    `acao`        VARCHAR(80)   NOT NULL DEFAULT '',
    `detalhes`    VARCHAR(255)  DEFAULT NULL,
    `criado_em`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_log_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabela de roupas ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roupas` (
    `id`           INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `titulo`       VARCHAR(120)  DEFAULT NULL,
    `tipo`         VARCHAR(100)  NOT NULL DEFAULT '',
    `tamanho`      VARCHAR(10)   DEFAULT NULL,
    `sexo`         ENUM('Masculino','Feminino','Unissex') DEFAULT NULL,
    `estado`       ENUM('Novo','Semi-Novo','Usado')       DEFAULT NULL,
    `local_doacao` VARCHAR(180)  DEFAULT NULL,
    `foto_path`    VARCHAR(255)  DEFAULT NULL,
    `pausado`      TINYINT(1)    NOT NULL DEFAULT 0,
    `id_usuario`   INT           DEFAULT NULL,
    `criado_em`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_roupa_usuario`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── STORED PROCEDURES (Proteção SQL Injection & Menor Privilégio) ──

DELIMITER //

-- 1. Buscar usuário por e-mail (login, cadastro, recuperar senha)
DROP PROCEDURE IF EXISTS sp_buscar_usuario_por_email //
CREATE PROCEDURE sp_buscar_usuario_por_email(IN p_email VARCHAR(150))
BEGIN
    SELECT id, nome, email, telefone, endereco, data_nascimento, senha, email_verificado, is_admin, telegram_chat_id, pergunta_seguranca, resposta_seguranca_hash
    FROM usuarios
    WHERE email = p_email;
END //

-- 2. Inserir novo usuário (cadastro)
DROP PROCEDURE IF EXISTS sp_inserir_usuario //
CREATE PROCEDURE sp_inserir_usuario(
    IN p_nome VARCHAR(100),
    IN p_email VARCHAR(150),
    IN p_telefone VARCHAR(20),
    IN p_endereco VARCHAR(200),
    IN p_data_nascimento DATE,
    IN p_senha VARCHAR(255),
    IN p_confirmacao_token VARCHAR(128),
    IN p_confirmacao_expira DATETIME
)
BEGIN
    INSERT INTO usuarios (nome, email, telefone, endereco, data_nascimento, senha, email_verificado, confirmacao_token, confirmacao_expira, is_admin)
    VALUES (p_nome, p_email, p_telefone, p_endereco, p_data_nascimento, p_senha, 0, p_confirmacao_token, p_confirmacao_expira, 0);
    SELECT LAST_INSERT_ID() AS id;
END //

-- 3. Atualizar cadastro pendente (cadastro)
DROP PROCEDURE IF EXISTS sp_atualizar_cadastro_pendente //
CREATE PROCEDURE sp_atualizar_cadastro_pendente(
    IN p_id INT,
    IN p_nome VARCHAR(100),
    IN p_telefone VARCHAR(20),
    IN p_endereco VARCHAR(200),
    IN p_data_nascimento DATE,
    IN p_senha VARCHAR(255),
    IN p_confirmacao_token VARCHAR(128),
    IN p_confirmacao_expira DATETIME
)
BEGIN
    UPDATE usuarios
    SET nome = p_nome,
        telefone = p_telefone,
        endereco = p_endereco,
        data_nascimento = p_data_nascimento,
        senha = p_senha,
        confirmacao_token = p_confirmacao_token,
        confirmacao_expira = p_confirmacao_expira
    WHERE id = p_id;
END //

-- 4. Buscar usuário por token de confirmação (confirmar_email)
DROP PROCEDURE IF EXISTS sp_buscar_usuario_por_token_confirmacao //
CREATE PROCEDURE sp_buscar_usuario_por_token_confirmacao(IN p_token VARCHAR(128))
BEGIN
    SELECT id, nome, email FROM usuarios
    WHERE confirmacao_token = p_token AND confirmacao_expira > NOW()
    LIMIT 1;
END //

-- 5. Confirmar e-mail (confirmar_email)
DROP PROCEDURE IF EXISTS sp_confirmar_email //
CREATE PROCEDURE sp_confirmar_email(IN p_id INT)
BEGIN
    UPDATE usuarios
    SET email_verificado = 1,
        confirmacao_token = NULL,
        confirmacao_expira = NULL
    WHERE id = p_id;
    
    -- Primeiro usuário confirmado torna-se admin automaticamente para testes
    IF p_id = 1 THEN
        UPDATE usuarios SET is_admin = 1 WHERE id = 1;
    END IF;
END //

-- 6. Definir token de recuperação (recuperar_senha)
DROP PROCEDURE IF EXISTS sp_definir_token_recuperacao //
CREATE PROCEDURE sp_definir_token_recuperacao(
    IN p_email VARCHAR(150),
    IN p_token VARCHAR(255),
    IN p_expira DATETIME
)
BEGIN
    UPDATE usuarios
    SET recuperacao_token = p_token,
        recuperacao_expira = p_expira
    WHERE email = p_email;
END //

-- 7. Buscar usuário por token de recuperação (recuperar_senha)
DROP PROCEDURE IF EXISTS sp_buscar_usuario_por_token_recuperacao //
CREATE PROCEDURE sp_buscar_usuario_por_token_recuperacao(IN p_token VARCHAR(255))
BEGIN
    SELECT id, nome FROM usuarios
    WHERE recuperacao_token = p_token AND recuperacao_expira > NOW()
    LIMIT 1;
END //

-- 8. Redefinir senha (recuperar_senha)
DROP PROCEDURE IF EXISTS sp_redefinir_senha //
CREATE PROCEDURE sp_redefinir_senha(
    IN p_token VARCHAR(255),
    IN p_senha_hash VARCHAR(255)
)
BEGIN
    UPDATE usuarios
    SET senha = p_senha_hash,
        recuperacao_token = NULL,
        recuperacao_expira = NULL
    WHERE recuperacao_token = p_token;
END //

-- 9. Buscar usuário por ID (profile, admin)
DROP PROCEDURE IF EXISTS sp_buscar_usuario_por_id //
CREATE PROCEDURE sp_buscar_usuario_por_id(IN p_id INT)
BEGIN
    SELECT id, nome, email, telefone, endereco, data_nascimento, telegram_chat_id, pergunta_seguranca, resposta_seguranca_hash, is_admin
    FROM usuarios
    WHERE id = p_id;
END //

-- 10. Atualizar Telegram (profile)
DROP PROCEDURE IF EXISTS sp_atualizar_telegram //
CREATE PROCEDURE sp_atualizar_telegram(IN p_id INT, IN p_telegram_chat_id VARCHAR(50))
BEGIN
    UPDATE usuarios
    SET telegram_chat_id = IF(p_telegram_chat_id = '', NULL, p_telegram_chat_id)
    WHERE id = p_id;
END //

-- 11. Atualizar pergunta de segurança (profile)
DROP PROCEDURE IF EXISTS sp_atualizar_pergunta //
CREATE PROCEDURE sp_atualizar_pergunta(
    IN p_id INT,
    IN p_pergunta VARCHAR(255),
    IN p_resposta_hash VARCHAR(255)
)
BEGIN
    UPDATE usuarios
    SET pergunta_seguranca = p_pergunta,
        resposta_seguranca_hash = p_resposta_hash
    WHERE id = p_id;
END //

-- 12. Excluir campo do perfil (profile)
DROP PROCEDURE IF EXISTS sp_excluir_campo_perfil //
CREATE PROCEDURE sp_excluir_campo_perfil(IN p_id INT, IN p_campo VARCHAR(50))
BEGIN
    IF p_campo = 'telefone' THEN
        UPDATE usuarios SET telefone = NULL WHERE id = p_id;
    ELSEIF p_campo = 'endereco' THEN
        UPDATE usuarios SET endereco = NULL WHERE id = p_id;
    ELSEIF p_campo = 'data_nascimento' THEN
        UPDATE usuarios SET data_nascimento = NULL WHERE id = p_id;
    END IF;
END //

-- 13. Excluir conta (profile, admin)
DROP PROCEDURE IF EXISTS sp_excluir_conta //
CREATE PROCEDURE sp_excluir_conta(IN p_id INT)
BEGIN
    DELETE FROM usuarios WHERE id = p_id;
END //

-- 14. Salvar log de auditoria
DROP PROCEDURE IF EXISTS sp_salvar_log //
CREATE PROCEDURE sp_salvar_log(
    IN p_usuario_id INT,
    IN p_nome VARCHAR(100),
    IN p_email VARCHAR(150),
    IN p_acao VARCHAR(80),
    IN p_detalhes VARCHAR(255)
)
BEGIN
    INSERT INTO logs_sistema (usuario_id, nome, email, acao, detalhes)
    VALUES (p_usuario_id, p_nome, p_email, p_acao, p_detalhes);
END //

-- 15. Cadastrar roupa (roupas/cadastrar)
DROP PROCEDURE IF EXISTS sp_cadastrar_roupa //
CREATE PROCEDURE sp_cadastrar_roupa(
    IN p_titulo VARCHAR(120),
    IN p_tipo VARCHAR(100),
    IN p_tamanho VARCHAR(10),
    IN p_sexo VARCHAR(20),
    IN p_estado VARCHAR(20),
    IN p_local_doacao VARCHAR(180),
    IN p_foto_path VARCHAR(255),
    IN p_id_usuario INT
)
BEGIN
    INSERT INTO roupas (titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, id_usuario, pausado)
    VALUES (p_titulo, p_tipo, p_tamanho, p_sexo, p_estado, p_local_doacao, p_foto_path, p_id_usuario, 0);
END //

-- 16. Buscar foto de roupa (roupas/excluir)
DROP PROCEDURE IF EXISTS sp_buscar_foto_roupa //
CREATE PROCEDURE sp_buscar_foto_roupa(IN p_id INT)
BEGIN
    SELECT foto_path FROM roupas WHERE id = p_id;
END //

-- 17. Excluir roupa (roupas/excluir)
DROP PROCEDURE IF EXISTS sp_excluir_roupa //
CREATE PROCEDURE sp_excluir_roupa(IN p_id INT)
BEGIN
    DELETE FROM roupas WHERE id = p_id;
END //

-- 18. Listar roupas com filtros (roupas/listar)
DROP PROCEDURE IF EXISTS sp_listar_roupas //
CREATE PROCEDURE sp_listar_roupas(
    IN p_tamanho VARCHAR(10),
    IN p_sexo VARCHAR(20),
    IN p_tipo VARCHAR(100),
    IN p_q VARCHAR(100)
)
BEGIN
    SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, criado_em
    FROM roupas
    WHERE pausado = 0
      AND (p_tamanho = '' OR tamanho = p_tamanho)
      AND (p_sexo = '' OR sexo = p_sexo)
      AND (p_tipo = '' OR tipo LIKE CONCAT('%', p_tipo, '%'))
      AND (p_q = '' OR titulo LIKE CONCAT('%', p_q, '%') OR tipo LIKE CONCAT('%', p_q, '%'))
    ORDER BY id DESC;
END //

-- 19. Pausar roupa (roupas/finalizar_doacao)
DROP PROCEDURE IF EXISTS sp_pausar_roupa //
CREATE PROCEDURE sp_pausar_roupa(IN p_id INT)
BEGIN
    UPDATE roupas SET pausado = 1 WHERE id = p_id;
END //

-- 20. Listar usuários (admin/usuarios)
DROP PROCEDURE IF EXISTS sp_listar_usuarios //
CREATE PROCEDURE sp_listar_usuarios()
BEGIN
    SELECT id, nome, email, is_admin FROM usuarios ORDER BY id ASC;
END //

-- 21. Promover usuário (admin/usuarios)
DROP PROCEDURE IF EXISTS sp_promover_usuario //
CREATE PROCEDURE sp_promover_usuario(IN p_id INT)
BEGIN
    UPDATE usuarios SET is_admin = 1 WHERE id = p_id;
END //

-- 22. Contar logs (admin/logs)
DROP PROCEDURE IF EXISTS sp_contar_logs //
CREATE PROCEDURE sp_contar_logs()
BEGIN
    SELECT COUNT(*) AS total FROM logs_sistema;
END //

-- 23. Listar logs paginados (admin/logs)
DROP PROCEDURE IF EXISTS sp_listar_logs_paginados //
CREATE PROCEDURE sp_listar_logs_paginados(IN p_limite INT, IN p_offset INT)
BEGIN
    SELECT id, usuario_id, nome, email, acao, detalhes, criado_em
    FROM logs_sistema
    ORDER BY id DESC
    LIMIT p_limite OFFSET p_offset;
END //

-- 24. Buscar roupa por ID (roupas/finalizar_doacao)
DROP PROCEDURE IF EXISTS sp_buscar_roupa_por_id //
CREATE PROCEDURE sp_buscar_roupa_por_id(IN p_id INT)
BEGIN
    SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, id_usuario, pausado
    FROM roupas
    WHERE id = p_id;
END //

DELIMITER ;