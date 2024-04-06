<?php

namespace AidevsTaskApi;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Task
{
    private string $token;
    private HttpClientInterface $httpClient;

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
    }

    public function getQuestion(): Question
    {
        $response = $this->httpClient
            ->request('GET', '/task/' . $this->token)
        ;

        return new Question($response->toArray());
    }

    public function sendAnswer(string|array $data): void
    {
        $response = $this->httpClient
            ->request('POST', '/answer/' . $this->token, [
                'json' => ['answer' => $data],
            ])
        ;

        if (200 === $response->getStatusCode()) {
            throw new CorrectAnswerException();
        }

        throw new WrongAnswerException();
    }
}

class Question
{
    private $params;
    private $msg;

    public function __construct(array $data)
    {
        $this->msg = $data['msg'];

        unset($data['code'], $data['msg']);
        $this->params = $data;
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
}

class WrongQuestionParamNameException extends Exception
{
    public function __construct(array $params)
    {
        $this->message = 'Existing param names: "' 
            . implode('" "', array_keys($params)) . '"';
    }
}

class CorrectAnswerException extends Exception 
{
    public function __construct()
    {
        $this->message = '✅ Correct answer';
    }
}

class WrongAnswerException extends Exception 
{
    public function __construct()
    {
        $this->message = '❌ Wrong answer';
    }
}