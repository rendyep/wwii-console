<?php

namespace WWII\Console;

interface ConsoleInterface
{
    public function __construct(
        \WWII\Service\ServiceManagerInterface $serviceManager,
        \Doctrine\ORM\EntityManager $entityManager
    );
}
