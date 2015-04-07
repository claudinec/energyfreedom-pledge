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
$client         = new OAuth2\Client($clientId, $clientSecret);
$appUrl         = 'http://energyfreedom-pledge.local:8888/';
$redirectUrl    = $appUrl . 'oauth_callback';
$authorizeUrl   = 'https://beyondzeroemissions.nationbuilder.com/oauth/authorize';
$authUrl        = $client->getAuthenticationUrl($authorizeUrl, $redirectUrl);

/**
 * Error handling.
 */
use Symfony\Component\HttpFoundation\Response;

$app->error(function (\Exception $e, $code) {
    if ($app['debug']) {
      return;
    }

    return new Response("Something went wrong: \n<pre>" . $e . "</pre>");
});

/**
 * Default path – pledge lookup.
 */
$app->get('/', function() use ($app, $client) {
    $baseApiUrl = 'https://beyondzeroemissions.nationbuilder.com';

    /* Test. */
    // $response = $client->fetch($baseApiUrl . '/api/v1/sites.json');
    // print_r($response);
    return new Response("OK!");

    // Display login options: Facebook, Twitter or email.

    // DEBUG HERE
    // Display current user login email address.
    // $response = $client->fetch($baseApiUrl . '/api/v1/people/me.json');
    // $result = json_decode($response);
    /* $email = $result['result']['person']['email']; */
    // $email = $response->{'person'}->{'email'};
    // return new Response("Your email address is " . $email);
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
    global $appUrl, $client, $redirectUrl;
    $code = $app['request']->get('code');

    // Generate a token response.
    $accessTokenUrl = 'https://beyondzeroemissions.nationbuilder.com/oauth/token';
    $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
    $response = $client->getAccessToken($accessTokenUrl, 'authorization_code', $params);

    // Set the client token.
    $token = $response['result']['access_token'];
    $client->setAccessToken($token);
    return $app->redirect($appUrl);
});

/**
 * Query custom field values and pre-fill form with them.
 */

$app->run();
