<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $info = [
            'id' => $this->ulid,
            'name' => $this->name,
            'is_active' =>$this->active,
            'image' => $this->media?->path ? Storage::disk('public')->url('uploads/category/'."$this->id/images/".$this->media->path) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        if( $this->relationLoaded('products')){
            $info['products'] = $this->whenLoaded('products', function () {
                return ProductResource::collection($this->products);
            });
        }
        return $info;
    }
}
