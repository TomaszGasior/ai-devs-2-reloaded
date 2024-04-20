<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('people');

$peopleDataUrl = $task->getParam('data');
$question = $task->getParam('question');

$personName = get_person_name_from_question($question);

$peopleData = get_data_from_url($peopleDataUrl);
$peopleData = filter_person_name_relevant_data($peopleData, $personName);

$context = format_people_data_for_context($peopleData);
$answer = get_answer_for_question($question, $context);

$task->sendAnswer($answer);

function get_data_from_url(string $url): array
{
    $client = HttpClient::create();
    $response = $client->request('GET', $url);
    
    return $response->toArray();
}

function get_person_name_from_question(string $question): array
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $systemMessage = <<<MESSAGE
    Podaj imię i nazwisko osoby, której dotyczy pytanie. Nie odpowiadaj na zadane pytanie. Ściśle przestrzegaj formatu według przykładu. Użyj najbardziej oficjalnej formy imienia.

    Przykład```
    {"imie": "Jan", "nazwisko": "Kowalski"}
    MESSAGE;

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

    return json_decode($data, true);
}

function filter_person_name_relevant_data(array $data, array $personName): array
{
    $strongFilter = fn($datum) => 
        $datum['imie'] === $personName['imie'] 
        && $datum['nazwisko'] === $personName['nazwisko']
    ;

    $weakFilter = fn($datum) => 
        mb_substr($datum['imie'], 0, 1) === mb_substr($personName['imie'], 0, 1)
        && $datum['nazwisko'] === $personName['nazwisko']
    ;

    $veryWeakFilter = fn($datum) => 
        $datum['nazwisko'] === $personName['nazwisko']
    ;

    return
        array_filter($data, $strongFilter)
        ?: array_filter($data, $weakFilter)
        ?: array_filter($data, $veryWeakFilter)
    ;
}

function format_people_data_for_context(array $data): string
{
    return implode(
        "\n\n---\n\n",
        array_map(
            fn($datum) => implode(
                "\n",
                array_map(
                    fn($key, $value) => '- ' . $key . ': ' . $value,
                    array_map(
                        fn($key) => str_replace('_', ' ', $key),
                        array_keys($datum)
                    ),
                    array_values($datum)
                )
            ),
            $data
        )
    );
}

function get_answer_for_question(string $question, string $context): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $systemMessage = <<<MESSAGE
    Odpowiedz na zadane pytanie. Używaj wyłącznie podanego kontekstu. Nie korzystaj z własnej wiedzy. Odpowiadaj wyłącznie na temat osoby, której dotyczy pytanie.
    
    Kontekst```
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
