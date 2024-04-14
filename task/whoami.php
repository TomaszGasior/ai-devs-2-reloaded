<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new class('whoami') extends Task
{
    public function refreshParams(): void
    {
        $response = $this->httpClient
            ->request('GET', '/task/' . $this->token)
        ;

        $this->fillInMsgAndParams($response->toArray());
    }
};

$attempts = 10;
$facts = [];

while($attempts-- != 0) {
    $facts[] = $task->getParam('hint');
    
    $personName = get_person_name_by_facts($facts);

    if (!$personName) {
        $task->refreshParams();
        sleep(3);
        continue;
    }

    $task->sendAnswer($personName);
}

function get_person_name_by_facts(array $facts): string|false
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $message = <<<MESSAGE
    Podaj imię i nazwisko osoby, której dotyczą podane fakty. Podaj wyłącznie imię i nazwisko, bez dodatkowych uwag. Odpowiedz wyłącznie zgodnie z prawdą. Jeśli nie znasz jednoznacznej odpowiedzi lub jeśli do podanych faktów pasuje wiele osób, odpowiedz "NIE WIEM". 

    Fakty:
    MESSAGE;

    $message .= "\n" . implode(
        "\n", 
        array_map(
            fn($fact) => '- ' . $fact, 
            $facts
        )
    );

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

    if ('NIE WIEM' === $data) {
        return false;
    }

    return $data;
}
