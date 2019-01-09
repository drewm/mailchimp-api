<?php

\error_reporting(E_ALL);

include_once \dirname(__DIR__) . '/vendor/autoload.php';

if (!\class_exists('Dotenv\Dotenv')) {
    throw new \RuntimeException('You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
}

$env_file_path = __DIR__ . '/../';

if (file_exists($env_file_path . '.env')) {
    $dotenv = new Dotenv\Dotenv($env_file_path);
    $dotenv->load();
}
