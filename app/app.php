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

 function check_token($response) {
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
 }

 $app->match('/pledge', function (Request $request) use ($app, $client) {
     global $appUrl, $baseApiUrl, $redirectUrl;
     $code = $app['request']->get('code');

     // Generate a token response.
     $accessTokenUrl = 'https://beyondzeroemissions.nationbuilder.com/oauth/token';
     $params = array('code' => $code, 'redirect_uri' => $redirectUrl);
     $response = $client->getAccessToken($accessTokenUrl, 'authorization_code', $params);

     // See if we got a valid token back or an error.
    //  check_token($response);

     // Set the client token.
     $token = $response['result']['access_token'];
     $client->setAccessToken($token);

     // Fetch the user's profile.
     $response = $client->fetch($baseApiUrl . '/api/v1/people/me.json');
     $result = json_decode($response);

     // See if we got a valid response back or an error.
     check_token($response);

     // Query custom field values and pre-fill form with them.
     $field_names = array(
         'house_type', 'house_type_other', 'brick_veneer',
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
         'heating_electric_oil_bar_heater', 'heating_hydronic_heating_wall',
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

     $form = $app['form.factory']->createBuilder('form', $data)
        ->add('house_type', 'choice', array(
            'choices' => array(
                'Stand alone home', 'Semi-detached home', 'Town house',
                'Apartment', 'Rural'
            )
        ))
        ->add('house_type_other', 'text', array(
            'required' => false
        ))
        ->add('wall_construction', 'choice', array(
            'choices' => array(
                'brick_veneer' => 'Brick veneer',
                'double_brick' => 'Double Brick',
                'weatherboard' => 'Weatherboard',
                'al_cladding'  => 'Aluminium cladding',
                'panels'       => 'Structurally integrated panels/cladding'
            ),
            'multiple' => true
        ))
        ->add('wall_construction_other', 'text', array(
            'required' => false
        ))
        ->add('wall_insulation', 'choice', array(
            'choices' => array(
                'wall_batts'  => 'Batts',
                'wall_infill' => 'Foam infill',
                'wall_foil'   => 'Reflective foil'
            ),
            'multiple' => true,
            'required' => false
        ))
        ->add('no_ins', 'checkbox', array(
            'label' => 'No insulation',
            'required' => false
        ))
        ->add('wall_insulation_other', 'text', array(
            'required' => false
        ))
        ->add('roof_construction', 'choice', array(
            'choices' => array(
                'tiles'            => 'Tiles',
                'corrugated_metal' => 'Corrugated metal',
                'slate'            => 'Slate'
            ),
            'multiple' => true
        ))
        ->add('roof_construction_other', 'text', array(
            'required' => false
        ))
        ->add('roof_insulation', 'choice', array(
            'choices' => array(
                'roof_batts'  => 'Batts',
                'roof_infill' => 'Foam infill',
                'roof_foil'   => 'Reflective foil'
            ),
            'multiple' => true,
            'required' => false
        ))
        ->add('no_roof_ins', 'checkbox', array(
            'label' => 'No insulation',
            'required' => false
        ))
        ->add('roof_insulation_other', 'text', array(
            'required' => false
        ))
        ->add('floor_construction', 'choice', array(
            'choices' => array(
                'slab_on_ground'  => 'Concrete Slab on ground',
                'slab_off_ground' => 'Concrete Slab off ground',
                'timber_boards'   => 'Timber boards'
            ),
            'multiple' => true
        ))
        ->add('floor_construction_other', 'text', array(
            'required' => false
        ))
        ->add('floor_insulation', 'choice', array(
            'choices' => array(
                'floor_batts'  => 'Batts',
                'floor_infill' => 'Foam infill',
                'floor_foil'   => 'Reflective foil'
            ),
            'multiple' => true,
            'required' => false
        ))
        ->add('floor_ins', 'checkbox', array(
            'label' => 'No insulation',
            'required' => false
        ))
        ->add('floor_insulation_other', 'text', array(
            'required' => false
        ))
        ->add('rooms_carpeted', 'choice', array(
            'choices' => array(
                '0%', '10%', '20%', '30%', '40%', '50%', '60%', '70%', '80%',
                '90%', '100%'
            ),
            'label' => 'Percentage of rooms carpeted'
        ))
        ->add('rooms_tiled', 'choice', array(
            'choices' => array(
                '0%', '10%', '20%', '30%', '40%', '50%', '60%', '70%', '80%',
                '90%', '100%'
            ),
            'label' => 'Percentage of rooms tiled'
        ))
        ->add('rooms_timber_floors', 'choice', array(
            'choices' => array(
                '0%', '10%', '20%', '30%', '40%', '50%', '60%', '70%', '80%',
                '90%', '100%'
            ),
            'label' => 'Percentage of rooms with timber floors'
        ))
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
        ->add('window_frame_other', 'text', array(
            'required' => false
        ))
        ->add('window_coverings_internal', 'choice', array(
            'choices' => array(
                'None', 'Venetian blinds', 'Block-out blinds', 'Light fabric blinds',
                'Heavy fabric blinds'
            )
        ))
        ->add('other_internal', 'text', array(
            'required' => false
        ))
        ->add('window_coverings_internal_pelmets', 'checkbox', array(
            'label' => 'Window Coverings Internal Pelmets',
            'required' => false
        ))
        ->add('no_pelmets', 'checkbox', array(
            'label' => 'No Pelmets',
            'required' => false
        ))
        ->add('window_coverings_external', 'choice', array(
            'choices' => array(
                'Awnings', 'Natural shading'
            )
        ))
        ->add('other_external', 'text', array(
            'required' => false
        ))
        ->add('heating_gas_ducted', 'checkbox', array(
            'label' => 'Heating: Gas ducted',
            'required' => false
        ))
        ->add('heating_gas_room', 'checkbox', array(
            'label' => 'Heating: Gas in room',
            'required' => false
        ))
        ->add('heating_electric_ducted', 'checkbox', array(
            'label' => 'Heating: Electric ducted',
            'required' => false
        ))
        ->add('heating_electric_wall_radiator', 'checkbox', array(
            'label' => 'Heating: Electric on wall radiator',
            'required' => false
        ))
        ->add('heating_electric_portable_radiator', 'checkbox', array(
            'label' => 'Heating: Electric portable radiator',
            'required' => false
        ))
        ->add('heating_electric_oil_bar_heater', 'checkbox', array(
            'label' => 'Heating: Electric oil/bar heater',
            'required' => false
        ))
        ->add('heating_hydronic_heating_wall', 'checkbox', array(
            'label' => 'Heating: Hydronic heating on wall',
            'required' => false
        ))
        ->add('heating_hydronic_heating_slab', 'checkbox', array(
            'label' => 'Heating: Hydronic heating in slab',
            'required' => false
        ))
        ->add('heating_reverse_cycle_pre_2000', 'checkbox', array(
            'label' => 'Heating: Reverse cycle air conditioner installed pre 2000',
            'required' => false
        ))
        ->add('heating_reverse_cycle_post_2000', 'checkbox', array(
            'label' => 'Heating: Reverse cycle air conditioner installed post 2000',
            'required' => false
        ))
        ->add('heating_wood_fired', 'checkbox', array(
            'label' => 'Heating: Wood-fired',
            'required' => false
        ))
        ->add('cooling_reverse_cycle_pre_2000', 'checkbox', array(
            'label' => 'Cooling: Reverse cycle air conditioner installed pre 2000',
            'required' => false
        ))
        ->add('cooling_reverse_cycle_post_2000', 'checkbox', array(
            'label' => 'Cooling: Reverse cycle air conditioner installed post 2000',
            'required' => false
        ))
        ->add('cooling_ceiling_fan', 'checkbox', array(
            'label' => 'Cooling: Ceiling fan',
            'required' => false
        ))
        ->add('cooling_ducted_evaporative_cooling', 'checkbox', array(
            'label' => 'Cooling: Ducted evaporative cooling',
            'required' => false
        ))
        ->add('cooling_room_evaporative_cooling', 'checkbox', array(
            'label' => 'Cooling: In room evaporative cooling',
            'required' => false
        ))
        ->add('cooking_gas_cooktop', 'checkbox', array(
            'label' => 'Cooking: Gas cooktop',
            'required' => false
        ))
        ->add('cooking_electric_ceramic_cooktop', 'checkbox', array(
            'label' => 'Cooking: Electric ceramic cooktop',
            'required' => false
        ))
        ->add('cooking_induction_cooktop', 'checkbox', array(
            'label' => 'Cooking: Induction cooktop',
            'required' => false
        ))
        ->add('cooking_electric_oven', 'checkbox', array(
            'label' => 'Cooking: Electric oven',
            'required' => false
        ))
        ->add('cooking_gas_oven', 'checkbox', array(
            'label' => 'Cooking: Gas oven',
            'required' => false
        ))
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
        ->add('action_display', 'checkbox', array(
            'label' => 'Action: In-home display connected to your smart meter',
            'required' => false
        ))
        ->add('action_led', 'checkbox', array(
            'label' => 'Action: Replaced Halogen downlights with LED’s',
            'required' => false
        ))
        ->add('action_draft_proofing', 'checkbox', array(
            'label' => 'Action: Draft-proofing measures',
            'required' => false
        ))
        ->add('action_reverse_cycle', 'checkbox', array(
            'label' => 'Action: High efficiency reverse cycle air conditioner for heating and cooling',
            'required' => false
        ))
        ->add('action_efficiency_appliances', 'checkbox', array(
            'label' => 'Action: High efficiency appliances',
            'required' => false
        ))
        ->add('action_double_glazing', 'checkbox', array(
            'label' => 'Action: Retrofitted double glazing for your windows',
            'required' => false
        ))
        ->add('action_wall_insulation', 'checkbox', array(
            'label' => 'Action: Retrofitted wall insulation',
            'required' => false
        ))
        ->add('action_solar', 'checkbox', array(
            'label' => 'Action: Installed rooftop solar',
            'required' => false
        ))
        ->add('solar_amount', 'choice', array(
            'choices' => array(
                '1kw', '1.5kw', '2kw', '2.5kw', '3kw', '3.5kw', '4kw', '4.5kw',
                '5kw', 'over 5kw'
            ),
            'required' => false
        ))
        ->add('volunteer_signup_content', 'textarea', array(
            'label'    => 'Comments, other ideas, etc.',
            'required' => false
        ))
        ->getForm();

     $form->handleRequest($request);

     if ($form->isValid()) {
         $data = $form->getData();

         // do something with the data
          return $data;

         // redirect somewhere
         return $app->redirect('/pledge/submit');
     }

    //  return $app['twig']->render('pledge.html.twig', $data);
    return $app['twig']->render('pledge.html.twig', array(
         'form'      => $form->createView(),
         'full_name' => $response['result']['person']['full_name']
    ));
 });

/**
 * Submit the data to NationBuilder.
 */
$app->post('/pledge/submit', function (Request $request) use ($app, $client) {
    return $request;
        // TODO Submit the data to NationBuilder.
        // TODO Display a message to the user.
});

return $app;
