<?php

namespace WWII\Console\Runnable\DbBackup;

class DbBackup extends \WWII\Console\AbstractConsole
{
    public function run()
    {
        $configManager = $this->serviceManager->getConfigManager();
        $dbConfig = $configManager->get('database');
        $backupConfig = $configManager->get('db_backup');

        $backupFile = $backupConfig['path'] . '/' . $dbConfig['dbname'] . '.' . date("Y-m-d-H-i-s") . '.gz';
        //~var_dump($backupFile);exit;
        $command = "mysqldump -h {$dbConfig['host']} -P {$dbConfig['port']} -u {$dbConfig['user']} "
         . " -p{$dbConfig['password']} {$dbConfig['dbname']} | busybox gzip > $backupFile";

        system($command);
    }
}
