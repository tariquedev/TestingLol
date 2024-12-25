<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'files' => $this->getFiles(),
            'url'   => $this->getUrls(),
        ];
    }

    private function getFiles()
    {
        return $this->files->map(function($file){
            return [
                'file_url' => $file->file_url
            ];
        });
    }

    private function getUrls()
    {
        return $this->urls->map(function($url){
            return [
                'title' => $url->title,
                'url'   => $url->url,
            ];
        });
    }
}
