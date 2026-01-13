<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\WithMediaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            $product->ulid = (string) Str::ulid();
        });
        static::addGlobalScope(ActiveScope::class);
        static::addGlobalScope(WithMediaScope::class);
    }


    protected $casts = [
        'colors' => 'array',
        'sizes' => 'array',
    ];

    protected $fillable = [
        'name' ,
        'price' ,
        'custom_tailoring',
        'colors',
        'sizes' ,
        'category_id',
        'visitor',
        'ordered'
    ];
    public function media():HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function category():BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
