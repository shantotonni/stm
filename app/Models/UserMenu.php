<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMenu extends Model
{
    use HasFactory;
    protected $table = "usermenu";
    protected $primaryKey = 'MenuID';
    protected $guarded = [];
    public $timestamps = false;
}
