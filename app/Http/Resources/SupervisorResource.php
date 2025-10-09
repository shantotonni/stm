<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupervisorResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'designation' => $this->designation,
            'department' => [
                'id' => $this->department->id ?? null,
                'name' => $this->department->name ?? null,
                'code' => $this->department->code ?? null,
            ],
            'supervisor_info' => [
                'role' => $this->pivot->role,
                'assigned_at' => $this->pivot->assigned_at,
                'notes' => $this->pivot->notes,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
