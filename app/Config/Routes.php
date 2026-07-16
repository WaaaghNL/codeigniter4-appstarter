<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/* Shield Admin */
$routes->group('shield', static function ($routes) {
    $routes->get('/', 'ShieldAdminController::index');
    $routes->get('create-default-users', 'ShieldAdminController::createDefaultUsers');
    $routes->get('create', 'ShieldAdminController::create');
    $routes->post('store', 'ShieldAdminController::store');
    $routes->get('edit/(:num)', 'ShieldAdminController::edit/$1');
    $routes->post('update/(:num)', 'ShieldAdminController::update/$1');
    $routes->get('delete/(:num)', 'ShieldAdminController::delete/$1');
    $routes->get('permissions/(:num)', 'ShieldAdminController::permissions/$1');
    $routes->post('permissions/(:num)', 'ShieldAdminController::savePermissions/$1');
});