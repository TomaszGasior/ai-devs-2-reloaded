<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;

$task = new Task('helloapi');

$q = $task->getQuestion();
$task->sendAnswer($q->getParam('cookie'));
