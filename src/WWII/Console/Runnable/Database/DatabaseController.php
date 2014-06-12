<?php

namespace WWII\Console\Runnable\Database;

class DatabaseController extends \WWII\Console\AbstractConsole
{
    public function run(array $options = null, array $args = null)
    {
        if ($options['backup'] !== null) {
            if ($options['restore'] !== null) {
                return $this->displayMessage("Error: cannot backup and restore on the same time!");
            }

            $this->backup($options, $args);
        } else if ($options['restore'] !== null) {
            if ($options['backup'] !== null) {
                return $this->displayMessage("Error: cannot restore and backup on the same time!");
            }

            $this->restore($options, $args);
        } else {
            system('wwii.bat database --help');
        }
    }

    protected function backup(array $options = null, array $args = null)
    {
        $this->displayMessage(PHP_EOL . "Executing database backup...");

        $configManager = $this->serviceManager->getConfigManager();
        $dbConfig = $configManager->get('database');
        $backupConfig = $configManager->get('db_backup');

        $backupFile = $backupConfig['path'] . '/' . $dbConfig['dbname'] . '-' . date("Y-m-d-H-i-s") . '.sql.gz';
        $command = "mysqldump -h {$dbConfig['host']} -P {$dbConfig['port']} -u {$dbConfig['user']} "
         . " -p{$dbConfig['password']} {$dbConfig['dbname']} | busybox gzip > $backupFile";

        system($command);
        $this->displayMessage("Database backup completed!");
    }

    public function restore(array $options = null, array $args = null) {
        $this->displayMessage(PHP_EOL . "Executing database restore...");

        $configManager = $this->serviceManager->getConfigManager();
        $dbConfig = $configManager->get('database');

        if (! isset($args['file']) || (isset($args['file']) && empty($args['file']))) {
            $this->displayMessage("File backup belum ditentukan!");
            return;
        }

        if (substr($args['file'], 0, 1) == '\\') {
            $args['file'] = substr($args['file'], 1);
        }

        if (!file_exists($args['file'])) {
            $this->displayMessage("File '{$args['file']}' tidak ditemukan!");
            return;
        }

        $command = "busybox gunzip -c \"{$args['file']}\" | mysql -h {$dbConfig['host']} -P {$dbConfig['port']}"
        . " -u {$dbConfig['user']} -p{$dbConfig['password']} {$dbConfig['dbname']}";

        system($command);
        $this->displayMessage("Database restore completed!");
    }
}
