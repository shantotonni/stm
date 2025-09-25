<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TeacherCollection extends ResourceCollection
{
    public function toArray($request)
    {
      return [
        'data'=>$this->collection->transform(function ($user){
            return [
                'user_id'=>$user->user_id,
                'name'=>$user->name,
                'BMDC_NO'=>$user->BMDC_NO,
                'is_head'=>$user->is_head,
                'email'=>$user->email,
                'mobile'=>$user->mobile,
                'status'=>$user->status,
                'role_id'=>$user->role_id,
                'role'=>isset($user->role) ? $user->role->name:'',
                'department_id'=>$user->department_id,
                'department_name'=>isset($user->department) ? $user->department->name:'',
                'designation_id'=>$user->designation_id,
                'designation_name'=>isset($user->designation) ? $user->designation->name:'',
            ];
        })
     ];
    }
}
