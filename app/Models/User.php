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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'member_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'role',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
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
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
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
    }

    /**
     * Get the borrowings for the user.
     */
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'member_id', 'member_id');
    }

    /**
     * Get active borrowings that haven't been returned.
     */
    public function activeBorrowings()
    {
        return $this->borrowings()->where('is_returned', false);
    }

    /**
     * Get overdue borrowings.
     */
    public function overdueBorrowings()
    {
        return $this->activeBorrowings()->where('due_date', '<', now());
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
        return $this->overdueBorrowings()->count();
    }
}
