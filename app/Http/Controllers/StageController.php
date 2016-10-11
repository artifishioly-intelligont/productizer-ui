<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Map;
use \File;
use \Image;
use \Input;
use App\Jobs\MakeTiles;

use Jeremytubbs\Deepzoom\DeepzoomFactory;

class StageController extends Controller
{
    use \Illuminate\Foundation\Bus\DispatchesJobs;

    public function makeTiles($image, $filename = null, $folder = null) {
        $command = new MakeTiles($image, $filename, $folder);
        $this->dispatch($command);
    }
    public function postStage1(Request $request) {
        $this->validate($request, [
            'image' => 'required|image|dimensions:max_width=2000,max_height=2000',//,ratio=1/1 SQUARE,
        ]);

        $map = new Map;
        $map->save();
        $path = base_path().'/public/maps/'.$map->id;
        File::makeDirectory($path);
        // resize image
        Image::make($request->file('image')->getRealPath())
            ->encode('jpg', 100)
            ->save($path.'/actual.jpg');

        //$this->makeTiles($path.'/actual.jpg');

        //DO MANUALLY
        $this->deepzoom = DeepzoomFactory::create([
            'path'   => $path,
            'driver' => isset($config['driver']) ? $config['driver'] : config('deepzoom.driver'),
            'format' => isset($config['tile_format']) ? $config['tile_format'] : config('deepzoom.tile_format'),
        ]);

        $levels = $this->deepzoom->makeTiles($path.'/actual.jpg');

        // save image to database
        $map->file = $path.'/actual.jpg';
        $map->levels = $levels;
        $map->save();

        return redirect()->to('map/'.$map->id);

    }

    public function showStage2($id) {
        return view('map')->withMap(Map::findOrFail($id));
    }
}
