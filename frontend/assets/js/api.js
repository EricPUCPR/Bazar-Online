/**
 * api.js — Camada de comunicação com o backend.
 *
 * Uso:
 *   import { api, auth } from './api.js';
 *
 * Ou via <script type="module">:
 *   import { api, auth } from '../assets/js/api.js';
 */

// ─── Configuração ─────────────────────────────────────────────────────────────

/** URL base da API. Ajuste conforme o ambiente. */
const API_BASE = 'http://localhost:8001';

// ─── Gerenciamento do token JWT ───────────────────────────────────────────────

const TOKEN_KEY = 'bazar_jwt';

export const auth = {
    /** Salva o JWT no localStorage. */
    salvarToken(token) {
        localStorage.setItem(TOKEN_KEY, token);
    },

    /** Retorna o JWT armazenado, ou null. */
    getToken() {
        return localStorage.getItem(TOKEN_KEY);
    },

    /** Remove o JWT (logout no cliente). */
    limparToken() {
        localStorage.removeItem(TOKEN_KEY);
    },

    /** Retorna true se há um token salvo (não verifica expiração no cliente). */
    estaLogado() {
        return !!this.getToken();
    },

    /**
     * Decodifica o payload do JWT sem verificar assinatura.
     * Usado apenas para exibir dados (nome, admin) no frontend.
     * A verificação real é feita no servidor a cada request.
     */
    decodificarPayload() {
        const token = this.getToken();
        if (!token) return null;
        try {
            const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
            return JSON.parse(atob(base64));
        } catch {
            return null;
        }
    },

    /** Retorna o nome do usuário logado, ou null. */
    getNome() {
        return this.decodificarPayload()?.nome ?? null;
    },

    /** Retorna true se o usuário logado é admin. */
    eAdmin() {
        return this.decodificarPayload()?.admin === true;
    },
};

// ─── Função base de fetch ─────────────────────────────────────────────────────

/**
 * Faz uma requisição ao backend.
 *
 * @param {string} endpoint   Caminho relativo à API_BASE (ex: '/api/roupas/listar.php')
 * @param {object} options    Opções adicionais do fetch (method, body, etc.)
 * @param {boolean} comAuth   Se true, adiciona o header Authorization: Bearer <token>
 * @returns {Promise<object>} Objeto JSON da resposta
 */
async function request(endpoint, options = {}, comAuth = false) {
    const headers = options.headers || {};

    if (comAuth) {
        const token = auth.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
    }

    const response = await fetch(`${API_BASE}${endpoint}`, {
        ...options,
        headers,
    });

    if (response.status === 401) {
        // Token expirado ou inválido — limpa e redireciona
        auth.limparToken();
        window.location.href = '/pages/login.html';
        return;
    }

    return response.json();
}

// ─── Endpoints do frontend ────────────────────────────────────────────────────

export const api = {
    // ── Auth ──────────────────────────────────────────────────────────────────

    /**
     * Etapa 1 do login: envia e-mail e senha.
     * Retorna { success, two_factor, pending_token, mensagem }
     */
    async login(email, senha) {
        const body = new FormData();
        body.append('email', email);
        body.append('senha', senha);
        return request('/api/auth/login.php', { method: 'POST', body });
    },

    /**
     * Etapa 2 do login: envia pending_token e código 2FA.
     * Retorna { success, token, nome, admin, mensagem }
     * Se sucesso, salva o JWT automaticamente.
     */
    async verificar2fa(pendingToken, codigo) {
        const body = new FormData();
        body.append('pending_token', pendingToken);
        body.append('codigo', codigo);
        const data = await request('/api/auth/verifica_2fa.php', { method: 'POST', body });
        if (data?.success && data.token) {
            auth.salvarToken(data.token);
        }
        return data;
    },

    /**
     * Logout: registra no servidor e limpa o token local.
     */
    async logout() {
        await request('/api/auth/logout.php', { method: 'POST' }, true).catch(() => {});
        auth.limparToken();
    },

    /**
     * Retorna dados do usuário atual via JWT (sem bater no banco).
     */
    async me() {
        return request('/api/auth/me.php', {}, true);
    },

    /**
     * Retorna os dados completos do perfil do banco de dados (autenticado).
     */
    async obterPerfil() {
        return request('/api/auth/profile.php', {}, true);
    },

    /**
     * Atualiza o Telegram Chat ID do usuário.
     */
    async atualizarTelegram(chatId) {
        const body = new FormData();
        body.append('action', 'atualizar_telegram');
        body.append('telegram_chat_id', chatId);
        return request('/api/auth/profile.php', { method: 'POST', body }, true);
    },

    /**
     * Atualiza a Pergunta e Resposta de Segurança.
     */
    async atualizarPerguntaSeguranca(pergunta, resposta) {
        const body = new FormData();
        body.append('action', 'atualizar_pergunta');
        body.append('pergunta_seguranca', pergunta);
        body.append('resposta_seguranca', resposta);
        return request('/api/auth/profile.php', { method: 'POST', body }, true);
    },

    /**
     * Exclui um campo opcional do perfil (telefone, endereco, data_nascimento).
     */
    async excluirCampoPerfil(campo) {
        const body = new FormData();
        body.append('action', 'excluir_campo');
        body.append('campo', campo);
        return request('/api/auth/profile.php', { method: 'POST', body }, true);
    },

    /**
     * Exclui permanentemente a própria conta.
     */
    async excluirConta() {
        const body = new FormData();
        body.append('action', 'excluir_conta');
        return request('/api/auth/profile.php', { method: 'POST', body }, true);
    },


    /**
     * Cria um novo usuário.
     * @param {FormData} formData
     */
    async cadastrar(formData) {
        return request('/api/auth/cadastro.php', { method: 'POST', body: formData });
    },

    /**
     * Confirma o e-mail via token da URL.
     * @param {string} token
     */
    async confirmarEmail(token) {
        return request(`/api/auth/confirmar_email.php?token=${encodeURIComponent(token)}`);
    },

    /**
     * Solicita link de recuperação de senha.
     * @param {string} email
     */
    async solicitarRecuperacaoSenha(email) {
        const body = new FormData();
        body.append('action', 'solicitar');
        body.append('email', email);
        return request('/api/auth/recuperar_senha.php', { method: 'POST', body });
    },

    /**
     * Redefine a senha usando o token do link de recuperação.
     * @param {string} token
     * @param {string} senha
     * @param {string} confirmarSenha
     */
    async redefinirSenha(token, senha, confirmarSenha) {
        const body = new FormData();
        body.append('action', 'redefinir');
        body.append('token', token);
        body.append('senha', senha);
        body.append('confirmar_senha', confirmarSenha);
        return request('/api/auth/recuperar_senha.php', { method: 'POST', body });
    },

    // ── Roupas ────────────────────────────────────────────────────────────────

    /**
     * Lista roupas com filtros opcionais.
     * @param {{ tamanho?, sexo?, tipo?, q? }} filtros
     */
    async listarRoupas(filtros = {}) {
        const params = new URLSearchParams(filtros).toString();
        const query  = params ? `?${params}` : '';
        return request(`/api/roupas/listar.php${query}`);
    },

    /**
     * Cadastra uma nova roupa.
     * @param {FormData} formData  (titulo, tipo, tamanho, sexo, estado, local_doacao, foto)
     */
    async cadastrarRoupa(formData) {
        return request('/api/roupas/cadastrar.php', { method: 'POST', body: formData }, true);
    },

    /**
     * Exclui uma roupa (admin only).
     * @param {number} id
     */
    async excluirRoupa(id) {
        const body = new FormData();
        body.append('id', id);
        return request('/api/roupas/excluir.php', { method: 'POST', body }, true);
    },

    /**
     * Finaliza uma doação.
     * @param {number[]} ids
     */
    async finalizarDoacao(ids) {
        const body = new FormData();
        body.append('ids', ids.join(','));
        body.append('aceite_compartilhamento', '1');
        return request('/api/roupas/finalizar_doacao.php', { method: 'POST', body }, true);
    },

    // ── Admin ─────────────────────────────────────────────────────────────────

    /**
     * Etapa 1 do login admin.
     * @param {string} email
     * @param {string} metodo  'telegram' | 'email' | 'pergunta'
     */
    async adminLogin(email, metodo) {
        const body = new FormData();
        body.append('email', email);
        body.append('metodo', metodo);
        return request('/api/admin/login.php', { method: 'POST', body });
    },

    /**
     * Etapa 2 do login admin (código Telegram/e-mail).
     * Se sucesso, salva o JWT automaticamente.
     */
    async adminVerificarCodigo(pendingToken, codigo) {
        const body = new FormData();
        body.append('pending_token', pendingToken);
        body.append('codigo', codigo);
        const data = await request('/api/admin/verifica_codigo.php', { method: 'POST', body });
        if (data?.success && data.token) {
            auth.salvarToken(data.token);
        }
        return data;
    },

    /**
     * Etapa 2 do login admin (pergunta de segurança).
     * Se sucesso, salva o JWT automaticamente.
     */
    async adminVerificarPergunta(pendingToken, resposta) {
        const body = new FormData();
        body.append('pending_token', pendingToken);
        body.append('resposta_seguranca', resposta);
        const data = await request('/api/admin/verifica_pergunta.php', { method: 'POST', body });
        if (data?.success && data.token) {
            auth.salvarToken(data.token);
        }
        return data;
    },

    /**
     * Lista usuários (admin only).
     */
    async adminListarUsuarios() {
        return request('/api/admin/usuarios.php', {}, true);
    },

    /**
     * Remove ou promove um usuário (admin only).
     * @param {'remover'|'promover'} action
     * @param {number} id
     */
    async adminAcaoUsuario(action, id) {
        const body = new FormData();
        body.append('action', action);
        body.append('id', id);
        return request('/api/admin/usuarios.php', { method: 'POST', body }, true);
    },

    /**
     * Lista logs do sistema (admin only).
     * @param {{ limite?, pagina? }} opcoes
     */
    async adminListarLogs(opcoes = {}) {
        const params = new URLSearchParams(opcoes).toString();
        const query  = params ? `?${params}` : '';
        return request(`/api/admin/logs.php${query}`, {}, true);
    },
};
