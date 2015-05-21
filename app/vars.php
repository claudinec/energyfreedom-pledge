<?php
/**
 * Global variables.
 *
 * Variables set in config/nationbuilder.php:
 * - $appUrl
 * - $baseApiUrl
 * - $clientId
 * - $clientSecret
 */
require __DIR__ . '/../config/nationbuilder.php';
$redirectUrl    = $appUrl . 'pledge';
$authorizeUrl   = $baseApiUrl . '/oauth/authorize';
$client         = new OAuth2\Client($clientId, $clientSecret);
$authUrl        = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl);

date_default_timezone_set('Australia/Melbourne');
