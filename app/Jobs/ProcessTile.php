<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Tile;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class ProcessTile implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $tile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Tile $tile)
    {
        $this->tile = $tile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $client = new Client(); //GuzzleHttp\Client
        $result = $client->post(env('SATURN_URL').'guess', [
            'form_params' => [
                'urls' => url('/').'/'.$this->tile->image_url.';',
            ]
        ]);

        $json_out = json_decode($result->getBody());

        $this->tile->classification = $json_out->class;
        $this->tile->save();
    }
}
