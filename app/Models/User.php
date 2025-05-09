<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Get all borrowings for this user.
     */
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'member_id', 'id');
    }
    
    /**
     * Get current active borrowings for this user.
     */
    public function activeBorrowings()
    {
        return $this->borrowings()->where('is_returned', false);
    }
    
    /**
     * Get count of active borrowings.
     */
    public function getActiveBorrowingsCountAttribute()
    {
        return $this->activeBorrowings()->count();
    }
    
    /**
     * Get count of overdue borrowings.
     */
    public function getOverdueBorrowingsCountAttribute()
    {
        return $this->activeBorrowings()
                    ->where('due_date', '<', now())
                    ->count();
    }
}
