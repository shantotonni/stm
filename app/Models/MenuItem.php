<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;
    protected $table = "menuitem";
    public $primaryKey = 'Id';
    protected $guarded = [];

    public function menu(){
        return $this->belongsTo(Menu::class,'MenuID','MenuID');
    }
}
