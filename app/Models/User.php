<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = "users";
    public $primaryKey = 'id';
    protected $fillable = [
        'login_code',
        'name',
        'password',
        'mobile',
        'role_id',
        'avatar',
        'is_active',
        'user_type',
        'is_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getIsActiveAttribute($value)
    {
        return $value === 'Y';
    }

    public function setIsActiveAttribute($value)
    {
        $this->attributes['is_active'] = $value ? 'Y' : 'N';
    }

    public function getIsChangePasswordAttribute($value)
    {
        return $value === 'N';
    }

    public function setIsChangePasswordAttribute($value)
    {
        $this->attributes['is_change_password'] = 'N';
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->name,
            'user_type' => $this->user_type,
            'permissions' => $this->getAllPermissions()
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function department(){
        return $this->belongsTo(Department::class,'department_id','id');
    }

    public function designation(){
        return $this->belongsTo(Designation::class,'designation_id','id');
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function isStudent()
    {
        return $this->user_type === 'student';
    }

    public function isTeacher()
    {
        return $this->user_type === 'teacher';
    }

    public function isAdmin()
    {
        return $this->user_type === 'admin';
    }

    public function hasPermission($permission)
    {
        return $this->role->permissions->contains('name', $permission);
    }

    public function getAllPermissions()
    {
        return $this->role->permissions->pluck('name')->toArray();
    }

    public function can($permission, $arguments = [])
    {
        return $this->hasPermission($permission);
    }

    // Get user's accessible menus
    public function getAccessibleMenus()
    {

        return Menu::getMenuTree($this->id);
    }

}
