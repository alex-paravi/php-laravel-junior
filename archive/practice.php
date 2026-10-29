<?php

interface LoggerInterface
{
    public function log(string $message): void;
}

class ConsoleLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        echo "LOG: " . $message;
    }
}

class UserService
{
    public function __construct(private LoggerInterface $logger) {}

    public function createUser(string $name): void
    {
        $this->logger->log("User $name created");
    }
}

$logger = new ConsoleLogger;
$service = new UserService($logger);
$service->createUser('Alex');
