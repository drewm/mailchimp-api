<?php

\error_reporting(E_ALL);

include_once \dirname(__DIR__) . '/vendor/autoload.php';

if (!\class_exists(Symfony\Component\Dotenv\Dotenv::class)) {
    throw new \RuntimeException('You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
}
(new Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');
