<?php

use AidevsTaskApi\AnswerException;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\ErrorHandler;

$errorHandler = ErrorHandler::register();
$errorHandler->setExceptionHandler(
    function(Throwable $exception) use ($errorHandler) {
        if ($exception instanceof AnswerException) {
            $exception->output();
            die;
        }

        $errorHandler->handleException($exception);
    }
);

(new Dotenv())->loadEnv(__DIR__ . '/.env');

define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);
