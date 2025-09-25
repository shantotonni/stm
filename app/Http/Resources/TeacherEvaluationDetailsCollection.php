<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TeacherEvaluationDetailsCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($details){
                return [
                    'id'=>$details->id,
                    'details'=>$details->details,
                ];
            })
        ];
    }
}
