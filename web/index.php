<?php
/**
 * Energy Freedom Pledge Viewer.
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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Register Monolog.
 */
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/development.log',
    'monolog.level'   => "WARNING",
    'monolog.name'    => "pledge",
));

/**
 * Debugging.
 */
$app->error(function (\Exception $e, $code) {
    if ($app['debug']) {
        return;
    }

    return new Response($code . " error: \n<pre>" . $e . "</pre>");
    $app['monolog']->addDebug($code . " error: " . $e);
});

/**
 * Register Twig provider.
 */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));

/**
 * Default path – check authentication.
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
 * The pledge app.
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
            $error = "<p><strong>ERROR</strong>: Invalid Grant. This code is invalid, expired, or revoked. <strong><a href='/'>START AGAIN.</a></strong></p>";
            $app['monolog']->addError("This code is invalid, expired, or revoked.");
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

    // Query custom field values and pre-fill form with them.

    // Template for page content.
    return $app['twig']->render('pledge.twig', array(
            'title' => 'Energy Freedom Pledge Viewer',
            'name' => $response['result']['person']['full_name'],
            'house_type' => $response['result']['person']['house_type'],
            'house_type_other' => $response['result']['person']['house_type_other'],
            'brick_veneer' => $response['result']['person']['brick_veneer'],
            'double_brick' => $response['result']['person']['double_brick'],
            'weatherboard' => $response['result']['person']['weatherboard'],
            'al_cladding' => $response['result']['person']['al_cladding'],
            'panels' => $response['result']['person']['panels'],
            'wall_construction_other' => $response['result']['person']['wall_construction_other'],
            'wall_batts' => $response['result']['person']['wall_batts'],
            'wall_infill' => $response['result']['person']['wall_infill'],
            'wall_foil' => $response['result']['person']['wall_foil'],
            'no_ins' => $response['result']['person']['no_ins'],
            'wall_insulation_other' => $response['result']['person']['wall_insulation_other'],
            'tiles' => $response['result']['person']['tiles'],
            'corrugated_metal' => $response['result']['person']['corrugated_metal'],
            'slate' => $response['result']['person']['slate'],
            'roof_construction_other' => $response['result']['person']['roof_construction_other'],
            'roof_batts' => $response['result']['person']['roof_batts'],
            'roof_infill' => $response['result']['person']['roof_infill'],
            'roof_foil' => $response['result']['person']['roof_foil'],
            'no_roof_ins' => $response['result']['person']['no_roof_ins'],
            'roof_insulation_other' => $response['result']['person']['roof_insulation_other'],
            'slab_on_ground' => $response['result']['person']['slab_on_ground'],
            'slab_off_ground' => $response['result']['person']['slab_off_ground'],
            'timber_boards' => $response['result']['person']['timber_boards'],
            'floor_construction_other' => $response['result']['person']['floor_construction_other'],
            'floor_batts' => $response['result']['person']['floor_batts'],
            'floor_infill' => $response['result']['person']['floor_infill'],
            'floor_foil' => $response['result']['person']['floor_foil'],
            'no_floor_ins' => $response['result']['person']['no_floor_ins'],
            'floor_insulation_other' => $response['result']['person']['floor_insulation_other'],
            'rooms_carpeted' => $response['result']['person']['rooms_carpeted'],
            'rooms_tiled' => $response['result']['person']['rooms_tiled'],
            'rooms_timber_floors' => $response['result']['person']['rooms_timber_floors'],
            'windows_glazing' => $response['result']['person']['windows_glazing'],
            'window_frame' => $response['result']['person']['window_frame'],
            'window_frame_other' => $response['result']['person']['window_frame_other'],
            'window_coverings_internal' => $response['result']['person']['window_coverings_internal'],
            'other_internal' => $response['result']['person']['other_internal'],
            'window_coverings_internal_pelmets' => $response['result']['person']['window_coverings_internal_pelmets'],
            'no_pelmets' => $response['result']['person']['no_pelmets'],
            'window_coverings_external' => $response['result']['person']['window_coverings_external'],
            'other_external' => $response['result']['person']['other_external'],
            'heating_gas_ducted' => $response['result']['person']['heating_gas_ducted'],
            'heating_gas_room' => $response['result']['person']['heating_gas_room'],
            'heating_electric_ducted' => $response['result']['person']['heating_electric_ducted'],
            'heating_electric_wall_radiator' => $response['result']['person']['heating_electric_wall_radiator'],
            'heating_electric_portable_radiator' => $response['result']['person']['heating_electric_portable_radiator'],
            'heating_electric_oil_bar_header' => $response['result']['person']['heating_electric_oil_bar_header'],
            'heating_hydronic_heating_wall' => $response['result']['person']['heating_hydronic_heating_wall'],
            'heating_hydronic_heating_slab' => $response['result']['person']['heating_hydronic_heating_slab'],
            'heating_reverse_cycle_pre_2000' => $response['result']['person']['heating_reverse_cycle_pre_2000'],
            'heating_reverse_cycle_post_2000' => $response['result']['person']['heating_reverse_cycle_post_2000'],
            'heating_wood_fired' => $response['result']['person']['heating_wood_fired'],
            'cooling_reverse_cycle_pre_2000' => $response['result']['person']['cooling_reverse_cycle_pre_2000'],
            'cooling_reverse_cycle_post_2000' => $response['result']['person']['cooling_reverse_cycle_post_2000'],
            'cooling_ceiling_fan' => $response['result']['person']['cooling_ceiling_fan'],
            'cooling_ducted_evaporative_cooling' => $response['result']['person']['cooling_ducted_evaporative_cooling'],
            'cooling_room_evaporative_cooling' => $response['result']['person']['cooling_room_evaporative_cooling'],
            'cooking_gas_cooktop' => $response['result']['person']['cooking_gas_cooktop'],
            'cooking_electric_ceramic_cooktop' => $response['result']['person']['cooking_electric_ceramic_cooktop'],
            'cooking_induction_cooktop' => $response['result']['person']['cooking_induction_cooktop'],
            'cooking_electric_oven' => $response['result']['person']['cooking_electric_oven'],
            'cooking_gas_oven' => $response['result']['person']['cooking_gas_oven'],
            'hot_water' => $response['result']['person']['hot_water'],
            'energy_production' => $response['result']['person']['energy_production'],
            'action_display' => $response['result']['person']['action_display'],
            'action_led' => $response['result']['person']['action_led'],
            'action_draft_proofing' => $response['result']['person']['action_draft_proofing'],
            'action_reverse_cycle' => $response['result']['person']['action_reverse_cycle'],
            'action_efficiency_appliances' => $response['result']['person']['action_efficiency_appliances'],
            'action_double_glazing' => $response['result']['person']['action_double_glazing'],
            'action_wall_insulation' => $response['result']['person']['action_wall_insulation'],
            'action_solar' => $response['result']['person']['action_solar'],
            'volunteer_signup_content' => $response['result']['person']['volunteer_signup_content'],
        )
    );
});

$app->run();
