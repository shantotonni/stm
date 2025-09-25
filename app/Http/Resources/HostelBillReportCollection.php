<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class HostelBillReportCollection extends ResourceCollection
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
            'data'=>$this->collection->transform(function ($hostel_fee){
                return [
                    'id'=>$hostel_fee->id,
                    'received_from'=>$hostel_fee->received_from,
                    'CategoryName'=>$hostel_fee->CategoryName,
                    'SessionName'=>$hostel_fee->SessionName,
                    'batch_number'=>$hostel_fee->batch_number,
                    'currency'=>$hostel_fee->currency,
                    'roll_no'=>$hostel_fee->roll_no,
                    'amount'=>$hostel_fee->amount,
                ];
            })
        ];
    }
}
