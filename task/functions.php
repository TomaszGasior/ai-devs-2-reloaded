<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;

$task = new Task('functions');

$task->sendAnswer([
    'name' => 'addUser',
    'description' => 'Add the user',
    'parameters' => [
        'type' => 'object',
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => 'User\'s first name',
            ],
            'surname' => [
                'type' => 'string',
                'description' => 'User\'s last name',
            ],
            'year' => [
                'type' => 'integer',
                'description' => 'User\'s birthday year',
            ],
        ],
    ],
]);
