<?php

require_once(__DIR__ . "/../../config.php");

foreach (scandir(STORAGEBASE) as $dir) {
    if ($dir === '.' || $dir === '..') {
        continue;
    }

    $source = STORAGEBASE . DIRECTORY_SEPARATOR . $dir;

    // Only process directories with a 32-character hexadecimal name
    if (!is_dir($source) || !preg_match('/^[a-f0-9]{32}$/i', $dir)) {
        continue;
    }

    // Split into 8 chunks of 4 characters
    $parts = str_split($dir, 4);

    // Build destination path
    $destination = STORAGEBASE;
    foreach ($parts as $part) {
        $destination .= DIRECTORY_SEPARATOR . $part;
    }

    // Create parent directories
    $parent = dirname($destination);
    if (!is_dir($parent)) {
        mkdir($parent, 0755, true);
    }

    $source = str_replace("//", "/", $source);
    $destination = str_replace("//", "/", $destination);
    // Move the directory
    if (rename($source, $destination)) {
        echo "Moved:\n";
        echo "  $source\n";
        echo "  -> $destination\n\n";
    } else {
        echo "Failed: $dir\n";
    }
}