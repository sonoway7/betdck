<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSlots extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_slots';
    protected $primaryKey = 'iduser';

    public $timestamps = false;

    protected $fillable = [
        'iduser',
        'idusergold',
        'token'
    ];
     
}
