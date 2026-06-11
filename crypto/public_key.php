<?php
header('Content-Type: application/octet-stream');

$publicKeyPath = __DIR__ . '/public.der';

if (!is_readable($publicKeyPath)) {
    http_response_code(404);
    exit('Chave pública não encontrada.');
}

readfile($publicKeyPath);