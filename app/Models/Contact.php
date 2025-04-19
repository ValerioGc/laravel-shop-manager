<?php

namespace App\Models;

use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Contact extends Model
{
    protected $fillable = [
        'label_ita',
        'label_eng',
        'link_value',
        'image_id'
    ];

    protected $appends = [
        'formatted_updated_at',
        'image_url',
    ];

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d/m/Y') : null;
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id', 'id');
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::url($this->image->path) : null;
    }
}
