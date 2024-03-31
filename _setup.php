<?php

use AidevsTaskApi\CorrectAnswerException;
use AidevsTaskApi\WrongAnswerException;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\ErrorHandler;

$errorHandler = ErrorHandler::register();
$errorHandler->setExceptionHandler(
    function(Throwable $exception) use ($errorHandler) {
        if (
            $exception instanceof CorrectAnswerException || 
            $exception instanceof WrongAnswerException
        ) {
            echo $exception->getMessage(), PHP_EOL;
            die;
        }

        $errorHandler->handleException($exception);
    }
);

(new Dotenv())->loadEnv(__DIR__ . '/.env');

define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);
