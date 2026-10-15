<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'item_number',
        'name',
        'brand',
    ];

    public function specifications() {
        return $this->hasMany('App\Models\ItemSpecification');
    }

    public function pictures() {
        return $this->hasMany('App\Models\ItemPicture')->orderBy('id');
    }

    public function auctions() {
        return $this->hasMany('App\Models\Auction');
    }
}
