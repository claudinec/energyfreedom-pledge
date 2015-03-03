<?php
/**
 * PHP exercises for NationBuilder developer certification.
 */

require_once __DIR__.'/../vendor/autoload.php';

$oauth_path = __DIR__ . '/../lib/PHP-OAuth2/src/OAuth2/';
require $oauth_path . 'client.php';
require $oauth_path . 'GrantType/IGrantType.php';
require $oauth_path . 'GrantType/AuthorizationCode.php';

$app = new Silex\Application();
$app['debug'] = true;

/**
 * Default path.
 */
$app->get('/', function() {
  return 'Are you looking for the NationBuilder <a href="/auth">authentication page</a>?';
});

/**
 * Authenticate to NationBuilder sandbox.
 *
 * Variables in config/nationbuilder.json:
 * - $clientId
 * - $clientSecret
 * - $authorizeUrl
 */
$app->get('/auth', function () use ($app) {
  $client = new OAuth2\Client($clientId, $clientSecret);
  $redirectUrl    = 'http://energyfreedom-pledge.dev:8888/oauth_callback';
  $authUrl = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl);
  return $app->redirect($authUrl);
  // return $authUrl;
});

/**
 * OAuth callback path.
 */
$app->get('/oauth_callback', function () use ($app) {
  $code = $app['request']->get('code');

  // Generate a token response.
  $accessTokenUrl = 'https://sandbox1806.nationbuilder.com/oauth/token';
  $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
  // DEBUG HERE.
  // $response = $client->getAccessToken($accessTokenUrl, 'authorization_code', $params);

  // Set the client token.
  // $token = $response['result']['access_token'];
  // $client->setAccessToken($token);

  return 'Success!';
});

$app->run();
