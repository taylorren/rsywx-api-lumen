<?php

/** @var \Laravel\Lumen\Routing\Router $router */
/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */
$router->get('/', function () use ($router) {
    return redirect()->to('/doc/index.html');
});
$router->get('/about', function () use ($router) {
    $ver = $router->app->version();
    $name = config('app.name');

    return response()->json([
        'Name' => $name,
        'Author' => 'TR@SOE',
        'Email' => 'taylor.ren@gmail.com',
        'Website' => 'https://rsywx.net',
        'Version' => '2.0',
        'Lumen Version' => $ver,
    ]);
});

$router->group(['prefix' => 'books'], function () use ($router) {
    $router->get('/', 'BookController@summary');
    $router->get('/latest/{count}', 'BookController@latest');
    $router->get('/{bookid:[0-9]{5}}', [
        'as' => 'detail',
        'uses' => 'BookController@detail',
    ]);
    $router->get('/image/{bookid}/{author}/{title}/{size}', [
        'as' => 'cover',
        'uses' => 'BookController@cover',
    ]);
    $router->get('/random[/{count}]', 'BookController@random');
    $router->get('/today/{m}/{d}', 'BookController@today');
    $router->get('/tags/{bookid}', 'BookController@tags');
    $router->get('/reviews/{bid}', 'BookController@reviews');
    $router->post('/addtag', 'BookController@addTag');
    $router->get('/list/{type}/{key}/{page}', 'BookController@list');
    $router->get('/hot[/{count}]', 'BookController@hot');
    $router->get('/tagcount', 'BookController@tagCount');
});

$router->group(['prefix' => 'readings'], function () use ($router) {
    $router->get('/latest/{count}', 'ReadController@latest');
    $router->get('/', 'ReadController@summary');
    $router->get('/{page}', 'ReadController@list');
});

$router->group(['prefix' => 'blogs'], function () use ($router) {
    $router->get('/latest/{count}', 'BlogController@latest');
    $router->get('/today', 'BlogController@blogsToday');
    
});

$router->group(['prefix'=>'admin'], function() use ($router) {
    $router->get('/visit[/{days}]', 'AdminController@visit');
    $router->get('/topbooks[/{count}]', 'AdminController@getTopBooks');
    $router->get('/bottombooks[/{count}]', 'AdminController@getBottomBooks');
    $router->get('/recentbooks[/{count}]', 'AdminController@getRecentBooks');
    $router->get('/forgottenbooks[/{count}]', 'AdminController@getForgottenBooks');
    
});

$router->get('/qotd', 'MiscController@qotd');
$router->get('/weather', 'MiscController@weather');
$router->get('/lakers', 'MiscController@lakers');
$router->get('/lakers/season', 'MiscController@season');


