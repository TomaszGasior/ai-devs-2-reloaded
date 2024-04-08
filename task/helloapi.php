<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;

$task = new Task('helloapi');

$task->sendAnswer($task->getParam('cookie'));
