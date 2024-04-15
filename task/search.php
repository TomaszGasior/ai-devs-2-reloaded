<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('search');

$question = $task->getParam('question');

preg_match('/(?:https|http)\:\/\/[^ ]+/', $task->getMsg(), $matches);
$documentsUrl = $matches[0];

$relevantDocuments = filter_relevant_documents_for_question($question, get_documents($documentsUrl));
$answer = get_answer_for_question($question, $relevantDocuments);

$task->sendAnswer($answer);

class Document
{
    public function __construct(
        public readonly int $id,
        public readonly string $url,
        public readonly string $title,
        public readonly string $info,
        public readonly string $date,
    ) {}
}

/**
 * @return Document[]
 */
function get_documents(string $url): array
{
    $client = HttpClient::create();

    $response = $client->request('GET', $url);
    $data = $response->toArray();

    return array_map(
        fn(int $i, array $datum) => new Document(
            $i, $datum['url'], $datum['title'], str_replace('INFO: ', '', $datum['info']), $datum['date']
        ),
        array_keys($data),
        $data
    );
}

/**
 * @param Document[] $documents
 */
function filter_relevant_documents_for_question(string $question, array $documents): array
{
    $client = HttpClient::createForBaseUri($_ENV['QDRANT_API_URL']);

    $client->request('DELETE', '/collections/aidevs_search_task');
    $client->request('PUT', '/collections/aidevs_search_task', ['json' => [
        'vectors' => ['size' => 1536, 'distance' => 'Cosine'],
    ]]);

    /** @var Document[] $chunk */
    foreach (array_chunk($documents, 20) as $chunk) {
        $response = $client->request('PUT', '/collections/aidevs_search_task/points?wait=true', ['json' => [
            'batch' => [
                'ids' => array_map(fn(Document $document) => $document->id, $chunk),
                'vectors' => generate_embeddings(
                    array_map(fn(Document $document) => $document->title.' '.$document->info, $chunk)
                ),
            ],
        ]]);
        unset($response); // Make synchronous request.
    }

    $response = $client->request('POST', '/collections/aidevs_search_task/points/search', ['json' => [
        'vector' => generate_embeddings(['q' => $question])['q'],
        'limit' => 3,
    ]]);

    $relevantIds = array_column($response->toArray()['result'], 'id');

    return array_filter(
        $documents,
        fn(Document $document) => in_array($document->id, $relevantIds),
    );
}

/**
 * @param string[] $input
 * @return string[]
 */
function generate_embeddings(array $input): array
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $response = $client->request('POST', 'embeddings', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'text-embedding-ada-002',
            'input' => array_values($input),
        ],
    ]);

    $response = $response->toArray();

    return array_combine(
        array_keys($input),
        array_column($response['data'], 'embedding')
    );
}

/**
 * @param Document[] $documents
 */
function get_answer_for_question(string $question, array $documents): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $context = implode(
        "\n---\n", 
        array_map(
            fn(Document $document) => sprintf(
                "Tytuł: %s\nSzczegóły: %s\nURL: %s",
                $document->title,
                $document->info,
                $document->url,
            ),
            $documents
        )
    );

    $systemMessage = <<<MESSAGE
    Odpowiadaj tylko na pytania dotyczące podanego kontektu. Jeśli pytanie nie dotyczy podanego kontekstu, odmów odpowiedzi. Podaj tylko adres URL bez dodatkowych komentarzy i uwag. Zwróć wyłącznie poprawny adres URL.
    
    Kontekst```
    $context
    MESSAGE;

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => 'gpt-3.5-turbo-0125',
            'max_tokens' => 1000,
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
