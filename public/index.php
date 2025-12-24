<?php

declare(strict_types=1);

/**
 * scale-pos Entry Point
 * Front controller for all HTTP requests
 */

define('APP_START', microtime(true));

// Register the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Bootstrap the application
require_once __DIR__ . '/../app/Bootstrap/app.php';
