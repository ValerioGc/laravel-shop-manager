<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Show extends Model
{
    use HasFactory;

    protected $fillable = [
        'label_ita',
        'label_eng',
        'start_date',
        'end_date',
        'description_ita',
        'description_eng',
        'link',
        'location',
        'image_id',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    protected $appends = [
        'formatted_updated_at',
        'image_url',
        'images_url',
    ];

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d/m/Y') : null;
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function images()
    {
        return $this->hasManyThrough(Image::class, ImageAssociation::class, 'entity_id', 'id', 'id', 'image_id')
            ->where('image_associations.type_entity', 1);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::url($this->image->path) : null;
    }

    public function getImagesUrlAttribute()
    {
        return $this->images->map(function ($image) {
            return Storage::url($image->path);
        });
    }
}
