<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'label_ita',
        'label_eng',
        'answer_ita',
        'answer_eng'
    ];
    
    protected $appends = [
        'formatted_updated_at',
    ];

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d/m/Y') : null;
    }
}

