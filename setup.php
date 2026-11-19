<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

chdir(__DIR__);
umask(0077);
if (PHP_VERSION_ID < 80300 || ! extension_loaded('pdo_sqlite') || ! in_array('argon2id', password_algos(), true)) {
    fwrite(STDERR, "Use PHP 8.3+ with SQLite PDO and Argon2id enabled.\n");
    exit(1);
}
if (! is_file('vendor/autoload.php')) {
    fwrite(STDERR, "Run composer install --no-dev in this folder, then run setup again.\n");
    exit(1);
}
foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
    if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
        throw new RuntimeException("Could not create {$dir}.");
    }
}
$database = 'database/database.sqlite';
if (! is_file('.env')) {
    if (is_file($database) && filesize($database) > 0) {
        fwrite(STDERR, "Restore the original .env and application key for this existing database.\n");
        exit(1);
    }
    $template = file_get_contents('.env.example');
    $key = 'APP_KEY=base64:'.base64_encode(random_bytes(32));
    $settings = str_replace("APP_KEY=\n", $key."\n", str_replace("\r\n", "\n", $template));
    $handle = fopen('.env', 'x');
    if (! $handle || fwrite($handle, $settings) !== strlen($settings)) {
        throw new RuntimeException('Could not write the new .env file.');
    }
    fclose($handle);
}
if (! file_exists($database) && ! touch($database)) {
    throw new RuntimeException('Could not create the SQLite database.');
}
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$status = $app->handleCommand(new Symfony\Component\Console\Input\ArrayInput(['command' => 'migrate', '--force' => true]));
if ($status === 0) {
    echo PHP_EOL."Ready. Create a personal invitation with php artisan member:invite YOUR_USERNAME.".PHP_EOL;
}
exit($status);
