<?php

namespace App\Models;

use App\Models\Show;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ImageAssociation extends Model
{
    protected $fillable = [
        'image_id',
        'type_entity',
        'entity_id',
        'order'
    ];

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'entity_id')->where('type_entity', 0);
    }

    public function show()
    {
        return $this->belongsTo(Show::class, 'entity_id')->where('type_entity', 1);
    }
}

