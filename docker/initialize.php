<?php

// Publish the key atomically so a restart never replaces an existing enrollment key.
$state = $argv[1];
$lock = fopen($state.'/init.lock', 'c');
if (! $lock || ! flock($lock, LOCK_EX)) {
    throw new RuntimeException('Could not lock the application data directory.');
}
if (! is_file($state.'/app.env')) {
    $tmp = tempnam($state, 'key-');
    $key = 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;
    if ($tmp === false || file_put_contents($tmp, $key) !== strlen($key) || ! rename($tmp, $state.'/app.env')) {
        throw new RuntimeException('Could not persist the application key.');
    }
}
$db = getenv('DB_DATABASE');
if (! file_exists($db) && ! touch($db)) {
    throw new RuntimeException('Could not create the SQLite database.');
}
fclose($lock);
