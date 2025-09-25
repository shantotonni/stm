<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'user_type' => $this->user_type,
            'role'  => isset($this->role) ? $this->role->name: '',
            'is_head'  => isset($this->teacher) ? $this->teacher->is_head: '',
        ];
    }
}
