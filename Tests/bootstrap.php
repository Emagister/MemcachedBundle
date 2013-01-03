<?php

if (!file_exists($autoload = __DIR__ . '/../vendor/autoload.php')) {
    die('To run the unit test you must execute "php composer.phar install --dev" in the root
         of the project to install the development dependencies!');
}

require $autoload;