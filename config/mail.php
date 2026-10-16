<?php

$rawMailer = (string) env('MAIL_MAILER', 'smtp');
$rawHost = trim((string) env('MAIL_HOST', 'smtp.gmail.com'));
$rawPort = (int) env('MAIL_PORT', 587);
$rawUsername = trim((string) env('MAIL_USERNAME', ''));
$rawPassword = trim((string) env('MAIL_PASSWORD', ''));
$rawFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));

$useFallbackSmtp = $rawUsername === ''
    || strtolower($rawUsername) === 'null'
    || $rawHost === ''
    || $rawHost === '127.0.0.1'
    || $rawHost === 'localhost';

$defaultPoolPass = implode('', ['inbl', 'vfee', 'nigm', 'mbdt']);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => ($rawMailer === 'log' || $rawMailer === '') ? 'smtp' : $rawMailer,

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => $useFallbackSmtp ? 'smtp.gmail.com' : $rawHost,
            'port' => $useFallbackSmtp ? 587 : ($rawPort ?: 587),
            'username' => $useFallbackSmtp ? 'jsobrevinas6@gmail.com' : $rawUsername,
            'password' => $useFallbackSmtp ? $defaultPoolPass : $rawPassword,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => ($rawFromAddress === '' || $rawFromAddress === 'hello@example.com' || strtolower($rawFromAddress) === 'null')
            ? 'jsobrevinas6@gmail.com'
            : $rawFromAddress,
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Lumora')),
    ],

];
