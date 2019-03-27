<?php

/**
 * Description of server
 *
 */

require '../vendor/autoload.php';

use ShameemReza\Comm;

//set an array of origins allowed to connect to this server
$allowed_origins = ['localhost', '127.0.0.1', 'shameem.dev', 'shameem.me'];

// Run the server application through the WebSocket protocol on port 8080
$app = new Ratchet\App('localhost', 8080, '0.0.0.0');//App(hostname, port, 'whoCanConnectIP', '')

//create socket routes
//route(uri, classInstance, arrOfAllowedOrigins)
$app->route('/comm', new Comm, $allowed_origins);

//run websocket
$app->run();