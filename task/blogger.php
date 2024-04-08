<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @return string[] Topics as keys, contents as values.
 */
function write_blog_post_using_open_ai(array $topics): array
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $message = <<<MESSAGE
    Napisz cztery rozdziały do wpisu na bloga w języku polskim na temat przyrządzania pizzy Margherity.
    
    Zwróć odpowiedź jako obiekt JSON według formatu
    {
    "nazwa rozdziału 1": "treść rozdziału 1",
    "nazwa rozdziału 2": "treść rozdziału 2",
    }
    
    Nazwy rozdziałów:
    MESSAGE;
    
    $message .= "\n" . implode(
        "\n", 
        array_map(
            fn($topic) => sprintf('- "%s"', $topic), 
            $topics
        )
    );
    
    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 1000,
            'response_format' => [ 'type' => 'json_object' ],
            'messages' => [
                ['role' => 'user', 'content' => $message],
            ],
        ],
    ]);

    $response = $response->toArray();
    $data = $response['choices'][0]['message']['content'];

    return json_decode($data, true);
}

$task = new Task('blogger');

$topics = $task->getParam('blog');

$contents = write_blog_post_using_open_ai($topics);

$answer = [];
foreach ($topics as $topic) {
    $answer[] = $contents[$topic];
}

$task->sendAnswer($answer);
