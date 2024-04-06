<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Question;
use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @return string String "YES" or "NO".
 */
function verify_question_and_answer_relevance(string $question, string $answer): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $message = <<<MESSAGE
    There was a question
    ```
    $question
    ```
    
    This is an answer
    ```
    $answer
    ```
    
    Is the answer relevant to the question? Please respond with just one word YES or NO.
    MESSAGE;

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

    return $data;
}

$task = new class('liar') extends Task
{
    public function getQuestionUsingInputQuestion(string $inputQuestion): Question
    {
        $response = $this->httpClient
            ->request('POST', '/task/' . $this->token, [
                'body' => [
                    'question' => $inputQuestion,
                ]
            ])
        ;

        return new Question($response->toArray());
    }
};

$inputQuestion = 'Jakie jest największe miasto w Polsce pod względem liczby ludności?';
$question = $task->getQuestionUsingInputQuestion($inputQuestion);

$answer = verify_question_and_answer_relevance($inputQuestion, $question->getParam('answer'));
$task->sendAnswer($answer);
