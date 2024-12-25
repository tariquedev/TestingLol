<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     return parent::toArray($request);
    // }

    public static function storeImage(Request $request, $collectionName = 'default')
    {
        $rules = [
            $collectionName => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'message' => "Something went wrong",
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file($collectionName);
        $uniqueFileName = $file->hashName(); // Generate a unique file name

        // Store the uploaded image in the specified collection
        $media = Media::create([
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'uuid' => (string) Str::uuid(),
            'size' =>  $file->getSize(),
            'file_name' => $uniqueFileName,
            'mime_type' => $file->getMimeType(),
            'disk' => 's3',
            'conversions_disk' => 'public',
            'collection_name' => $collectionName,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);
        $path = Storage::disk('s3')->putFileAs("media/{$media->id}", $file, $uniqueFileName);

        return response()->json([
            'success' => 'Image Uploaded Successfully',
            "data" => [
                'media_id' => $media->id,
                'path' => $path,
                'media_url' => route('media.show', [$media->id, $media->file_name]), // Proxy URL
            ],
        ]);
    }

    /**
     * Associates an existing image with a model (e.g., Product) in a specified collection.
     */
    public static function associateImageWithModel(int $mediaId, $model, $collectionName)
    {
        $media = Media::findOrFail($mediaId);
        $media->model_id = $model->id;
        $media->model_type = get_class($model);
        $media->collection_name = $collectionName;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Image associated successfully',
            'collection' => $collectionName,
        ]);
    }

    /**
     * Deletes an image by media ID and collection name.
     */
    public static function deleteImage(int $mediaId, $collectionName = 'default')
    {
        $media = Media::where('id', $mediaId)
            // ->where('collection_name', $collectionName)
            ->first();

        if ($media) {
            $media->delete();
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully from ' . $collectionName,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image not found in ' . $collectionName,
        ], 404);
    }

}
