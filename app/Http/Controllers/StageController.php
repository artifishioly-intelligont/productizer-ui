<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Map;
use App\Tile;
use App\Jobs\ProcessTile;
use App\Jobs\BatchProcessTile;
use \File;
use \Image;
use \Input;
use App\Jobs\MakeTiles;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class StageController extends Controller
{
    use \Illuminate\Foundation\Bus\DispatchesJobs;
    public function makeTiles($image, $filename = null, $folder = null) {
        $command = new MakeTiles($image, $filename, $folder);
        $this->dispatch($command);
    }
    public function postStage1(Request $request) {
        $MAXDIMENSIONS = 4000;
        ini_set('max_execution_time', 300);
        $this->validate($request, [
            'image' => 'required|mimes:jpeg,jpg,png,tif,tiff|dimensions:max_width='.$MAXDIMENSIONS.',max_height='.$MAXDIMENSIONS,//,ratio=1/1 SQUARE,
        ]);

        $map = new Map;
        $map->save();
        $path = base_path().'/public/maps/'.$map->id;
        File::makeDirectory($path);
        // resize image
        // minimise edge stretching (temp fix) by checking if more excess on height or width
        $image = Image::make($request->file('image')->getRealPath());
        $height = $image->height();
        $heightAdded = $height + (128 - ($height % 128));

        $width = $image->width();
        $widthAdded = $width + (128 - ($width % 128));

        if($heightAdded > $widthAdded) {
            $image->resize(null, $heightAdded, function ($constraint) {
                    $constraint->aspectRatio();
                })
                ->encode('jpg', 100)
                ->save($path.'/actual.jpg');
        } else {
            $image->resize(null, $widthAdded, function ($constraint) {
                    $constraint->aspectRatio();
                })
                ->encode('jpg', 100)
                ->save($path.'/actual.jpg');
        }
        //Save thumbnail too
        Image::make($request->file('image')->getRealPath())
            ->resize(null, 100, function ($constraint) {
                $constraint->aspectRatio();
            })
            ->encode('jpg', 100)
            ->save($path.'/thumb.jpg');

        //$this->makeTiles($path.'/actual.jpg');

        //DO MANUALLY
        $this->deepzoom = DeepzoomFactory::create([
            'path'   => $path,
            'driver' => isset($config['driver']) ? $config['driver'] : config('deepzoom.driver'),
            'format' => isset($config['tile_format']) ? $config['tile_format'] : config('deepzoom.tile_format'),
        ]);

        $this->deepzoom->makeTiles($path.'/actual.jpg', $map);

        // save image to database
        $map->file = $path.'/actual.jpg';
        $map->save();

        return redirect()->to('map/'.$map->id);

    }

    public function showStage2($id) {
        ini_set("allow_url_fopen", 1);
        $json = file_get_contents(env('SATURN_URL').'/features');
        $obj = json_decode($json);
        $features = ['-'];
        if($obj->success == true) {
            $features = $obj->features;
        }
        $map = Map::findOrFail($id);
        return view('map')->withMap(Map::findOrFail($id))->withFeatures($features)
        ->withTiles(Tile::where('map_id', $id)->where('level', ($map->levels - 1))->get())
        ->withCurrent(Tile::where('map_id', $id)->where('level', ($map->levels - 1))->where('classification', '!=', null)->count());
    }

    public function requeue($id) {
        $map = Map::findOrFail($id);
        $batchsize = 14;
        $tiles = Tile::where('map_id', $id)->where('level', ($map->levels - 1))->get();

        for ($i=0; $i < $tiles->count(); $i+=$batchsize) { 
            $slice = $tiles->slice($i, $batchsize);
            foreach ($tiles as $tile) {
                $tile->classification = null;
                $tile->save();
            }
            $job = (new BatchProcessTile($slice))
            ->onConnection('sqs');
            dispatch($job);
        }
        return redirect()->route('stage1', $id)->with('guess', true);
    }

    public function postStage2($id, Request $request) {
        if(!($request->has('mode') && ($request->has('learn-files') || $request->has('guess-files')))) {
            return redirect()->back();
        }
        $mode = $request->get('mode');
            if($mode == 'learn') {
                $files = $request->get('learn-files');
                //$files = explode(';', $files);
                //array_pop($files);

                $client = new Client(); //GuzzleHttp\Client
                $result = $client->post(env('SATURN_URL').'/learn', [
                    'form_params' => [
                        'theme' => $request->get('selected-feature'),
                        'urls' => $files,
                    ]
                ]);
                dd($result->body());
                // TODO: make it go to guess mode with info
                return redirect()->back();


            } else if ($mode == 'guess') {
                $files = $request->get('guess-files');
                $filesarr = explode(';', $files);
                //array_pop($files);

                $client = new Client(); //GuzzleHttp\Client
                $result = $client->post(env('SATURN_URL').'/guess', [
                    'form_params' => [
                        'urls' => $files,
                    ]
                ]);

                $json_out = json_decode($result->getBody());

                // TODO: make it go to guess mode with info
                return redirect()->back()->with('guess', true)->with('class', $json_out->class)->with('image', $filesarr[0]);

            }
        return redirect()->back();
    }

    public function mapHistory() {
        return view('history')->withMaps(Map::orderBy('created_at', 'desc')->paginate(20));
    }
}
