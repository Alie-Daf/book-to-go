<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    protected $primaryKey = 'document_id';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }

    // The documents every host must send (type => label).
    public const HOST_DOCUMENTS = [
        'Valid ID' => 'Valid government ID',
        'Selfie with ID' => 'Selfie holding your ID',
        'Business Permit' => 'Business permit',
        'Property Title' => 'Lot / unit / house title',
    ];
}