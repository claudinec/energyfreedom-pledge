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
 * NationBuilder configuration.
 *
 * Variables in config/nationbuilder.php:
 * - $clientId
 * - $clientSecret
 * - $authorizeUrl
 */
require __DIR__ . '/../config/nationbuilder.php';
$client      = new OAuth2\Client($clientId, $clientSecret);
$redirectUrl = 'http://energyfreedom-pledge.dev:8888/oauth_callback';
$authUrl     = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl);
    
/**
 * Default path.
 */
$app->get('/', function() {
  return 'Are you looking for the NationBuilder <a href="/auth">authentication page</a>?';
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

    // Test.
    $baseApiUrl = 'https://beyondzeroemissions.nationbuilder.com';
    $response = $client->fetch($baseApiUrl . '/api/v1/sites');
    print_r($response);

    return $token;
});

$app->run();
