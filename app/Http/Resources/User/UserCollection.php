<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($user){
                return [
                    'user_id'=>$user->user_id,
                    'role_id'=>$user->role_id,
                    'name'=>$user->name,
                    'email'=>$user->email,
                    'mobile'=>$user->mobile,
                    'status'=>$user->status,
                    'role'=>isset($user->role) ? $user->role->name:'',
                ];
            })
        ];
    }
}
