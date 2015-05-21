<?php
/**
 * Register providers for use in pledge viewer app.
 */

/**
 * Register Monolog.
 */
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/development.log',
    'monolog.level'   => "WARNING",
    'monolog.name'    => "pledge",
));

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
