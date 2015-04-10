<?php
/**
 * Energy Freedom Pledge editing app.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$oauth_path = __DIR__ . '/../vendor/adoy/oauth2/src/OAuth2/';
require $oauth_path . 'Client.php';
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
$appUrl         = 'http://energyfreedom-pledge.local:8888/';
$redirectUrl    = $appUrl . 'oauth_callback';
$authorizeUrl   = 'https://beyondzeroemissions.nationbuilder.com/oauth/authorize';

$client         = new OAuth2\Client($clientId, $clientSecret);
$authUrl        = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl);

/**
 * Error handling.
 */
use Symfony\Component\HttpFoundation\Response;

$app->error(function (\Exception $e, $code) {
    if ($app['debug']) {
      return;
    }

    return new Response($code . " error: \n<pre>" . $e . "</pre>");
});

/**
 * Default path – pledge lookup.
 */
$app->get('/', function() use ($app, $client) {
    $baseApiUrl = 'https://beyondzeroemissions.nationbuilder.com';
    global $authUrl;

    if (!isset($_GET['code'])) {
      return $app->redirect($authUrl);
    }
    else {
      $response = $client->fetch($baseApiUrl . '/api/v1/sites.json');
      return $response;
    }

    // Is user logged in?
    // DEBUG HERE
    // $response = $client->fetch($baseApiUrl . '/api/v1/people/me.json');
    // $result = json_decode($response);
    // $email = $response['result']['person']['email'];
    // return new Response("Your email address is " . $email);
    // print_r($response);
    // return new Response("You are logged in as " . $response['result']['person']['full_name']);
});

/**
 * Authenticate to NationBuilder.
 */
// $app->get('/auth', function () use ($app, $client) {
//     global $authUrl;
//     return $app->redirect($authUrl);
// });

/**
 * OAuth callback path.
 */
$app->get('/oauth_callback', function () use ($app, $client) {
    global $appUrl, $redirectUrl;
    $code = $app['request']->get('code');

    // Generate a token response.
    $accessTokenUrl = 'https://beyondzeroemissions.nationbuilder.com/oauth/token';
    $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
    $response = $client->getAccessToken($accessTokenUrl, 'authorization_code', $params);

    // Set the client token.
    $token = $response['result']['access_token'];
    $client->setAccessToken($token);

    // Return to app.
    return $app->redirect($appUrl);
});

/**
 * Query custom field values and pre-fill form with them.
 */

$app->run();
