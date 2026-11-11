<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $primaryKey = 'log_id';
    const UPDATED_AT = null;            // the table only has created_at
    protected $guarded = [];

    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }

    // AuditLog::record('Host verified', $verification, 'notes')  writes who did what, and to which record
    public static function record(string $action, $target = null, ?string $details = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'details' => $details ? Str::limit($details, 490) : null,
        ]);
    }
}