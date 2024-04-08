<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('embedding');

$embedding = generate_embedding('Hawaiian pizza');

$task->sendAnswer($embedding);

function generate_embedding(string $input): array
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $response = $client->request('POST', 'embeddings', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'text-embedding-ada-002',
            'input' => $input,
        ],
    ]);

    $response = $response->toArray();
    
    return $response['data'][0]['embedding'];
}
