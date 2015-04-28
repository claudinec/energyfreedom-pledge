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
$redirectUrl    = $appUrl . 'pledge';

$baseApiUrl     = 'https://beyondzeroemissions.nationbuilder.com';
$authorizeUrl   = $baseApiUrl . '/oauth/authorize';

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
    // global $authUrl, $token;
    global $authUrl, $baseApiUrl;

    // If we don't have an auth code, redirect to auth url.
    if (!isset($_GET['code'])) {
        return $app->redirect($authUrl);
    }
    // If we do, redirect to the pledge app.
    else {
        return $app->redirect($redirectUrl);
    }
});

/**
 * OAuth callback path.
 */
$app->get('/pledge', function () use ($app, $client) {
    global $appUrl, $baseApiUrl, $redirectUrl;
    $code = $app['request']->get('code');

    // Generate a token response.
    $accessTokenUrl = 'https://beyondzeroemissions.nationbuilder.com/oauth/token';
    $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
    $response = $client->getAccessToken($accessTokenUrl, 'authorization_code', $params);

    // See if we got a valid token back or an error.
    if (isset($response['result']['error'])) {
    switch($response['result']['error']) {
        case 'invalid_grant':
            $error = "<b>ERROR</b>: Invalid Grant. This code is invalid, expired, or revoked.<br>";
            break;

        default:
            $error = "<b>Unknown error:</b> " . $response['result']['error'] . " - "
                . $response['result']['error_description'] . "<br>";
            break;
    }

    // End execution and display error message.
       die($error);
    }

    // Set the client token.
    $token = $response['result']['access_token'];
    //     $client->setAccessTokenType(1);
    $client->setAccessToken($token);

    // Is user logged in?
    $response = $client->fetch($baseApiUrl . '/api/v1/people/me.json');
    $result = json_decode($response);


    // See if we got a valid response back or an error.
    if (isset($response['result']['error'])) {
        $error = "<b>Unknown error:</b> " . $response['result']['error'] . " - "
            . $response['result']['error_description'] . "<br>";

    // End execution and display error message.
    die($error);
    }

    // $email = $response['result']['person']['email'];
    // return new Response("Your email address is " . $email);
    return new Response("You are logged in as " . $response['result']['person']['full_name'] . ".");

    /**
     * Query custom field values and pre-fill form with them.
     */

});

$app->run();
