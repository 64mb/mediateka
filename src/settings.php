<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // allow the web server to send the content-length header

        // renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // log settings
        'logger' => [
            'name' => 'slim-app',
            'path' => 'php://stdout',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // database connection settings
        "db" => [
            "host" => "mysql_database",
            "dbname" => "db",
            "user" => $_ENV['MYSQL_USER'],
            "pass" => $_ENV['MYSQL_PASSWORD']
        ]
    ],
];
