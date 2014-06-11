<?php

$config = array(
    "app" => array(
        "MasterCuti" => "\\WWII\\Console\\Runnable\\MasterCuti\\MasterCuti",
        "DbBackup" => "\\WWII\\Console\\Runnable\\DbBackup\\DbBackup",
        //~"Fingerprint" => "\\WWII\\Console\\Runnable\\Fingerprint\\Fingerprint",
    ),
    'db_backup' => array(
        'path' => __DIR__ . '/../db_backups',
    )
);

if (file_exists(__DIR__ . '/config.sensitive.php')) {
    $config = array_merge($config, include(__DIR__ . '/config.sensitive.php'));
}

return $config;
