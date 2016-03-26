<?php
/**
 * Minimal form for testing.
 */
require 'vars.php';
$app = new Silex\Application();
$app['debug'] = true;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

$app->match('/pledge', function (Request $request) use ($app, $client) {
    $data = array(
        'title' => 'Energy Freedom Pledge Viewer',
        'name'  => 'test name'
    );

    $form = $app['form.factory']->createBuilder('form', $data)
        ->add('name')
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $data = $form->getData();

        // do something with the data
        return $data;

        // redirect somewhere
        // return $app->redirect('...');
    }

    return $app['twig']->render('pledgeMin.twig', array('form' => $form->createView()));
});
