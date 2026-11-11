<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage; 

class User extends Authenticatable
{
    use Notifiable;     // lets us do $user->notify(...)
    
    protected $primaryKey = 'user_id';
    protected $guarded = [];
    protected $hidden = ['password_hash'];

    // Our password column is named password_hash (see the database dictionary)
    public function getAuthPassword() { return $this->password_hash; }
    public function getAuthPasswordName() { return 'password_hash'; }

    public function getFullNameAttribute() { return trim("{$this->first_name} {$this->last_name}"); }
    public function getInitialsAttribute() { return strtoupper(substr($this->first_name, 0, 1).substr($this->last_name, 0, 1)); }
    public function getIsVerifiedAttribute() { return $this->verification?->status === 'Verified'; }

    public function units() { return $this->hasMany(Unit::class, 'host_id', 'user_id'); }
    public function bookings() { return $this->hasMany(Booking::class, 'guest_id', 'user_id'); }
    public function reviews() { return $this->hasMany(Review::class, 'guest_id', 'user_id'); }
    public function documents() { return $this->hasMany(UserDocument::class, 'user_id', 'user_id'); }
    public function verification() { return $this->hasOne(HostVerification::class, 'host_id', 'user_id'); }
    public function bookmarks() { return $this->belongsToMany(Unit::class, 'bookmarks', 'guest_id', 'unit_id'); }
    
    // search by name or email
    public function scopeSearch($query, $term)
    {
        return $query->where(fn ($q) => $q->where('first_name', 'like', "%$term%")
            ->orWhere('last_name', 'like', "%$term%")->orWhere('email', 'like', "%$term%"));
    }

        public const HOLD_MESSAGE = 'Your account is on hold because of an overdue invoice. Please settle it in Billing.';

    public function invoices() { return $this->hasMany(Invoice::class, 'host_id', 'user_id'); }
    public function getIsOnHoldAttribute() { return $this->account_status === 'Hold'; }

    // Units a host may have: the free limit plus the unit slots he bought
    public function unitLimit(): int
    {
        return (int) FeeSetting::get('free_unit_limit', 10) + $this->extra_unit_slots;
    }

    // Saves (or replaces) one verification document and sends the host to the admin for review
    public function submitDocument(string $type, UploadedFile $file): void
    {
        $old = $this->documents()->where('document_type', $type)->first();
        if ($old) {
            Storage::delete($old->file_reference);
        }

        $this->documents()->updateOrCreate(['document_type' => $type], [
            'file_reference' => $file->store('host-documents'),     // private folder
            'verification_status' => 'Pending',
            'reviewed_by' => null, 'reviewed_at' => null, 'reviewer_notes' => null,
            'uploaded_at' => now(),
        ]);

        if ($this->verification?->status !== 'Verified') {
            $this->verification()->updateOrCreate([], ['status' => 'Pending', 'submitted_at' => now(), 'reviewed_by' => null, 'reviewed_at' => null]);
        }
    }

    }