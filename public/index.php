<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container as Container;
use League\Plates\Engine as Engine;
use Util\Connection;

require __DIR__ . '/../vendor/autoload.php';
require_once '../conf/config.php';

$container = new Container();
//da inserire prima della create di AppFactory
AppFactory::setContainer($container);

$container->set('template', function (){
    return new Engine('../templates', 'phtml');
});

$container->set('connection', function (){
    return Connection::getInstance();
});

$app = AppFactory::create();

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger
 *
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

//Questa parte deve essere sostituita con il nome della propria+
//sottocartella dove si trova l'applicazione
$app->setBasePath("/registrazione_esami");

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/altra_pagina', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Questa è un'altra pagina");
    return $response;
});

$app->get('/esempio_template/{name}', function (Request $request, Response $response, $args) {
    //Recupero l'oggetto che gestisce i template dal container
    //usando il metodo get e passando la stringa con cui l'ho identificato
    //nel metodo set
    $template = $this->get('template');
    //Recupero dall'URL il nome che si trova dopo esempio_template
    $name = $args['name'];
    //La stringa creata dal metodo render viene poi inserita nel body
    //grazie al metodo write
    $response->getBody()->write($template->render('esempio',[
        'name' => $name,
        'basepath' => BASE_PATH,
    ]));
    return $response;
});

$app->get('/esempio_database/', function (Request $request, Response $response, $args) {
    $pdo = $this->get('connection');
    $stmt = $pdo->query('SELECT * FROM corso');
    $result = $stmt->fetchAll();
    $response->getBody()->write($result[0]['descrizione']);
    return $response;
    }
);

//Rotta per le immagini, deve essere messa in fondo a tutte le rotte
//altrimenti le intercetta
$app->get('/{folder}/{file}', function (Request $request, Response $response, $args) {
    $filePath = __DIR__ . '/../' . $args['folder']. '/'. $args['file'];
    if (!file_exists($filePath)) {
        return $response->withStatus(404, 'File Not Found');
    }
    switch (pathinfo($filePath, PATHINFO_EXTENSION)) {
        case 'css':
            $mimeType = 'text/css';
            break;

        case 'jpg':
            $mimeType = 'application/jpeg';
            break;

        // Add more supported mime types per file extension as you need here

        default:
            $mimeType = 'text/html';
    }

    $response = $response->withHeader('Content-Type',$mimeType);
    $response->getBody()->write(file_get_contents($filePath));
    return $response;
});

$app->run();
