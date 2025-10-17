<?php // phpcs:disable Magento2.Legacy.InstallUpgrade.ObsoleteInstallScript

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

return [
    'db-host' => '127.0.0.1',
    'db-user' => 'root',
    'db-password' => 'root',
    'db-name' => 'magento_integration_tests',
    'db-prefix' => '',
    'backend-frontname' => 'backend',
    'search-engine' => 'elasticsearch7',
    'elasticsearch-host' => 'localhost',
    'elasticsearch-port' => 9200,
    'admin-user' => \Magento\TestFramework\Bootstrap::ADMIN_NAME, // @phpstan-ignore-line
    'admin-password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD, // @phpstan-ignore-line
    'admin-email' => \Magento\TestFramework\Bootstrap::ADMIN_EMAIL, // @phpstan-ignore-line
    'admin-firstname' => \Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME, // @phpstan-ignore-line
    'admin-lastname' => \Magento\TestFramework\Bootstrap::ADMIN_LASTNAME, // @phpstan-ignore-line
];
