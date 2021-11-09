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

//seskupeni endpointu, ktere chceme zpristupnit pouze prihlasenym uzivatelum
$app->group('/auth', function() use($app){
    //do kazdeho endpointu automaticky doplni /auth

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

    //nacitani formulare new user
    $app->get('/user/new', function (Request $request, Response $response, $args) {

        $data['formData'] = [
            'first_name' => '',
            'last_name' => '',
            'nickname' => '',
            'gender' => '',
            'height' => '',
            'birth_day' => '',
            'street_name' => '',
            'street_number' => '',
            'city' => '',
            'zip' => ''
        ];
        return $this->view->render($response, 'user_new.latte', $data);
    })->setName('user_new');

    //zpracovani formulare
    //metoda se podiva do tela pozadavku a sestavi nam z toho pole
    $app->post('/user/new', function (Request $request, Response $response, $args){
        $formData = $request->getParsedBody();

        //zjisteni, jestli jsou prvni 3 atributy vyplnene
        if(empty ($formData['first_name']) || empty($formData['last_name']) || empty($formData['nickname']) ) {
            $data['message'] = 'Please fill required fields';
        }else { // muzeme to zadat do databaze
        //uzivatel vyplnil povinne inputy, zjistujeme adresu, zda vyplnil aspon jeden
        if(!empty ($formData['street_name']) || !empty($formData['street_number']) || !empty($formData['city']) || !empty($formData['zip']) ){
            //uzivatel chce vytvorit novou adresu
            $stmt = $this->db->prepare('INSERT INTO location (street_name, street_number, city, zip) VALUES (:sname, :snum, :city, :zip)');
            //nevime, ktery parametr uziv vyplnil, musime vyplnit NULL, pokud
            //pokud je pole nevyplnene, pouzijem podminku pomoci ternarniho operatoru
            $stmt->bindParam(':sname', empty($formData['street_name']) ? null : $formData['street_name']);
            $stmt->bindParam(':snum', empty($formData['street_number']) ? null : $formData['street_number']);
            $stmt->bindParam(':city', empty($formData['city']) ? null : $formData['city']);
            $stmt->bindParam(':zip', empty($formData['zip']) ? null : $formData['zip']);
            $stmt->execute();
            //chceme ziskat nove id lokace
            $id_location = $this->db->lastInsertedId(); // vrati nam id noveho zaznamu
        }

        //do bindParams muzu hodit jen hotove hotdnoty, do bindValues i vyrazy a terenarni operatory
        $stmt = $this->db->prepare('INSERT INTO person (nickname, first_name, last_name, birth_day, gender, height, id_location) VALUES (:nn, :fn, :ln, :bd, :gn, :hg, :idl)');

        $stmt->bindValue(':nn', $formData['nickname']);
        $stmt->bindValue(':fn', $formData['first_name']);
        $stmt->bindValue(':ln', $formData['last_name']);
        $stmt->bindValue(':bd', empty($formData['birth_day']) ? null : $formData['birth_day']);
        $stmt->bindValue(':gn', empty($formData['gender']) ? null : $formData['gender']);
        $stmt->bindValue(':hg', empty($formData['height']) ? null : $formData['height']);
        $stmt->bindValue(':idl', $id_location ? $id_location : null);
        $stmt->execute();
        $data['message'] = 'Person successfully inserted';
        }
        $data['formData'] = $formData;
        return $this->view->render($response, 'user_new.latte', $data);
    });

    //Logout user
    $app->get('/logout', function (Request $request, Response $response, $args) {
        session_destroy(); //tohle staci
        return $response->withHeader('Location', $this->router->pathFor('login'));
        //presmerovani
    })->setName('logout');

})->add(function($request, $response, $next){
    if(!empty($_SESSION['logged_user'])){ //overujem, jestli je uzivatel prihlaseny pomoci overeni existence session
        return $next($request, $response);
    }else {
        return $response->withHeader('Location', $this->router->pathFor('login'));
        //kdyz jsem odhlaseny, tak me to vykopne na prihlasovaci formular
    }
});



//render login form
$app->get('/login', function (Request $request, Response $response, $args) {
    return $this->view->render($response, 'login.latte');
})->setName('login');


//Autentizace usera
$app->post('/login', function (Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    $passwd_hash = hash('sha256', $formData['password']);

    $stmt = $this->db->prepare('SELECT id_person, first_name, nickname, last_name FROM person WHERE lower(nickname) = lower(:nn) AND password = :pswd'); // zjisteni, zda existuje uzivatel s takovym jmemen a heslem
    // :nn a :pswd jsou vstupy od uzivatele
    $stmt->bindValue(':nn', $formData['nickname']);
    $stmt->bindValue(':pswd', $passwd_hash);
    $stmt->execute();

    $logged_user = $stmt->fetch(); //{'nicname'=> 'aaaa' ...}

    if($logged_user){
        $_SESSION['logged_user'] = $logged_user;
        setcookie('first_name', $logged_user['first_name']);
        // je do nej ukladam vystup z databazoveho dotazu
        // dulezity radek pro prihlaseni a autentizaci uivatele, tato promenna zije po dobu celeho 'sezeni'
        return $response->withHeader('Location', $this->router->pathFor('users')); //nastavujem hlavicku a url na seznam mych uzivatelu
    }else {
        return $this->view->render($response, 'login.latte', ['message' => 'Wrong username']);
        // v opacnem pripade ho posleme na prihlasovaci formular a posleme mu zpravu o chybnych udajich
    }
});

// in terminal:
// php -S localhost:2000
// v prohlizeci: http://localhost:2000/public/users

