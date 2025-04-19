<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'code',
        'quantity',
        'label_ita',
        'label_eng',
        'year',
        'description_ita',
        'description_eng',
        'price',
        'creator',
        'condition_id',
        'in_evidence',
        'deleting',
        'draft'
    ];

    protected $appends = [
        'formatted_updated_at',
        'picture_url',
        'images_url'
    ];

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d/m/Y') : null;
    }

    public function getPictureUrlAttribute()
    {
        return $this->images->isNotEmpty() ? Storage::url($this->images->first()->path) : null;
    }

    public function getImagesUrlAttribute()
    {
        return $this->images->map(function ($image) {
            return Storage::url($image->path);
        });
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, CategoryProduct::class, 'product_id', 'category_id');
    }

    public function imageAssociations()
    {
        return $this->hasMany(ImageAssociation::class)->where('type_entity', 0);
    }

    public function images()
    {
        return $this->hasManyThrough(Image::class, ImageAssociation::class, 'entity_id', 'id', 'id', 'image_id')->where('image_associations.type_entity', 0);
    }
}


