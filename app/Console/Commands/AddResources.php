<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;

class AddResources extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'add-resources';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Insert music and sticker resources urls into databases ';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $this->addStickerResources();
    }

    private function addMusicResources()
    {
        $musicImages = [
            'music1@2x.png',
            'music2@2x.png',
            'music3@2x.png',
            'music4@2x.png',
            'music5@2x.png',
            'music6@2x.png',
            'music7@2x.png',
            'music8@2x.png',
            'music9@2x.png',
            'music10@2x.png',
            'music11@2x.png',
            'music12@2x.png',
            'music13@2x.png',
            'music14@2x.png',
            'music15@2x.png',
            'music16@2x.png',
            'music17@2x.png',
        ];
        foreach (Resource::all() as $index => $resource) {
            $resource->update(['logo_key' => $musicImages[$index]]);
            $this->info($resource);
        }

    }

    /**
     *
     */
    private function addStickerResources()
    {
        $stickerResources    = [
            ['waterImage_image_image_1@2x.png', 'waterImage_image_1@2x.png',],
            ['waterImage_image_image_2@2x.png', 'waterImage_image_2@2x.png'],
            ['waterImage_image_image_3@2x.png', 'waterImage_image_3@2x.png'],
            ['waterImage_image_image_4@2x.png', 'waterImage_image_4@2x.png'],
            ['waterImage_image_image_5@2x.png', 'waterImage_image_5@2x.png',],
            ['waterImage_image_image_6@2x.png', 'waterImage_image_6@2x.png',],
            ['waterImage_image_image_7@2x.png', 'waterImage_image_7@2x.png',],
            ['waterImage_image_image_8@2x.png', 'waterImage_image_8@2x.png',],
            ['waterImage_image_image_9@2x.png', 'waterImage_image_9@2x.png',],
            ['waterImage_image_image_10@2x.png', 'waterImage_image_10@2x.png',],
            ['waterImage_image_image_11@2x.png', 'waterImage_image_11@2x.png',],
            ['waterImage_location_image.png', 'waterImage_location@2x.png',],
            ['waterImage_text_image_1.png', 'waterImage_text_1@2x.png',],
            ['waterImage_text_image_2_1.png', 'waterImage_text_2@2x.png',],
            ['waterImage_text_image_2.png', 'waterImage_text_2@2x.png',],
            ['waterImage_text_image_3.png', 'waterImage_text_3@2x.png',],
            ['waterImage_text_image_4_1.png', 'waterImage_text_4@2x.png',],
            ['waterImage_text_image_4_2.png', 'waterImage_text_4@2x.png',],
            ['waterImage_text_image_5_1.png', 'waterImage_text_5@2x.png',],
            ['waterImage_text_image_5_2.png', 'waterImage_text_5@2x.png',],
        ];
        $stickerBtnResources = [
            'waterImage_weather@2x.png',
        ];
        foreach ($stickerResources as $resource) {
            $resource = Resource::create([
                'file_key' => $resource[0],
                'logo_key' => $resource[1],
                'type'     => Resource::TYPE_STICKER
            ]);
            $this->info($resource);
        }
    }
}
