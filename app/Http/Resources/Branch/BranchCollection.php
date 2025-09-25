<?php

namespace App\Http\Resources\Branch;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BranchCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($branch){
                return [
                    'id'=>$branch->id,
                    'bank_id'=>$branch->bank_id,
                    'name'=>$branch->name,
                    'account_no'=>$branch->account_no,
                    'bank_name'=>isset($branch->bank) ? $branch->bank->name:'',
                ];
            })
        ];
    }
}
