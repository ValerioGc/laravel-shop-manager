<?php

namespace App\Models;

use App\Models\Show;
use App\Models\Product;
use App\Models\ImageAssociation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'order' 
    ];

    public function associations()
    {
        return $this->hasMany(ImageAssociation::class);
    }

    public function products()
    {
        return $this->hasManyThrough(Product::class, ImageAssociation::class, 'image_id', 'id', 'id', 'entity_id')->where('image_associations.type_entity', 0);
    }

    public function shows()
    {
        return $this->hasManyThrough(Show::class, ImageAssociation::class, 'image_id', 'id', 'id', 'entity_id')->where('image_associations.type_entity', 1);
    }
}
