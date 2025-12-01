<?php
// error_logger.php

define('ERROR_LOG_FILE', __DIR__ . '/error_log.txt');

function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $date = date('Y-m-d H:i:s');
    $errorType = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER DEPRECATED',
    ];

    $type = isset($errorType[$errno]) ? $errorType[$errno] : 'UNKNOWN ERROR';

    $message = "[$date] [$type] $errstr in $errfile on line $errline" . PHP_EOL;

    // Dosyaya yazmadan önce kilitleme yaparak yazma işlemi
    $fp = fopen(ERROR_LOG_FILE, 'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    // Normal hata işleyişine devam etsin
    return false;
}

function customExceptionHandler($exception) {
    $date = date('Y-m-d H:i:s');
    $message = sprintf("[%s] Uncaught Exception: %s in %s on line %d%s",
        $date,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        PHP_EOL
    );
    $fp = fopen(ERROR_LOG_FILE, 'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function customShutdownHandler() {
    $error = error_get_last();
    if ($error !== NULL) {
        $date = date('Y-m-d H:i:s');
        $message = sprintf("[%s] Shutdown Error: %s in %s on line %d%s",
            $date,
            $error['message'],
            $error['file'],
            $error['line'],
            PHP_EOL
        );
        $fp = fopen(ERROR_LOG_FILE, 'a');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $message);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}

set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('customShutdownHandler');

ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(E_ALL);
?>