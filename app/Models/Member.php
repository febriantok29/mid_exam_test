<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use Notifiable;
    
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'member_id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password', // Changed from password_hash to password for Laravel auth compatibility
        'password_hash', // Keep this for backward compatibility
        'full_name',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'password_hash',
    ];
    
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role' => 'member',
        'status' => 'active',
    ];
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash ?? $this->password;
    }
    
    /**
     * Set the user's password.
     *
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = bcrypt($value);
        
        // Also set password field if it exists in the schema
        if (array_key_exists('password', $this->attributes)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }
    
    /**
     * Get the borrowings for the member.
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'member_id', 'member_id');
    }
    
    /**
     * Get active borrowings that haven't been returned.
     */
    public function activeBorrowings(): HasMany
    {
        return $this->borrowings()->where('is_returned', false);
    }
    
    /**
     * Get overdue borrowings.
     */
    public function overdueBorrowings(): HasMany
    {
        return $this->activeBorrowings()->where('due_date', '<', now());
    }
    
    /**
     * Check if the member is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the member has admin role.
     *
     * @return bool
     */
    public function hasAdminRole(): bool
    {
        return $this->role === 'admin';
    }
}
