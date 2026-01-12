<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;
    protected $fillable = [
        'type',
        'path',
        'product_id'
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
