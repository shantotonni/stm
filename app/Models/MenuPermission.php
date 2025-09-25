<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MenuPermission extends Model
{
    use HasFactory;

    protected $table = "MenuPermission";
    public $timestamps = false;
    public $primaryKey = false;
    public $incrementing = false;
    protected $keyType = "string";
    protected $guarded = [];

    public function subMenus() {
        return $this->hasMany(SubMenu::class,'MenuID','MenuID')
            ->join('SubMenuPermission','SubMenuPermission.SubMenuID','SubMenus.SubMenuID')
            ->where('UserID',Auth::user()->StaffID)->where('Status',1)->orderBy('SubMenuOrder');
    }

    
}
