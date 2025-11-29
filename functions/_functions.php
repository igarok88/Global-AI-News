<?php


// цикл для автоматического подключения

$functionFiles = glob(__DIR__ . '/*.php');
foreach ($functionFiles as $file) {
    if (basename($file) !== '_functions.php') {
        require_once $file;
    }
}
