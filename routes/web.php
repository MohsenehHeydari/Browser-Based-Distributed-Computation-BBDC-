<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => '{type}'], function () {
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Auth\LoginController@login');
    Route::post('logout', 'Auth\LoginController@logout')->name('logout');
    Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
    Route::post('register', 'Auth\RegisterController@register')->middleware('throttle:30,5');
});

Route::get('/', function () {
    return view('welcome');
});


Route::get('/get_token', function () {
    return csrf_token();
});

Route::post('/submit', 'RegisterController@addWorkerDevice');



// Route::get('/dataRequest/{id}','DataController@getData');


Route::get('/home', 'HomeController@index')->name('home');

Route::get('test/{f?}', 'TestController@index');

Route::get('/panel/{type}', 'PanelController@index')->name('panel')->middleware('auth');

//worker group routes
Route::group(['prefix' => 'worker', 'middleware' => 'auth'], function () {
    Route::get('/taskRequest/{id}', 'TaskController@getTask')->middleware('bandwidth_assessment')->name('getTask');
    Route::post('/sendResult', 'DataController@sendResult')->middleware('bandwidth_assessment')->name('sendResult');

    // job group routes
    Route::group(['prefix' => 'jobs'], function () {
        Route::get('/list', 'JobController@listJobs');
    });

    // device group routes
    Route::group(['prefix' => 'devices', 'middleware' => 'auth'], function () {
        Route::get('/list', 'DeviceController@list');
        Route::put('/edit/{id}', 'DeviceController@update');
        Route::get('/edit/{id}', 'DeviceController@edit');
        Route::delete('/delete/{id}', 'DeviceController@delete');
        Route::post('/add', 'DeviceController@add');
    });
});

//owner job group routes
Route::group(['prefix' => 'owner', 'middleware' => 'auth'], function () {

    Route::group(['prefix' => 'owner_jobs', 'middleware' => 'auth'], function () {
        Route::get('/list', 'OwnerJobController@list');
        Route::get('/getJobs', 'OwnerJobController@getJobs');
        Route::put('/edit/{id}', 'OwnerJobController@update');
        Route::get('/edit/{id}', 'OwnerJobController@edit');
        Route::delete('/delete/{id}', 'OwnerJobController@delete');
        Route::post('/create', 'OwnerJobController@create')->name('OwnerJob.create');
    });
});


Route::post('/set-cookie', 'AppController@setCookie')->name('set-cookie');
Route::get('/get-cookie/{name}', 'AppController@getCookie')->name('get-cookie');

Route::post('/test', function (Request $request) {
    // sleep(10);
    return '';
});




