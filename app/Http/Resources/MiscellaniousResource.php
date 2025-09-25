<?php

namespace App\Http\Resources;

use App\Models\Sessions;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MiscellaniousResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $session = Sessions::where('name',$this->session)->first();
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'amount'=>$this->amount,
            'check_no'=>$this->check_no,
            'MiscellaneousType'=>$this->MiscellaneousType,
            'payment_date'=>date('Y-m-d',strtotime($this->payment_date)),
            'current_date'=>date('Y-m-d',strtotime(Carbon::now())),
            'roll_no'=>$this->roll_no,
            'batch_number'=>$this->batch_number,
            'session'=>$this->session,
            'category'=>$this->category,
            'received_from'=>$this->received_from,
            'purpose_id'=>$this->purpose_id,
            'session_id'=>isset($session->session_id) ? $session->session_id : '',
            'purpose_name'=>isset($this->purpose) ? $this->purpose->name:'',
            'amount_in_word' => numberToWord($this->amount),
        ];
    }
}
