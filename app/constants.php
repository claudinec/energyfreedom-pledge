<?php
/**
 * Global constants.
 *
 * Constants set in config/nationbuilder.php:
 * - APP_URL
 * - BASE_API_URL
 * - CLIENT_ID
 * - CLIENT_SECRET
 */
require __DIR__ . '/../config/nationbuilder.php';
define('REDIRECT_URL', APP_URL . '/pledge');
define('AUTHORIZE_URL', BASE_API_URL . '/oauth/authorize');
define('ACCESS_TOKEN_URL', BASE_API_URL . '/oauth/token');

$client  = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

date_default_timezone_set('Australia/Melbourne');
