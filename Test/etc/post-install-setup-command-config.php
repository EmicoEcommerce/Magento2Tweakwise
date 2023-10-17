<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// List of bin/magento setup CLI commands to run after setup:install
return [
    [
        'command' => 'setup:config:set',
        'config' => [
            '--queue-default-connection' => 'db',
        ]
    ],
    [
        'command' => 'setup:upgrade',
        'config' => []
    ]
];
