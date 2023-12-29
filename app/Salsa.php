<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Salsa extends Model 
{
    protected $table = 'salsa_games';

    protected $fillable = ['game', 'pn', 'lang', 'url_dev'];

    protected $hidden = ['created_at', 'updated_at'];
}