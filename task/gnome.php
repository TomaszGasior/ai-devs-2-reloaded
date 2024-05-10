<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('gnome');

$imageUrl = $task->getParam('url');

$question =<<<TEXT
If there's a gnome in the image, name color of gnome's hat. 
Return just color name in Polish and nothing else.

If there no gnome on the image, return just one word "error" in English.
TEXT;

$answer = get_answer_for_question_about_image($question, $imageUrl);

$task->sendAnswer($answer);

function get_answer_for_question_about_image(string $question, string $imageUrl): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-4-1106-vision-preview',
            'max_tokens' => 256,
            'messages' => [
                [
                    'role' => 'user', 
                    'content' => [
                        ['type' => 'text', 'text' => $question],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                    ]
                ],
            ],
        ],
    ]);

    $response = $response->toArray();
    
    return $response['choices'][0]['message']['content'];
}

