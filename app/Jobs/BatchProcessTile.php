<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use App\Tile;
use Log;
use Pubnub\Pubnub;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class BatchProcessTile implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $tiles;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($t)
    {
        $this->tiles = collect($t);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $urls = "";
        foreach($this->tiles as $tile) {
            $urls = $urls.(url('/').'/'.($tile->image_url).';');
        }
        $client = new Client(); //GuzzleHttp\Client
        $result = $client->post(env('SATURN_URL').'/find', [
            'form_params' => [
                'urls' => $urls
            ]
        ]);

        $json_out = json_decode($result->getBody());
        $matching = json_decode(json_encode($json_out->matching_urls), true);
        $pubnub = new Pubnub(env('PUBNUB_PUB'), env('PUBNUB_SUB'));
        foreach ($this->tiles as $tile) {
            $midUrl = url('/').'/'.($tile->image_url)."#mid";
            if(array_key_exists($midUrl, $matching)) {
                $tile->classification = $matching[$midUrl];
                $tile->save();
                $publish_result = $pubnub->publish('map'.($tile->map_id), $tile->toJson());
            } else {
                $job = (new ProcessTile($tile))
                ->onConnection('sqs');
                dispatch($job);
            }
        }
    }
}
