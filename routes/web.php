<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', ['as' => 'home', 'uses' => 'HomeController@index']);
Route::post('/', ['as' => 'stage1_post', 'uses' => 'StageController@postStage1']);

Route::get('/history', ['as' => 'history', 'uses' => 'StageController@mapHistory']);
Route::get('/map/{id}', ['as' => 'stage1', 'uses' => 'StageController@showStage2']);
Route::post('/map/{id}', ['as' => 'stage2_post', 'uses' => 'StageController@postStage2']);

Route::get('/requeue/{id}', ['as' => 'requeue', 'uses' => 'StageController@requeue']);


Route::get('/clearsqs', function() {

    $client = Aws\Sqs\SqsClient::factory(array(
                    'credentials' => array(
                        'key'    => config('queue.connections.sqs.key'),
                        'secret' => config('queue.connections.sqs.secret')
                    ),
                    'region' => config('queue.connections.sqs.region'),
                    'version' => '2012-11-05'
            ));
    $client->purgeQueue(array(
        // QueueUrl is required
        'QueueUrl' => config('queue.connections.sqs.prefix').'/'.config('queue.connections.sqs.queue'),
    ));
    return redirect('/');
});