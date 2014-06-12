<?php

namespace WWII\Console;

class AbstractConsole implements ConsoleInterface
{
    protected $serviceManager;

    protected $databaseManager;

    protected $entityManager;

    protected $itemCount = 0;

    protected $progress = 0;

    protected $progressBarSign = '|';

    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->databaseManager = $serviceManager->get('DatabaseManager');
        $this->entityManager = $entityManager;
    }

    protected function prepareProgressBar($itemCount, $processName = 'Processing')
    {
        $this->itemCount = $itemCount;

        $this->progress = 0;
        $this->displayMessage($processName . ' ' . $itemCount . ' number(s) of data...');
    }

    protected function incrementProgressBar($progress)
    {
        $this->progress = $progress;
        $progress++;

        $currentProgressBarSize = round($progress / $this->itemCount * 100);
        $lastProgressBarSize = round($this->progress / $this->itemCount * 100);
        $progressBarSize = $currentProgressBarSize - $lastProgressBarSize;

        $tmpProgressBarSign = '';
        for ($i = 0; $i < $progressBarSize; $i++) {
            $tmpProgressBarSign .= $this->progressBarSign;
        }

        $this->displayMessage($tmpProgressBarSign, false);
    }

    protected function closeProgressBar()
    {
        $this->displayMessage(' 100%');
    }

    protected function displayMessage($message, $newLine = true)
    {
        fwrite(STDOUT, $message);

        if ($newLine) {
            fwrite(STDOUT, PHP_EOL);
        }
    }
}
