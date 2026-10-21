<?php

declare(strict_types=1);

$autoload = getenv('TWEAKWISE_TEST_AUTOLOAD');
if (!$autoload) {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
}
if (!is_file($autoload)) {
    throw new RuntimeException('Set TWEAKWISE_TEST_AUTOLOAD to an existing Magento vendor/autoload.php.');
}

$loader = require $autoload;
$loader->addPsr4('Tweakwise\\Magento2Tweakwise\\', dirname(__DIR__, 2), true);
