<?php

namespace AidevsTaskApi;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Task
{
    protected string $token;
    protected HttpClientInterface $httpClient;
    protected array $params;
    protected string $msg;

    public function __construct(string $name)
    {
        $this->httpClient = HttpClient::createForBaseUri(
            'https://tasks.aidevs.pl'
        );

        $response = $this->httpClient
            ->request('POST', '/token/' . $name, [
                'json' => ['apikey' => $_ENV['AIDEVS_TASKS_API_KEY']],
            ])
        ;
        
        $this->token = $response->toArray()['token'];

        $response = $this->httpClient
            ->request('GET', '/task/' . $this->token)
        ;

        $this->fillInMsgAndParams($response->toArray());
    }
    
    public function getMsg(): string
    {
        return $this->msg;
    }

    public function getParam(string $name): array|int|string
    {
        if (!isset($this->params[$name])) {
            throw new WrongQuestionParamNameException($this->params);
        }

        return $this->params[$name];
    }

    public function sendAnswer(string|array $data): void
    {
        $response = $this->httpClient
            ->request('POST', '/answer/' . $this->token, [
                'json' => ['answer' => $data],
            ])
        ;

        if (200 === $response->getStatusCode()) {
            throw new CorrectAnswerException($response->toArray());
        }

        throw new WrongAnswerException($response->toArray(false));
    }

    protected function fillInMsgAndParams(array $data): void
    {
        $this->msg = $data['msg'];

        unset($data['code'], $data['msg']);
        $this->params = $data;
    }
}

class WrongQuestionParamNameException extends Exception
{
    public function __construct(array $params)
    {
        $this->message = 'Existing param names: "' 
            . implode('" "', array_keys($params)) . '"';
    }
}

abstract class AnswerException extends Exception 
{
    private array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
        $this->filterResponse($this->response);
    }

    public function output(): void
    {
        echo $this->message;

        if ($this->response) {
            echo PHP_EOL, PHP_EOL;
            echo json_encode(
                $this->response, 
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        
        echo PHP_EOL;
    }

    protected function filterResponse(array &$response): void
    {
        unset($response['code']);
    }
}

class CorrectAnswerException extends AnswerException 
{
    protected $message = '✅ Correct answer';

    protected function filterResponse(array &$response): void
    {
        unset($response['code'], $response['msg'], $response['note']);
    }
}

class WrongAnswerException extends AnswerException 
{
    protected $message = '❌ Wrong answer';
}
