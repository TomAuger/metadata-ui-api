<?php

ini_set('display_errors','on');
error_reporting(E_ALL);

require_once getenv('WP_TESTS_DIR') . '/tests/phpunit/includes/functions.php';

require getenv('WP_TESTS_DIR') . '/tests/phpunit/includes/bootstrap.php';

require dirname( __FILE__ ) . '/framework/testcase.php';
