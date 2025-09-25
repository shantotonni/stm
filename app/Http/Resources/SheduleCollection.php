<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SheduleCollection extends ResourceCollection
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
            'data'=>$this->collection->transform(function ($schdule){
                $expected_payment_year = isset($schdule->session) ? $schdule->session->from_period: '';
                $expected_payment_year = str_split($expected_payment_year,4);
                $date_formating = $expected_payment_year[0].$expected_payment_year[1].'01';
                $final_expected_date = date('Y-m-d',strtotime($date_formating));
                if ($schdule->year->year_serial == 2){
                    $final_expected_date = date('Y-m-d',strtotime($final_expected_date . " +1 year"));
                }elseif ($schdule->year->year_serial == 3){
                    $final_expected_date = date('Y-m-d',strtotime($final_expected_date . " +2 year"));
                }elseif ($schdule->year->year_serial == 4){
                    $final_expected_date = date('Y-m-d',strtotime($final_expected_date . " +3 year"));
                }elseif ($schdule->year->year_serial == 5){
                    $final_expected_date = date('Y-m-d',strtotime($final_expected_date . " +4 year"));
                }

                return [
                    'session_fee_id'=>$schdule->session_fee_id,
                    'session_id'=>$schdule->session_id,
                    'session'=>isset($schdule->session) ? $schdule->session->name: '',
                    'expected_payment_date'=> $final_expected_date,
                    'year_id'=>$schdule->session_id,
                    'year'=>isset($schdule->year) ? $schdule->year->name: '',
                    'ordering'=>isset($schdule->year) ? $schdule->year->ordering: '',
                    'category_id'=>$schdule->session_id,
                    'category'=>isset($schdule->category) ? $schdule->category->name: '',
                    'amount'=>$schdule->amount,
                    'currency'=>$schdule->category->currency->name,
                    'currency_symbol'=>$schdule->category->currency->symbol,
                ];
            })
        ];
    }
}
