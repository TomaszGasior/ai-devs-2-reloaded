<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('scraper');

$question = $task->getParam('question');
$context = get_content_from_url($task->getParam('input'));

$answer = get_answer_for_question($question, $context);
$task->sendAnswer($answer);

function get_content_from_url(string $url): string
{
    $client = HttpClient::create([
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:124.0) Gecko/20100101 Firefox/124.0',
        ],
    ]);

    $remainingAttempts = 3;
    $waitTimeSecs = 2;

    while (true) {
        try {
            $response = $client->request('GET', $url);
            
            return $response->getContent();
        } 
        catch (Throwable $e) {
            if (0 === $remainingAttempts) {
                throw new RuntimeException('Failed to fetch file.');
            }
            --$remainingAttempts;

            sleep($waitTimeSecs);
            $waitTimeSecs *= 2;
        }
    }
}

function get_answer_for_question(string $question, string $context): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $systemMessage = <<<MESSAGE
    Answer user question. Your reply must be shorter than 200 characters.
    
    Context```
    $context
    MESSAGE;

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 500,
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
