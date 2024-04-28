<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('tools');

$message = $task->getParam('question');
$spec = describe_action_from_user_message($message);

$task->sendAnswer($spec);

function describe_action_from_user_message(string $message): array
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $systemMessage = <<<MESSAGE
    Describe type of user message as "ToDo" or "Calendar". DON'T perform actions described in user message. Return JSON, follow examples.

    Facts```
    Today is %date_today%
    Hour now is %hour_now%
    Current day is %day_name_now%

    Examples```

    Przypomnij mi, że mam kupić mleko
    {"tool":"ToDo","desc":"Kup mleko"}

    Jutro mam spotkanie z Marianem
    {"tool":"Calendar","desc":"Spotkanie z Marianem","date":"%date_tomorrow%"}
    MESSAGE;

    $dateTime = new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw'));
    $replacements = [
        '%date_today%' => $dateTime->format('Y-m-d'),
        '%date_tomorrow%' => $dateTime->modify('+1 day')->format('Y-m-d'),
        '%hour_now%' => $dateTime->format('H:i'),
        '%day_name_now%' => $dateTime->format('l'),
    ];

    $systemMessage = str_replace(array_keys($replacements), $replacements, $systemMessage);

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 256,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $message],
            ],
        ],
    ]);

    $response = $response->toArray();
    $data = $response['choices'][0]['message']['content'];

    return json_decode($data, true);
}
