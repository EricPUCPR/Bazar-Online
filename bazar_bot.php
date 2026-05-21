<?php
// Script de bot do Telegram via Long-Polling
// Deve ser rodado via linha de comando: php bazar_bot.php

require_once __DIR__ . '/config/app.php';

$token_bot = env_value('BOT_TOKEN');
$admin_chat_id = env_value('TELEGRAM_ADMIN_CHAT_ID'); // Gestão segura de quem pode interagir

if (empty($token_bot)) {
    die("Erro: BOT_TOKEN não configurado no .env\n");
}

$base_url = "https://api.telegram.org/bot{$token_bot}";

// Arquivo para guardar o último update_id processado (evita processar mensagens repetidas)
$offset_file = __DIR__ . '/banco/bot_offset.txt';
$offset = 0;
if (file_exists($offset_file)) {
    $offset = (int) file_get_contents($offset_file);
}

echo "Bot do Telegram iniciado. Aguardando mensagens de forma segura...\n";

// Configura o contexto HTTP para permitir long-polling (timeout alto)
$context_options = [
    'http' => [
        'method' => 'GET',
        'timeout' => 35 // Timeout do PHP levemente maior que o timeout do Telegram (30s)
    ]
];
$context = stream_context_create($context_options);

while (true) {
    // getUpdates com timeout de 30 segundos (long-polling)
    $url = "{$base_url}/getUpdates?offset={$offset}&timeout=30";

    // Suprime warnings se der timeout na rede
    $resposta = @file_get_contents($url, false, $context);

    if ($resposta === false) {
        sleep(1);
        continue;
    }

    $dados = json_decode($resposta, true);

    if ($dados && isset($dados['ok']) && $dados['ok']) {
        foreach ($dados['result'] as $update) {
            // Atualiza o offset para não ler a mesma mensagem novamente
            $offset = $update['update_id'] + 1;
            file_put_contents($offset_file, $offset);

            if (!isset($update['message']))
                continue;

            $chat_id = $update['message']['chat']['id'];
            $texto = $update['message']['text'] ?? '';

            // ---- SEGURANÇA: Autorização ----
            // Se um ADMIN_CHAT_ID estiver configurado, apenas ele pode interagir
            if (!empty($admin_chat_id) && (string) $chat_id !== (string) $admin_chat_id) {
                $msg_nao_autorizado = "🚫 Acesso negado. Você não tem permissão para interagir com o bot do sistema Bazar.";
                $send_url = "{$base_url}/sendMessage?chat_id={$chat_id}&text=" . urlencode($msg_nao_autorizado);
                @file_get_contents($send_url);
                echo "[ALERTA de Segurança] Tentativa de acesso não autorizada do Chat ID: {$chat_id}\n";
                continue;
            }

            echo "Chat ID: {$chat_id} | Mensagem: {$texto}\n";

            // ---- Lógica do Bot (Terceiro Método de Autenticação) ----
            if ($texto === '/start') {
                $resposta_texto = "🤖 Bot de Segurança Bazar Online ativado.\n\nSua sessão será monitorada por aqui. Enviarei códigos/confirmações de autenticação de administrador quando o sistema solicitar.";
            } elseif (strtolower($texto) === '/status') {
                $resposta_texto = "✅ Conexão segura estabelecida. Gestão de segredos funcionando.";
            } else {
                $resposta_texto = "Comando não reconhecido. Aguarde solicitações do painel do sistema.";
            }

            $send_url = "{$base_url}/sendMessage?chat_id={$chat_id}&text=" . urlencode($resposta_texto);
            @file_get_contents($send_url);
        }
    }

    usleep(200000); // 200ms de pausa
}
?>