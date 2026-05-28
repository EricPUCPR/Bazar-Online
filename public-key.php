<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

if (file_exists('public.pem')) {
    echo file_get_contents('public.pem');
} else {
    http_response_code(404);
    echo "Erro: Arquivo public.pem não encontrado no servidor.";
}
?>
