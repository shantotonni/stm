<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstatementMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $data;
    public $name;
    public $staffId;
    public $department;
    public $designation;
    public $closingDate;

    public function __construct($data,$name,$staffId,$department,$designation,$closingDate)
    {
        $this->data = $data;
        $this->name = $name;
        $this->staffId = $staffId;
        $this->department = $department;
        $this->designation = $designation;
        $this->closingDate = $closingDate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('no-reply@aci-bd.com')->subject('AMS E-Statement Report')->view('mail.eStatement',[
            'data' => $this->data,
            'name' => $this->name,
            'staffId' => $this->staffId,
            'department' => $this->department,
            'designation' => $this->designation,
            'closingDate' => $this->closingDate
        ]);
    }
}
