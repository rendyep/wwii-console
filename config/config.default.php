<?php

$config = array(
    "app" => array(
        "MasterCuti" => "\\WWII\\Console\\Runnable\\MasterCuti\\MasterCuti",
    ),
);

if (file_exists(__DIR__ . '/config.sensitive.php')) {
    $config = array_merge($config, include(__DIR__ . '/config.sensitive.php'));
}

return $config;
