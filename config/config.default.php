<?php

$config = array(
    'console' => array(
        'commands' => array(
            'database' => array(
                'description' => 'WWII Database Handler',
                'controller' => '\\WWII\\Console\\Runnable\\Database\\DatabaseController',
                'options' => array(
                    'backup' => array(
                        'description' => 'backup database to predefined directory with predefined name',
                        'short_name'  => '-b',
                        'long_name'   => '--backup',
                        'action'      => 'StoreTrue',
                    ),
                    'restore' => array(
                        'description' => 'restore database from file',
                        'short_name'  => '-r',
                        'long_name'   => '--restore',
                        'action'      => 'StoreTrue',
                    ),
                ),
                'arguments' => array(
                    'file' => array(
                        'description' => '.sql.gz file to restore',
                        'optional'    => true,
                    )
                )
            ),
            'cuti' => array(
                'description' => 'WWII Master Cuti Handler',
                'controller' => '\\WWII\\Console\\Runnable\\Cuti\\CutiController',
                'options' => array(
                    'generateMasterCuti' => array(
                        'description' => 'generate master cuti',
                        'short_name'  => '-m',
                        'long_name'   => '--master',
                        'action'      => 'StoreTrue',
                    ),
                    'generatePerpanjanganCuti' => array(
                        'description' => 'generate perpanjangan cuti',
                        'short_name'  => '-p',
                        'long_name'   => '--perpanjangan',
                        'action'      => 'StoreTrue',
                    ),
                    'regenerateMasterCuti' => array(
                        'description' => 'regenerate master cuti',
                        'short_name'  => '-r',
                        'long_name'   => '--regenerate-master',
                        'action'      => 'StoreTrue',
                    ),
                    'generateAll' => array(
                        'description' => 'generate all',
                        'short_name'  => '-a',
                        'long_name'   => '--all',
                        'action'      => 'StoreTrue',
                    ),
                )
            ),
            'daily-presence' => array(
                'description' => 'WWII Personel Daily Presence Handler',
                'controller' => '\\WWII\\Console\\Runnable\\DailyPresence\\DailyPresenceController',
                'options' => array(
                    'generateDailyPresence' => array(
                        'description' => 'generate personel daily presence',
                        'short_name'  => '-g',
                        'long_name'   => '--generate',
                        'action'      => 'StoreTrue',
                    ),
                ),
                'arguments' => array(
                    'datetime' => array(
                        'description' => 'datetime of daily presence to generate',
                        'optional' => true,
                    ),
                ),
            ),
        )
    ),
    'db_backup' => array(
        'path' => __DIR__ . '/../db_backups',
    )
);

if (file_exists(__DIR__ . '/config.sensitive.php')) {
    $config = array_merge($config, include(__DIR__ . '/config.sensitive.php'));
}

return $config;
