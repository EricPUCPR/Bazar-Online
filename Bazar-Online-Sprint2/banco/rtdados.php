<?php
require_once dirname(__DIR__) . '/config/app.php';

$host = env_value('DB_HOST', 'localhost');
$usuario = env_value('DB_USER', 'root');
$senha = env_value('DB_PASS', '');
$database = env_value('DB_NAME_USUARIOS', 'sistema_usuarios');
$port = 3306;
$token_bot = env_value('BOT_TOKEN', '');
?>
