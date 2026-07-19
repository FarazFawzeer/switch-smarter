<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // Define the table name if it doesn't follow Laravel's naming convention
    protected $table = 'customers';

    // Specify the fillable fields to allow mass assignment
    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'contact',
        'address',
        'type',
        'sub_type',
        'other_phone',
        'whatsapp_number',
        'date_of_entry',
        'date_of_birth',
        'company_name',
        'country',
        'service',
        'heard_us',
        'portal',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_entry' => 'datetime',
    ];
}
