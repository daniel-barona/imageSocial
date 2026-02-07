<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //
    /*use HasFactory;
    //indicar cual es la tabala a la cual se conecta este modelo
    protected $table = 'images';
    //relacion de uno a muchos
    //creamos el metodo
    public function comments(){
    return $this->hasMany('App\Models\Comment')->orderBy('id','desc');
    }
    //relacion de uno a muchos
    //creamos el metodo
    public function likes(){
    return $this->hasMany('App\Models\Like');
    }
    //relacion de muchos a uno
    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }*/
    protected $fillable = [
        'user_id',
        'description',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    
    public function likes()
    {
        return $this->hasMany('App\Models\Like');
    }

    public function likedByUser($userId)
    {
        return $this->likes->contains('user_id', $userId);
    }
    public function comments()
    {
        return $this->hasMany('App\Models\Comment')->orderBy('id', 'desc');
    }
}
