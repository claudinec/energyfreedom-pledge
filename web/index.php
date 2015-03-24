<?php
/**
 * PHP exercises for NationBuilder developer certification.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$oauth_path = __DIR__ . '/../vendor/adoy/oauth2/src/OAuth2/';
require $oauth_path . 'client.php';
require $oauth_path . 'GrantType/IGrantType.php';
require $oauth_path . 'GrantType/AuthorizationCode.php';

$app = new Silex\Application();
$app['debug'] = true;

/**
 * Global variables.
 *
 * Variables set in config/nationbuilder.php:
 * - $clientId
 * - $clientSecret
 */
require __DIR__ . '/../config/nationbuilder.php';
$client      = new OAuth2\Client($clientId, $clientSecret);
$redirectUrl = 'http://energyfreedom-pledge.dev:8888/oauth_callback';
$authorizeUrl   = 'https://beyondzeroemissions.nationbuilder.com/oauth/authorize';
$authUrl     = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl);
    
/**
 * Default path – pledge lookup.
 */
$app->get('/', function() {
    // Test.
    $baseApiUrl = 'https://beyondzeroemissions.nationbuilder.com';
    $response = $client->fetch($baseApiUrl . '/api/v1/sites');
    // print_r($response);
    return $response;
});

/**
 * Authenticate to NationBuilder.
 */
$app->get('/auth', function () use ($app) {
    global $authUrl;
    return $app->redirect($authUrl);
});

/**
 * OAuth callback path.
 */
$app->get('/oauth_callback', function () use ($app) {
    global $client, $redirectUrl;
    $code = $app['request']->get('code');

    // Generate a token response.
    $accessTokenUrl = 'https://beyondzeroemissions.nationbuilder.com/oauth/token';
    $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
    // DEBUG HERE.
    $response = $client->getAccessToken($accessTokenUrl, 'authorization_code', $params);

    // Set the client token.
    $token = $response['result']['access_token'];
    $client->setAccessToken($token);

    return $token;
});

$app->run();
