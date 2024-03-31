<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('moderation');

$question = $task->getQuestion();

$answer = check_input_in_moderation_api($question->getParam('input'));
$answer = array_map('intval', $answer);

$task->sendAnswer($answer);

/**
 * @param string[] $input
 * @return bool[]
 */
function check_input_in_moderation_api(array $input): array
{
    $client = HttpClient::createForBaseUri('https://api.openai.com');

    $response = $client->request(
        'POST', 
        'v1/moderations',
        [
            'auth_bearer' => OPENAI_API_KEY,
            'json' => [
                'input' => $input,
                'model' => 'text-moderation-latest',
            ]
        ]
    )->toArray();

    return array_map(
        fn(array $result) => $result['flagged'],
        $response['results']
    );
}
