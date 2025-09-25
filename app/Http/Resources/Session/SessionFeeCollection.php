<?php

namespace App\Http\Resources\Session;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SessionFeeCollection extends ResourceCollection
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
            'data'=>$this->collection->transform(function ($session_fees){
                return [
                    'session_fee_id'=>$session_fees->session_fee_id,
                    'session_id'=>$session_fees->session_id,
                    'year_id'=>$session_fees->year_id,
                    'category_id'=>$session_fees->category_id,
                    'amount'=>$session_fees->amount,
                    'session_name'=>isset($session_fees->session) ? $session_fees->session->name:'',
                    'year_name'=>isset($session_fees->year) ? $session_fees->year->name:'',
                    'category_name'=>isset($session_fees->category) ? $session_fees->category->name:'',
                ];
            })
        ];
    }
}
