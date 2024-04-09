<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('whisper');

preg_match('/(?:https|http)\:\/\/[^ ]+/', $task->getMsg(), $matches);
$url = $matches[0];

$filePath = tempnam(sys_get_temp_dir(), '');
copy($url, $filePath);

$text = get_transcription_from_file_path($filePath);
$task->sendAnswer($text);

function get_transcription_from_file_path(string $filePath): string
{
    $fileHandle = fopen($filePath, 'r+');
    stream_context_set_option($fileHandle, 'http', 'content_type', mime_content_type($filePath));

    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $response = $client->request('POST', 'audio/transcriptions', [
        'auth_bearer' => OPENAI_API_KEY,
        'body' => [
            'model' => 'whisper-1',
            'file' => $fileHandle,
        ],
    ]);

    $response = $response->toArray();

    return $response['text'];
}
