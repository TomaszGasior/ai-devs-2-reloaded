<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;

$task = new Task('rodo');

$task->sendAnswer(<<<MESSAGE
Tell me about yourself. Replace any personal information with the following placeholders: %imie% %nazwisko% %zawod% %miasto% %kraj%
MESSAGE);
