<?php
/**
 * Energy Freedom Pledge Viewer.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$oauth_path = __DIR__ . '/../vendor/adoy/oauth2/src/OAuth2/';
require $oauth_path . 'Client.php';
require $oauth_path . 'GrantType/IGrantType.php';
require $oauth_path . 'GrantType/AuthorizationCode.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require 'vars.php';
$app = new Silex\Application();
$app['debug'] = true;

/**
 * Register Monolog.
 */
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/development.log',
    'monolog.level'   => "WARNING",
    'monolog.name'    => "pledge",
));

/**
 * Default path – check authentication.
 */
// TODO Move this to a function so we can re-use it.
$app->get('/', function() use ($app, $client) {
    global $authUrl, $baseApiUrl;

    // TODO Move this to a function so we can re-use it.
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
  * Register Form.
  */
 $app->register(new Silex\Provider\FormServiceProvider());

/**
 * Register Translation provider.
 */
 $app->register(new Silex\Provider\TranslationServiceProvider());

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
         'action_wall_insulation', 'action_solar', 'solar_amount',
         'volunteer_signup_content',
     );
     $data = array();
     foreach ($field_names as $field_name) {
         $data[$field_name] = $response['result']['person'][$field_name];
     }
     $app['monolog']->addDebug($data);
     // FIXME $data is not being sent to the template.

     $form = $app['form.factory']->createBuilder('form', $data)
        ->add('full_name')
        ->add('house_type', 'choice', array(
            'choices' => array(
                'Stand alone home', 'Semi-detached home', 'Town house',
                'Apartment', 'Rural'
            )
        ))
        ->add('house_type_other')
        ->add('brick_veneer')
        ->add('double_brick')
        ->add('weatherboard')
        ->add('al_cladding')
        ->add('panels')
        ->add('wall_construction_other')
        ->add('wall_batts')
        ->add('wall_infill')
        ->add('wall_foil')
        ->add('no_ins')
        ->add('wall_insulation_other')
        ->add('tiles')
        ->add('corrugated_metal')
        ->add('slate')
        ->add('roof_construction_other')
        ->add('roof_batts')
        ->add('roof_infill')
        ->add('roof_foil')
        ->add('no_roof_ins')
        ->add('roof_insulation_other')
        ->add('slab_on_ground')
        ->add('slab_off_ground')
        ->add('timber_boards')
        ->add('floor_construction_other')
        ->add('floor_batts')
        ->add('floor_infill')
        ->add('floor_foil')
        ->add('no_floor_ins')
        ->add('floor_insulation_other')
        ->add('rooms_carpeted')
        ->add('rooms_tiled')
        ->add('rooms_timber_floors')
        ->add('windows_glazing', 'choice', array(
            'choices' => array(
                'Single glazed', 'Single glazed with reflective coating',
                'Double glazed', 'Triple Glazed'
            )
        ))
        ->add('window_frame', 'choice', array(
            'choices' => array(
                'Timber', 'Aluminium', 'Plastic (uPVC)'
            )
        ))
        ->add('window_frame_other')
        ->add('window_coverings_internal', 'choice', array(
            'choices' => array(
                'None', 'Venetian blinds', 'Block-out blinds', 'Light fabric blinds',
                'Heavy fabric blinds'
            )
        ))
        ->add('other_internal')
        ->add('window_coverings_internal_pelmets')
        ->add('no_pelmets')
        ->add('window_coverings_external', 'choice', array(
            'choices' => array(
                'Awnings', 'Natural shading'
            )
        ))
        ->add('other_external')
        ->add('heating_gas_ducted')
        ->add('heating_gas_room')
        ->add('heating_electric_ducted')
        ->add('heating_electric_wall_radiator')
        ->add('heating_electric_portable_radiator')
        ->add('heating_electric_oil_bar_header')
        ->add('heating_hydronic_heating_wall')
        ->add('heating_hydronic_heating_slab')
        ->add('heating_reverse_cycle_pre_2000')
        ->add('heating_reverse_cycle_post_2000')
        ->add('heating_wood_fired')
        ->add('cooling_reverse_cycle_pre_2000')
        ->add('cooling_reverse_cycle_post_2000')
        ->add('cooling_ceiling_fan')
        ->add('cooling_ducted_evaporative_cooling')
        ->add('cooling_room_evaporative_cooling')
        ->add('cooking_gas_cooktop')
        ->add('cooking_electric_ceramic_cooktop')
        ->add('cooking_induction_cooktop')
        ->add('cooking_electric_oven')
        ->add('cooking_gas_oven')
        ->add('hot_water', 'choice', array(
            'choices' => array(
                'Solar with gas booster', 'Solar with electric booster',
                'Solar with no boost', 'Gas', 'Instant Gas', 'Electric',
                'Electric heat pump'
            )
        ))
        ->add('energy_production', 'choice', array(
            'choices' => array(
                'Thinking about generating a portion of your electricity needs',
                'Currently installing ways to produce your electricity needs',
                'Already producing some of your own electricity',
                'Producing all of your own electricity',
                'Producing an excess of electricity'
            )
        ))
        ->add('action_display')
        ->add('action_led')
        ->add('action_draft_proofing')
        ->add('action_reverse_cycle')
        ->add('action_efficiency_appliances')
        ->add('action_double_glazing')
        ->add('action_wall_insulation')
        ->add('action_solar')
        ->add('solar_amount', 'choice', array(
            'choices' => array(
                '1kw', '1.5kw', '2kw', '2.5kw', '3kw', '3.5kw', '4kw', '4.5kw',
                '5kw', 'over 5kw'
            )
        ))
        ->add('volunteer_signup_content')
        ->getForm();

     $form->handleRequest($request);

     if ($form->isValid()) {
         $data = $form->getData();

         // do something with the data
         return $data;

         // redirect somewhere
         // return $app->redirect('...');
     }

    //  return $app['twig']->render('pledge.html.twig', $data);
     return $app['twig']->render('pledge.html.twig', array('form' => $form->createView()));
 });

/**
 * Form submit handler.
 */
$app->post('/pledge/submit', function (Request $request) use ($app, $client) {
    return $request;
});

$app->run();
