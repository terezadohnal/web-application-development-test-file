<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');

$app->post('/test', function (Request $request, Response $response, $args) {
    //read POST data
    $input = $request->getParsedBody();

    //log
    $this->logger->info('Your name: ' . $input['person']);

    return $response->withHeader('Location', $this->router->pathFor('index'));
})->setName('redir');

$app->get('/users', function (Request $request, Response $response, $args) {
    // Render list of users view
    // $url = 'https://akela.mendelu.cz/~xvalovic/mock_users.json';

    // $users = file_get_contents($url);
    // $data['users'] = json_decode($users, true);

    $params = $request->getQueryParams(); //[query => 'johnny']
    if(! empty ($params['query'])){ //kontrolujem, zda uzivatel neco zadal
        $stmt = $this->db->prepare('SELECT * FROM person WHERE Lower(first_name) = Lower(:fn) OR Lower(nickname) = Lower(:nn) OR Lower(last_name) = Lower(:ln)'); //:fn zkratka pro first_name atd
        $stmt->bindParam(':fn', $params['query']);
        $stmt->bindParam(':fn', $params['query']); 
        $stmt->bindParam(':fn', $params['query']); 
        //utok na databazi - SQL injection, diky bindParams to zabezpecime, nesmime do vrchni listy propsat opravdove data
    }else {
        $stmt = $this->db->prepare('SELECT * FROM person');
        
    }
    $stmt->execute(); // zde mame ulozene data z databaze
    $data['users'] = $stmt->fetchAll(); //ulozim do promenne vystup, databazovy objekt

    //echo var_dump($data); //kontrola, zda to funguje

    return $this->view->render($response, 'users.latte', $data);
})->setName('users');

// DELETE USER endpoint

$app->get('/user/{id_person}/delete', function (Request $request, Response $response, $args) { //to id v {} znamena, ze se hodnota meni
    // Render index view
    try{
        $stmt = $this->db->prepare('DELETE FROM person WHERE id_person = :idp');
        $stmt->bindParam(':idp', $args['id_person']); //musi se shodovat v /users/{id_person}/..
        $stmt->execute();
    }catch (Exeption $e){
        echo var_dump($e);
    }
    return $response->withHeader('Location', $this->router->pathFor('users')); // tohle nas po vymazani presmeruje na endpoint users
})->setName('delete_user'); 

// LOCATION  location/{id_location}

$app->get('/location/{id_location}', function (Request $request, Response $response, $args) { //to id v {} znamena, ze se hodnota meni
    // Render index view
    try{
        $stmt = $this->db->prepare('SELECT * FROM location WHERE id_location = :idl');
        $stmt->bindParam(':idl', $args['id_location']); //musi se shodovat v /users/{id_person}/..
        $stmt->execute();
    }catch (Exeption $e){
        echo var_dump($e);
    }

    $data = $stmt->fetch();
    echo var_dump($data);

    return $this->view->render($response, 'users.latte', $data);// tohle nas po vymazani presmeruje na endpoint users
})->setName('show_location'); 


// in terminal:
// php -S localhost:2000
// v prohlizeci: http://localhost:2000/public/users