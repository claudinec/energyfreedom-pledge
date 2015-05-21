<?php
/**
 * Functions for use in pledge viewer app.
 */

// FIXME Check whether user is logged in to our nation.
function check_auth($app, $client) {
    $authUrl = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URL);
    global $authUrl;

    if (!isset($_GET['code'])) {
        $code = $_GET['code'];
        $app['monolog']->addInfo('Code: ' . $code);
        return $app->redirect($authUrl);
    }
    // If we do, redirect to the pledge app.
    else {
        return $app->redirect(REDIRECT_URL);
    }
}

// FIXME Get an access token from NationBuilder.
function set_token($app, $client) {
    $code = $app['request']->get('code');
    $code = $_GET['code'];
    $app['monolog']->addInfo('Code: ' . $code);

    // Generate a token response.
    $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params);

    if (isset($response['result']['error'])) {
        switch($response['result']['error']) {
            case 'invalid_grant':
                $error = "<p><strong>ERROR</strong>: Invalid Grant. This code is invalid, expired, or revoked. <strong><a href='/'>START AGAIN.</a></strong></p>";
                $app['monolog']->addError($error);
                break;

            default:
                $error = "<b>Unknown error:</b> " . $response['result']['error'] . " - "
                   . $response['result']['error_description'] . "<br>";
                $app['monolog']->addError($error);
                break;
        }

        // End execution and display error message.
        die($error);
    }

    // Set the client token.
    $token = $response['result']['access_token'];
    $client->setAccessTokenType(1);
    $client->setAccessToken($token);
    $app['monolog']->addInfo('Token: ' . $token);

    // Fetch the user's profile.
    $response = $client->fetch(BASE_API_URL . '/api/v1/people/me.json');
    $result = json_decode($response);

    return $result;
}
