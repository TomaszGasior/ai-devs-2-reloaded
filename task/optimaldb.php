<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('optimaldb');

$peopleInformation = fetch_people_information($task->getParam('database'));
$answer = create_context_using_people_information($peopleInformation, 9216);

$task->sendAnswer($answer);

/**
 * @return array[] Names as keys, array with sentences as values.
 */
function fetch_people_information(string $url): array
{
    $client = HttpClient::create();

    $response = $client->request('GET', $url);

    return $response->toArray();
}

/**
 * @param array[] $peopleInformation Names as keys, array with sentences as values.
 */
function create_context_using_people_information(array $peopleInformation, int $maxBytes): string
{
    $personContextMaxBytes = (int) round($maxBytes / count($peopleInformation), 0, PHP_ROUND_HALF_DOWN);

    $context = [];
    
    foreach ($peopleInformation as $name => $sentences) {
        $personContext = format_person_context($name, $sentences);
        $personContext = compress_person_context($personContext, $personContextMaxBytes);
    
        $context[] = $personContext;
    }
    
    return implode("\n\n", $context);
}

/**
 * @param string[] $sentences
 */
function format_person_context(string $name, array $sentences): string
{
    return mb_strtoupper($name) . ":\n" . implode(
        "\n",
        array_map(
            fn($sentence) => '- ' . $sentence,
            $sentences
        )
    );
}

function compress_person_context(string $context, int $maxBytes): string
{
    $minBytes = round($maxBytes * 0.95, 0);

    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $systemMessage = <<<TEXT
    Rephrase given text. Respond with the same amount of bullet points as in given input. Make each point more concise to cover the same information with fewer words. Don't lose any important information. Don't skip any bullet point. Make sure length of your response does not exceed $maxBytes bytes.

    Example
    Input
    ```
    Jan:
    - Jan lubi smażone placki, uwielbia je jeść.
    - Każdego dnia Jan chodzi na zakupy do sklepu Biedronka.
    - Z zawodu Jan jest inżynierem budownictwa.
    ```
    Output
    ```
    Jan:
    - uwielbia jeść smażone placki
    - codziennie robi zakupy w Biedronce
    - jest inżynierem budownictwa
    ```
    TEXT;

    $extraUserMessage = <<<TEXT
    Respond with the same amount of bullet points as in given input. Don't skip any bullet point. Make sure length of your response is longer than $minBytes and shorter than $maxBytes bytes.
    TEXT;

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 4000,
            'temperature' => 0.75,
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $context],
                ['role' => 'user', 'content' => $extraUserMessage],
            ],
        ],
    ]);

    $response = $response->toArray();
    $data = $response['choices'][0]['message']['content'];

    return $data;
}
