<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPicture extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'item_id',
        'title',
        'path'
    ];

    public function item() {
        return $this->belongsTo('App\Models\Item');
    }

    /**
     * Public URL of one of the saved sizes: "large" (800x700 max) or "small" (100x100).
     */
    public function url(string $size = 'large'): string
    {
        return asset($this->path.'/'.$size.'.jpg');
    }
}
