<?php

namespace App\Http\Resources;

use App\Models\Sessions;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MiscellaniousCollection extends ResourceCollection
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
            'data'=>$this->collection->transform(function ($misce){
                $session = Sessions::where('name',$misce->session)->first();
                return [
                    'id'=>$misce->id,
                    'title'=>$misce->title,
                    'amount'=>$misce->amount,
                    'check_no'=>$misce->check_no,
                    'MiscellaneousType'=>$misce->MiscellaneousType,
                    'payment_date'=>date('d-m-Y',strtotime($misce->payment_date)),
                    'roll_no'=>$misce->roll_no,
                    'batch_number'=>$misce->batch_number,
                    'session'=>$misce->session,
                    'category'=>$misce->category,
                    'received_from'=>$misce->received_from,
                    'purpose_id'=>$misce->purpose_id,
                    'session_id'=>isset($session->session_id) ? $session->session_id : '',
                    'purpose_name'=>isset($misce->purpose) ? $misce->purpose->name:'',
                    'amount_in_word' => numberToWord($misce->amount),
                ];
            })
        ];
    }
}
