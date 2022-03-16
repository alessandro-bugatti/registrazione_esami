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
    $engine = new Engine('../templates', 'phtml');
    $engine->addData([ 'basepath' => BASE_PATH]);
    return $engine;
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
$app->setBasePath(BASE_PATH);

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
        'name' => $name
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

/*
 * Rotta per la creazione della form di ricerca di uno studente
 * tramite la matricola
 */
$app->get('/studente/cerca', function (Request $request, Response $response, $args) {
    $template = $this->get('template');
    $response->getBody()->write($template->render('cercaStudente'));
    return $response;
}
);

/*
 * Gestisce il caso che sia presente o meno lo studente
 * - se è già presente mostra la form di inserimento del voto
 * - altrimenti ridirigere verso la pagina di aggiunta dello studente
 */
$app->post('/voto/form', function (Request $request, Response $response, $args) {
    //Serve a fare il parsing dei dati contenuti nel body
    //spediti tramite la form con il metodo POST
    $data = $request->getParsedBody();
    //Recupero la matricola
    $matricola = $data['matricola'];
    //Controllo se è presente, nella realtà verificando
    //all'interno del database, qua è solo per prova
    if ($matricola == '12345') {
        $template = $this->get('template');
        $response->getBody()->write($template->render('inserisciVoto', [
            'matricola' => $matricola
        ]));
        return $response;
    }
    else{
        return $response->withStatus(302)->withHeader('Location', BASE_PATH . '/studente/form');
    }
}
);

/*
 * Inserisce il voto dello studente individuato dalla {matricola},
 * da completare con l'informazione sul professore che inserisce il voto
 * e dell'esame al quale si riferisce
 */

$app->post('/studente/{matricola}/voto', function (Request $request, Response $response, $args) {
    //Qui andrebbe il codice associato all'inserimento del voto nel database
    $response->getBody()->write($args['matricola']);
    return $response;
}
);

/*
 * Genera il form per l'inserimento di uno studente
 */
$app->get('/studente/form', function (Request $request, Response $response, $args) {
    $template = $this->get('template');
    $response->getBody()->write($template->render('inserisciStudente',));
    return $response;
}
);

/*
 * Inserisce un nuovo studente
 */
$app->post('/studente', function (Request $request, Response $response, $args){
    //Qui andrebbe il codice per l'inserimento dello studente nel DB
    $data = $request->getParsedBody();
    $matricola = $data['matricola'];
    $cognome = $data['cognome'];
    $nome = $data['nome'];
    $response->getBody()->write('Studente inserito: ' . $matricola . ', ' .
        $cognome . ' ' . $nome);
    return $response;
});


//Rotta per gli assett (immagini, file css, ecc.,
// deve essere messa in fondo a tutte le rotte
//altrimenti le intercetta
$app->get('/{folder}/{file}', function (Request $request, Response $response, $args) {
    //Crea il percorso fisico dove si dovrebbe trovare il file
    //Ad esempio D:\xampp_7_4_25\htdocs\registrazione_esami\public/../images/logo.jpg
    $filePath = __DIR__ . '/../' . $args['folder']. '/'. $args['file'];
    //Controllo dell'esistenza del file
    if (!file_exists($filePath)) {
        return $response->withStatus(404, 'File Not Found');
    }
    //Si controlla l'estensione del file
    switch (pathinfo($filePath, PATHINFO_EXTENSION)) {
        case 'css':
            $mimeType = 'text/css';
            break;

        case 'jpg':
            $mimeType = 'application/jpeg';
            break;

        // Per evitare di rimandare indietro file con estensione
        // diversa da quelle riconosciute, nel default si fa in
        // modo di non mandare indietro nulla
        default:
            $mimeType = 'text/html';
            $filePath = null;
    }
    //Aggiunge nell'header il content type corretto, che ho individuato
    //con lo switch di prima
    $response = $response->withHeader('Content-Type',$mimeType);
    // Scrive nel body il contenuto del file, se è tra quelli con estensioni
    // conosciute
    if ($filePath !== null)
        $response->getBody()->write(file_get_contents($filePath));
    return $response;
});

$app->run();
