<?php

use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/poo/Usuario.php';
require __DIR__ . '/../src/poo/Perfil.php';
require __DIR__ . '/../src/poo/MW.php';


use \Slim\Routing\RouteCollectorProxy;

$app = AppFactory::create();


$app->post('/usuario', \Usuario::class . ':altaUsuario');

$app->get('/', \Usuario::class . ':traerUsuarios');

$app->post('/', \Perfil::class . ':altaPerfil');

$app->get('/perfil', \Perfil::class . ':traerPerfiles');

$app->post('/login', \Usuario::class . ':retornarJWT');

$app->get('/login', \Usuario::class . ':verificarporHeader');


$app->group('/perfiles', function (RouteCollectorProxy $grupo) {  

    $grupo->delete('/{id_perfil}', \Perfil::class . ':eliminarPerfil');
    $grupo->put('/{id_perfil}', \Perfil::class . ':modificarPerfil');


})->add(\MW::class . ':verificarTokenHeader');


$app->group('/usuarios', function (RouteCollectorProxy $grupo) {  

    $grupo->delete('/', \Usuario::class . ':eliminarUsuario');
    $grupo->post('/', \Usuario::class . ':modificarUsuario');


})->add(\MW::class . ':verificarTokenHeader');


$app->run();
