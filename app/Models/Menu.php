<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Menu extends Model
{
    use HasFactory;
    protected $table = "menu";
    public $primaryKey = 'MenuID';
    protected $guarded = [];

    public function menuItem(){
        return $this->hasMany(MenuItem::class,'MenuID','MenuID');
    }
}
