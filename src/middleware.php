<?php
// Application middleware
//vydy kdyz prijde novy pozadavek, projde to pres tento skript

//zde muzeme ukladat globalni promenne

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->add(function(Request $request, Response $response, $next) {
    $basePath = $request->getUri()->getBasePath();
    $this->view->addParam('basePath', $basePath);
    if (isset($_SESSION['logged_user'])) {
        $this->view->addParam('loggedUser', $_SESSION['logged_user']);
    }
    return $next($request, $response);
});

//endpoint je ulozen v $next a pres next se odkazeme na endpoint, ktery chceme navstivit
