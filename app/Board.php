<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    protected $fillable = ['name', 'cards'];

    public function getCardsAttribute($value)
    {
        return !empty($value) ? json_decode($value, true) : [];
    }

    public function setCardsAttribute($value)
    {
        $this->attributes['cards'] = json_encode($value);
    }
}
