<?php
/**
 * The pledge viewer: display logged-in user's pledge data.
 */
require 'vars.php';
$app = new Silex\Application();
$app['debug'] = true;

/**
 * Register Form.
 */
$app->register(new Silex\Provider\FormServiceProvider());

/**
 * Register Twig provider.
 */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));

$app->get('/pledge', function (Request $request) use ($app, $client) {
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

    // Fetch the user's profile.
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
    $field_names = array(
        'full_name', 'house_type', 'house_type_other', 'brick_veneer',
        'double_brick', 'weatherboard', 'al_cladding', 'panels',
        'wall_construction_other', 'wall_batts', 'wall_infill', 'wall_foil',
        'no_ins', 'wall_insulation_other', 'tiles', 'corrugated_metal', 'slate',
        'roof_construction_other', 'roof_batts', 'roof_infill', 'roof_foil',
        'no_roof_ins', 'roof_insulation_other', 'slab_on_ground', 'slab_off_ground',
        'timber_boards', 'floor_construction_other', 'floor_batts',
        'floor_infill', 'floor_foil', 'no_floor_ins', 'floor_insulation_other',
        'rooms_carpeted', 'rooms_tiled', 'rooms_timber_floors', 'windows_glazing',
        'window_frame', 'window_frame_other', 'window_coverings_internal',
        'other_internal', 'window_coverings_internal_pelmets', 'no_pelmets',
        'window_coverings_external', 'other_external',
        'heating_gas_ducted', 'heating_gas_room', 'heating_electric_ducted',
        'heating_electric_wall_radiator', 'heating_electric_portable_radiator',
        'heating_electric_oil_bar_header', 'heating_hydronic_heating_wall',
        'heating_hydronic_heating_slab', 'heating_reverse_cycle_pre_2000',
        'heating_reverse_cycle_post_2000', 'heating_wood_fired',
        'cooling_reverse_cycle_pre_2000', 'cooling_reverse_cycle_post_2000',
        'cooling_ceiling_fan', 'cooling_ducted_evaporative_cooling',
        'cooling_room_evaporative_cooling', 'cooking_gas_cooktop',
        'cooking_electric_ceramic_cooktop', 'cooking_induction_cooktop',
        'cooking_electric_oven', 'cooking_gas_oven', 'hot_water',
        'energy_production', 'action_display', 'action_led',
        'action_draft_proofing', 'action_reverse_cycle',
        'action_efficiency_appliances', 'action_double_glazing',
        'action_wall_insulation', 'action_solar', 'volunteer_signup_content',
    );
    $data = array(
        'title' => 'Energy Freedom Pledge Viewer',
    );
    foreach ($field_names as $field_name) {
        $data[$field_name] = $response['result']['person'][$field_name];
    }

    $form = $app['form.factory']->createBuilder('form', $data)
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $data = $form->getData();

        // do something with the data
        // return $data;

        // redirect somewhere
        // return $app->redirect('...');
    }

    // return $app['twig']->render('pledge.twig', $data);
    return $app['twig']->render('pledge.twig', array('form' => $form->createView()));
});
