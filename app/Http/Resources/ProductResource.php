<?php

namespace App\Http\Resources;

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $is_admin = auth()->guard('api')->check();
        $allMedia = ['images' => [], 'videos' => []];
        if(!empty($this->media)){
            foreach ($this->media as $media){
                $allMedia[$media->type][] = ['id' => $media->id, 'url' => Storage::disk('public')->url('uploads/products/'.$this->id.'/'.$media->type.'/'.$media->path)];
            }
        }
        $for_user = [
            'id' => $this->ulid,
            'name' => $this->name,
            'description' => $this->description,
            'price' =>$this->price,
            'custom_tailoring' => $this->custom_tailoring,
            'visitor' => $this->visitor,
            'orders_count' => $this->orders,
            'colors' => $this->colors,
            'sizes' => $this->sizes,
            'categories' => CategoryResource::collection($this->categories),
            'images' => $allMedia['images'],
            'videos' => $allMedia['videos'],

        ];
        $more_info = [
            'is_active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
        return $is_admin ? array_merge($for_user, $more_info) : $for_user;
    }
}
