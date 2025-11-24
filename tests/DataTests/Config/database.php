<?php

return [

    'migrations' => 't_migrations',

    'connections' => [

        'crate' => [
            'driver'   => 'crate',
            'name'     => 'crate',
            'host'     => 'localhost',
            'port'     => 4201,
            'database' => 'doc',
        ],
    ]

];
