<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('inprompt');

$question = $task->getQuestion();

$inputQuestion = $question->getParam('question');
$sentences = $question->getParam('input');

$firstName = get_first_name_from_question($inputQuestion);

$sentences = array_filter(
    $sentences, 
    fn($sentence) => str_contains($sentence, $firstName)
);

$answer = get_answer_for_question($inputQuestion, $sentences);

$task->sendAnswer($answer);

function get_first_name_from_question(string $question): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $message = 'Podaj imię zawarte w poniższym pytaniu. Podaj tylko imię i nic więcej. Pytanie: ' . $question;

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 256,
            'messages' => [
                ['role' => 'user', 'content' => $message],
            ],
        ],
    ]);

    $response = $response->toArray();
    $data = $response['choices'][0]['message']['content'];

    $data = trim($data, '.');

    return $data;
}

function get_answer_for_question(string $question, array $sentences): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $systemMessage = <<<MESSAGE
    Odpowiadaj tylko na pytania dotyczące podanego kontektu. Jeśli pytanie nie dotyczy podanego kontekstu, odmów odpowiedzi. Odpowiadaj po polsku.
    
    Kontekst:
    MESSAGE;
    
    $systemMessage .= "\n" . implode(
        "\n", 
        array_map(
            fn($sentence) => sprintf('- "%s"', $sentence), 
            $sentences
        )
    );

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 256,
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $question],
            ],
        ],
    ]);

    $response = $response->toArray();
    $data = $response['choices'][0]['message']['content'];

    return $data;
}
