<?php

// Each suite gets a fresh ephemeral key, independent of any real installation.
$_SERVER['APP_KEY'] = $_ENV['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
require dirname(__DIR__).'/vendor/autoload.php';
