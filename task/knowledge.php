<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('knowledge');

$question = $task->getParam('question');
$answer = get_answer_for_question($question);

$task->sendAnswer($answer);

function get_answer_for_question(string $question): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 256,
            'messages' => [
                ['role' => 'user', 'content' => $question],
            ],
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_exchange_rate_by_currency_code',
                        'description' => 'Get exchange rate for given currency named after currency code.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'currency_code' => [
                                    'type' => 'string',
                                    'description' => 'Currency code like PLN, EUR or CZK.',
                                ],
                            ],
                        ],
                    ]
                ],
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_country_population_by_country_code',
                        'description' => 'Get country population for given country named after country code.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'country_code' => [
                                    'type' => 'string',
                                    'description' => 'Country code like PL, DE, FR, CZ.',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_answer_for_general_question',
                        'description' => 'Get answer for any question other than exchange rate or currency code.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'question' => [
                                    'type' => 'string',
                                    'description' => 'The question user asked.',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ],
    ]);

    $response = $response->toArray();

    $call = $response['choices'][0]['message']['tool_calls'][0]['function'] ?? null;
    if (null === $call) {
        $call = [
            'name' => 'get_answer_for_general_question',
            'arguments' => json_encode(['question' => $question]),
        ];
    }

    $name = ['function_call', $call['name']];
    $args = json_decode($call['arguments'], true);

    return $name(...$args);
}

trait function_call
{
    static function get_exchange_rate_by_currency_code(string $currency_code): float
    {
        $data = json_decode(
            file_get_contents(
                'https://api.nbp.pl/api/exchangerates/rates/a/' . $currency_code . '?format=json'
            ),
            true
        );
    
        return $data['rates'][0]['mid'];
    }

    static function get_country_population_by_country_code(string $country_code): float
    {
        $data = json_decode(
            file_get_contents(
                'https://restcountries.com/v3.1/alpha/' . $country_code
            ),
            true
        );
    
        return $data[0]['population'];
    }

    static function get_answer_for_general_question(string $question): string
    {
        $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

        $response = $client->request('POST', 'chat/completions', [
            'auth_bearer' => OPENAI_API_KEY,
            'json' => [
                'model' => 'gpt-3.5-turbo-0125',
                'max_tokens' => 256,
                'messages' => [
                    ['role' => 'user', 'content' => $question],
                ],
            ],
        ]);

        $response = $response->toArray();
        $data = $response['choices'][0]['message']['content'];

        return $data;
    }
}
