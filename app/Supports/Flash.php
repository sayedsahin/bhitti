<?php

namespace App\Supports;

use Bhitti\Session\Session;

class Flash
{
    protected static array $flashClass = [
        'success' => 'alert alert-success',
        'error' => 'alert alert-danger',
        'warning' => 'alert alert-warning',
        'info' => 'alert alert-info',
    ];


    public static function render(): string
    {
        $message = Session::get('flash');

        if (!$message) {
            return '';
        }

        Session::forget('flash');

        return self::build($message);
    }


    private static function build(array $message): string
    {
        if (!empty($message['errors'])) {
            return self::errors($message['errors']);
        }

        if (!empty($message['error'])) {
            return self::message($message['error'], 'error');
        }

        if (!empty($message['success'])) {
            return self::message($message['success'], 'success');
        }

        if (!empty($message['warning'])) {
            return self::message($message['warning'], 'warning');
        }

        if (!empty($message['info'])) {
            return self::message($message['info'], 'info');
        }

        return '';
    }


    private static function message(mixed $message, string $type): string
    {
        $messages = is_array($message)
            ? $message
            : [$message];


        $class = self::$flashClass[$type]
            ?? self::$flashClass['info'];


        return sprintf(
            "<div class=\"%s\">%s</div>",
            $class,
            implode('<br>', array_map('e', $messages))
        );
    }


    private static function errors(mixed $errors): string
    {
        if (!is_array($errors)) {
            return self::message($errors, 'error');
        }


        $messages = [];


        foreach ($errors as $field => $error) {

            $error = is_array($error)
                ? $error
                : [$error];


            foreach ($error as $msg) {
                $messages[] = "* [" . e($field) . "] " . e($msg);
            }
        }


        return self::message($messages, 'error');
    }
}
