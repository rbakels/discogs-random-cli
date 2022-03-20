#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Command\RandomCommand;
use App\Command\LoginCommand;

$randomCommand = new RandomCommand();

$application = new Application("Discogs command line application", "0.1.0");
$application->add(new LoginCommand());
$application->add($randomCommand);
$application->setDefaultCommand($randomCommand->getName());
$application->run();
