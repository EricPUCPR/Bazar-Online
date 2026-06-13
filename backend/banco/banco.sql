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
-- Endpoints: cadastro.php, login.php, confirmar_email.php,
--            recuperar_senha.php, profile.php,
--            admin/login.php, admin/usuarios.php, verifica_2fa.php
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`                      INT            NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome`                    VARCHAR(100)   NOT NULL,
    `email`                   VARCHAR(150)   NOT NULL UNIQUE,
    `telefone`                VARCHAR(20)    DEFAULT NULL,
    `endereco`                VARCHAR(200)   DEFAULT NULL,
    `data_nascimento`         DATE           DEFAULT NULL,
    `senha`                   VARCHAR(255)   NOT NULL,
    `criado_em`               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Recuperação de senha (recuperar_senha.php)
    `recuperacao_token`       VARCHAR(255)   DEFAULT NULL,
    `recuperacao_expira`      DATETIME       DEFAULT NULL,

    -- Confirmação de e-mail (cadastro.php, confirmar_email.php)
    `email_verificado`        TINYINT(1)     NOT NULL DEFAULT 0,
    `confirmacao_token`       VARCHAR(128)   DEFAULT NULL,
    `confirmacao_expira`      DATETIME       DEFAULT NULL,

    -- Controle de acesso (admin/login.php, admin/usuarios.php)
    `is_admin`                TINYINT(1)     NOT NULL DEFAULT 0,

    -- Autenticação admin alternativa (admin/login.php, profile.php)
    `telegram_chat_id`        VARCHAR(50)    DEFAULT NULL,
    `pergunta_seguranca`      VARCHAR(255)   DEFAULT NULL,
    `resposta_seguranca_hash` VARCHAR(255)   DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabela de logs do sistema ──────────────────────────────────
-- Usada por app_log_event() em config/app.php e admin/logs.php
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
-- Endpoints: roupas/cadastrar.php, roupas/listar.php,
--            roupas/excluir.php, roupas/finalizar_doacao.php,
--            config/app.php → db_ensure_roupa_schema()
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