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
        $MAXDIMENSIONS = 4000;
        ini_set('max_execution_time', 300);
        $this->validate($request, [
            'image' => 'required|image|dimensions:max_width='.$MAXDIMENSIONS.',max_height='.$MAXDIMENSIONS,//,ratio=1/1 SQUARE,
        ]);

        $map = new Map;
        $map->save();
        $path = base_path().'/public/maps/'.$map->id;
        File::makeDirectory($path);
        // resize image
        $height = Image::make($request->file('image')->getRealPath())->height();
        $heightAdded = $height + (256 - ($height % 256));
        Image::make($request->file('image')->getRealPath())
            ->resize($heightAdded, null, function ($constraint) {
                $constraint->aspectRatio();
            })
            ->encode('jpg', 100)
            ->save($path.'/actual.jpg');

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
        return view('map')->withMap(Map::findOrFail($id));
    }
}
