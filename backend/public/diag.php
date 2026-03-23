<?php
header('Content-Type: text/plain');
echo "diagnostics:\n";
echo "open_basedir: " . ini_get('open_basedir') . "\n";
echo "vendor exists: " . (is_dir(__DIR__ . '/../vendor') ? 'yes' : 'no') . "\n";
echo "autoload exists: " . (file_exists(__DIR__ . '/../vendor/autoload.php') ? 'yes' : 'no') . "\n";
echo "autoload is_readable: " . (is_readable(__DIR__ . '/../vendor/autoload.php') ? 'yes' : 'no') . "\n";

require __DIR__ . '/../vendor/autoload.php';
echo "AUTOLOAD REQUIRED SUCCESSFULLY!\n";
