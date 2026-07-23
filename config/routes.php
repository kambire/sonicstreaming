<?php

declare(strict_types=1);

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

/**
 * Definicion de rutas. Devuelve una funcion que recibe el Router.
 */
return function (Router $router): void {

    // CSRF global para peticiones que modifican estado.
    $csrf = new CsrfMiddleware();
    $auth = new AuthMiddleware();
    $admin = new RoleMiddleware('admin');
    $reseller = new RoleMiddleware('admin', 'reseller');
    $client = new RoleMiddleware('client');

    // ---- Publicas / auth / streams ----
    $router->get('/', 'HomeController@index');
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login', [$csrf]);
    $router->post('/logout', 'AuthController@logout', [$csrf, $auth]);

    // Enlaces de reproduccion directa y descargas M3U / PLS
    $router->get('/listen/station_{id}', 'StreamController@listen');
    $router->get('/listen/station_{id}/m3u', 'StreamController@m3u');
    $router->get('/listen/station_{id}/pls', 'StreamController@pls');

    // ---------------------------------------------------------------
    // ADMIN
    // ---------------------------------------------------------------
    $router->group([$auth, $admin], function (Router $r) use ($csrf) {
        $r->get('/admin', 'Admin\\DashboardController@index');
        $r->get('/admin/dashboard', 'Admin\\DashboardController@index');

        // Servidores
        $r->get('/admin/servers', 'Admin\\ServerController@index');
        $r->get('/admin/servers/create', 'Admin\\ServerController@create');
        $r->post('/admin/servers', 'Admin\\ServerController@store', [$csrf]);
        $r->get('/admin/servers/{id}/edit', 'Admin\\ServerController@edit');
        $r->put('/admin/servers/{id}', 'Admin\\ServerController@update', [$csrf]);
        $r->delete('/admin/servers/{id}', 'Admin\\ServerController@destroy', [$csrf]);

        // Planes
        $r->get('/admin/plans', 'Admin\\PlanController@index');
        $r->get('/admin/plans/create', 'Admin\\PlanController@create');
        $r->post('/admin/plans', 'Admin\\PlanController@store', [$csrf]);
        $r->get('/admin/plans/{id}/edit', 'Admin\\PlanController@edit');
        $r->put('/admin/plans/{id}', 'Admin\\PlanController@update', [$csrf]);
        $r->delete('/admin/plans/{id}', 'Admin\\PlanController@destroy', [$csrf]);

        // Usuarios (clientes y resellers)
        $r->get('/admin/users', 'Admin\\UserController@index');
        $r->get('/admin/users/create', 'Admin\\UserController@create');
        $r->post('/admin/users', 'Admin\\UserController@store', [$csrf]);
        $r->get('/admin/users/{id}/edit', 'Admin\\UserController@edit');
        $r->put('/admin/users/{id}', 'Admin\\UserController@update', [$csrf]);
        $r->delete('/admin/users/{id}', 'Admin\\UserController@destroy', [$csrf]);

        // Estaciones
        $r->get('/admin/stations', 'Admin\\StationController@index');
        $r->get('/admin/stations/create', 'Admin\\StationController@create');
        $r->post('/admin/stations', 'Admin\\StationController@store', [$csrf]);
        $r->get('/admin/stations/{id}', 'Admin\\StationController@show');
        $r->get('/admin/stations/{id}/edit', 'Admin\\StationController@edit');
        $r->put('/admin/stations/{id}', 'Admin\\StationController@update', [$csrf]);
        $r->delete('/admin/stations/{id}', 'Admin\\StationController@destroy', [$csrf]);
        $r->post('/admin/stations/{id}/start', 'Admin\\StationController@start', [$csrf]);
        $r->post('/admin/stations/{id}/stop', 'Admin\\StationController@stop', [$csrf]);
        $r->post('/admin/stations/{id}/restart', 'Admin\\StationController@restart', [$csrf]);
        $r->post('/admin/stations/{id}/settings', 'Client\\StationController@updateSettings', [$csrf]);
        $r->get('/admin/stations/{id}/relay', 'Client\\RelayController@index');
        $r->post('/admin/stations/{id}/relay', 'Client\\RelayController@update', [$csrf]);

        // AutoDJ (admin)
        $r->get('/admin/stations/{id}/autodj', 'Admin\\AutoDjController@index');
        $r->get('/admin/stations/{id}/autodj/playlists/{pid}', 'Admin\\AutoDjController@showPlaylist');
        $r->get('/admin/stations/{id}/autodj/tracks/{tid}/stream', 'Admin\\AutoDjController@streamTrack');
        $r->post('/admin/stations/{id}/autodj/upload', 'Admin\\AutoDjController@upload', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/tracks/{tid}/delete', 'Admin\\AutoDjController@deleteTrack', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/tracks/bulk-delete', 'Admin\\AutoDjController@bulkDeleteTracks', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/bulk-add', 'Admin\\AutoDjController@bulkAddTracks', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists', 'Admin\\AutoDjController@createPlaylist', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/update', 'Admin\\AutoDjController@updatePlaylist', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/delete', 'Admin\\AutoDjController@deletePlaylist', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/toggle', 'Admin\\AutoDjController@togglePlaylist', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/play', 'Admin\\AutoDjController@playPlaylist', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/clear', 'Admin\\AutoDjController@clearPlaylist', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/tracks', 'Admin\\AutoDjController@addTrack', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/playlists/{pid}/items/{itemId}/remove', 'Admin\\AutoDjController@removeItem', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/skip', 'Admin\\AutoDjController@skipTrack', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/broadcast-mic', 'Admin\\AutoDjController@broadcastMic', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/start', 'Admin\\AutoDjController@start', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/stop', 'Admin\\AutoDjController@stop', [$csrf]);
        $r->post('/admin/stations/{id}/autodj/bitrate', 'Admin\\AutoDjController@setBitrate', [$csrf]);

        // Analiticas (admin)
        $r->get('/admin/stations/{id}/analytics', 'Admin\\AnalyticsController@index');
        $r->get('/admin/stations/{id}/analytics/api', 'Admin\\AnalyticsController@apiData');

        // Facturas (M6)
        $r->get('/admin/invoices', 'Admin\\InvoiceController@index');
        $r->get('/admin/invoices/create', 'Admin\\InvoiceController@create');
        $r->post('/admin/invoices', 'Admin\\InvoiceController@store', [$csrf]);
        $r->post('/admin/invoices/{id}/pay', 'Admin\\InvoiceController@markPaid', [$csrf]);
        $r->delete('/admin/invoices/{id}', 'Admin\\InvoiceController@destroy', [$csrf]);
    });

    // ---------------------------------------------------------------
    // CLIENTE
    // ---------------------------------------------------------------
    $router->group([$auth, $client], function (Router $r) use ($csrf) {
        $r->get('/client', 'Client\\DashboardController@index');
        $r->get('/client/dashboard', 'Client\\DashboardController@index');
        $r->get('/client/stations/{id}', 'Client\\StationController@show');
        $r->post('/client/stations/{id}/start', 'Client\\StationController@start', [$csrf]);
        $r->post('/client/stations/{id}/stop', 'Client\\StationController@stop', [$csrf]);
        $r->post('/client/stations/{id}/restart', 'Client\\StationController@restart', [$csrf]);
        $r->post('/client/stations/{id}/settings', 'Client\\StationController@updateSettings', [$csrf]);
        $r->get('/client/invoices', 'Client\\InvoiceController@index');

        // AutoDJ (cliente)
        $r->get('/client/stations/{id}/autodj', 'Client\\AutoDjController@index');
        $r->get('/client/stations/{id}/autodj/playlists/{pid}', 'Client\\AutoDjController@showPlaylist');
        $r->get('/client/stations/{id}/autodj/tracks/{tid}/stream', 'Client\\AutoDjController@streamTrack');
        $r->post('/client/stations/{id}/autodj/upload', 'Client\\AutoDjController@upload', [$csrf]);
        $r->post('/client/stations/{id}/autodj/tracks/{tid}/delete', 'Client\\AutoDjController@deleteTrack', [$csrf]);
        $r->post('/client/stations/{id}/autodj/tracks/bulk-delete', 'Client\\AutoDjController@bulkDeleteTracks', [$csrf]);
        $r->post('/client/stations/{id}/autodj/bulk-add', 'Client\\AutoDjController@bulkAddTracks', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists', 'Client\\AutoDjController@createPlaylist', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/update', 'Client\\AutoDjController@updatePlaylist', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/delete', 'Client\\AutoDjController@deletePlaylist', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/toggle', 'Client\\AutoDjController@togglePlaylist', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/play', 'Client\\AutoDjController@playPlaylist', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/clear', 'Client\\AutoDjController@clearPlaylist', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/tracks', 'Client\\AutoDjController@addTrack', [$csrf]);
        $r->post('/client/stations/{id}/autodj/playlists/{pid}/items/{itemId}/remove', 'Client\\AutoDjController@removeItem', [$csrf]);
        $r->post('/client/stations/{id}/autodj/skip', 'Client\\AutoDjController@skipTrack', [$csrf]);
        $r->post('/client/stations/{id}/autodj/broadcast-mic', 'Client\\AutoDjController@broadcastMic', [$csrf]);
        $r->post('/client/stations/{id}/autodj/start', 'Client\\AutoDjController@start', [$csrf]);
        $r->post('/client/stations/{id}/autodj/stop', 'Client\\AutoDjController@stop', [$csrf]);
        $r->post('/client/stations/{id}/autodj/bitrate', 'Client\\AutoDjController@setBitrate', [$csrf]);

        // Analiticas (cliente)
        $r->get('/client/stations/{id}/analytics', 'Client\\AnalyticsController@index');
        $r->get('/client/stations/{id}/analytics/api', 'Client\\AnalyticsController@apiData');

        // Relay & Horarios (cliente)
        $r->get('/client/stations/{id}/relay', 'Client\\RelayController@index');
        $r->post('/client/stations/{id}/relay', 'Client\\RelayController@update', [$csrf]);
    });

    // ---------------------------------------------------------------
    // RESELLER
    // ---------------------------------------------------------------
    $router->group([$auth, new RoleMiddleware('reseller')], function (Router $r) use ($csrf) {
        $r->get('/reseller', 'Reseller\\DashboardController@index');
        $r->get('/reseller/dashboard', 'Reseller\\DashboardController@index');
        $r->get('/reseller/clients', 'Reseller\\ClientController@index');
        $r->get('/reseller/clients/create', 'Reseller\\ClientController@create');
        $r->post('/reseller/clients', 'Reseller\\ClientController@store', [$csrf]);
        $r->get('/reseller/stations', 'Reseller\\StationController@index');
        $r->get('/reseller/stations/{id}', 'Reseller\\StationController@show');
        $r->post('/reseller/stations/{id}/start', 'Reseller\\StationController@start', [$csrf]);
        $r->post('/reseller/stations/{id}/stop', 'Reseller\\StationController@stop', [$csrf]);
        $r->post('/reseller/stations/{id}/restart', 'Reseller\\StationController@restart', [$csrf]);
        $r->post('/reseller/stations/{id}/settings', 'Client\\StationController@updateSettings', [$csrf]);
        $r->get('/reseller/stations/{id}/relay', 'Client\\RelayController@index');
        $r->post('/reseller/stations/{id}/relay', 'Client\\RelayController@update', [$csrf]);

        // AutoDJ (reseller)
        $r->get('/reseller/stations/{id}/autodj', 'Reseller\\AutoDjController@index');
        $r->get('/reseller/stations/{id}/autodj/playlists/{pid}', 'Reseller\\AutoDjController@showPlaylist');
        $r->get('/reseller/stations/{id}/autodj/tracks/{tid}/stream', 'Reseller\\AutoDjController@streamTrack');
        $r->post('/reseller/stations/{id}/autodj/upload', 'Reseller\\AutoDjController@upload', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/tracks/{tid}/delete', 'Reseller\\AutoDjController@deleteTrack', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/tracks/bulk-delete', 'Reseller\\AutoDjController@bulkDeleteTracks', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/bulk-add', 'Reseller\\AutoDjController@bulkAddTracks', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists', 'Reseller\\AutoDjController@createPlaylist', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/update', 'Reseller\\AutoDjController@updatePlaylist', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/delete', 'Reseller\\AutoDjController@deletePlaylist', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/toggle', 'Reseller\\AutoDjController@togglePlaylist', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/play', 'Reseller\\AutoDjController@playPlaylist', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/clear', 'Reseller\\AutoDjController@clearPlaylist', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/tracks', 'Reseller\\AutoDjController@addTrack', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/playlists/{pid}/items/{itemId}/remove', 'Reseller\\AutoDjController@removeItem', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/skip', 'Reseller\\AutoDjController@skipTrack', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/start', 'Reseller\\AutoDjController@start', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/stop', 'Reseller\\AutoDjController@stop', [$csrf]);
        $r->post('/reseller/stations/{id}/autodj/bitrate', 'Reseller\\AutoDjController@setBitrate', [$csrf]);

        // Analiticas (reseller)
        $r->get('/reseller/stations/{id}/analytics', 'Reseller\\AnalyticsController@index');
        $r->get('/reseller/stations/{id}/analytics/api', 'Reseller\\AnalyticsController@apiData');
    });

    // ---------------------------------------------------------------
    // Perfil (cualquier usuario autenticado)
    // ---------------------------------------------------------------
    $router->group([$auth], function (Router $r) use ($csrf) {
        $r->get('/profile', 'ProfileController@show');
        $r->post('/profile/password', 'ProfileController@updatePassword', [$csrf]);
    });

    // ---------------------------------------------------------------
    // API (JSON, para refresco de estadisticas en vivo)
    // ---------------------------------------------------------------
    $router->group([$auth], function (Router $r) {
        $r->get('/api/stations/{id}/stats', 'Api\\StatsController@station');
        $r->get('/api/stations/{id}/history', 'Api\\StatsController@history');
    });
};
