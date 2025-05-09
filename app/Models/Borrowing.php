<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'borrowing_id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'book_id',
        'borrow_date',
        'return_date',
        'status'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date'
    ];
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get formatted borrow date
     * 
     * @return string
     */
    public function getFormattedBorrowDateAttribute(): string
    {
        return $this->borrow_date->translatedFormat('l, j F Y');
    }

    /**
     * Get formatted return date
     * 
     * @return string|null
     */
    public function getFormattedReturnDateAttribute(): ?string
    {
        return $this->return_date ? $this->return_date->translatedFormat('l, j F Y') : null;
    }
    
    /**
     * Get the member that owns the borrowing.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id', 'member_id');
    }
    
    /**
     * Get the book that was borrowed.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id', 'book_id');
    }

    /**
     * Get the display status of the borrowing.
     * 
     * @return array
     */
    public function getDisplayStatusAttribute(): array
    {
        // Validasi data yang tidak valid
        if ($this->borrow_date->isFuture()) {
            return [
                'text' => 'Data Invalid',
                'class' => 'bg-danger',
                'tooltip' => 'Tanggal peminjaman tidak boleh di masa depan'
            ];
        }

        // Status untuk buku yang sudah dikembalikan
        if ($this->status === 'returned') {
            if ($this->return_date && $this->borrow_date->diffInDays($this->return_date) > 14) {
                $days = (int)$this->borrow_date->diffInDays($this->return_date);
                return [
                    'text' => 'Dikembalikan Terlambat',
                    'class' => 'bg-warning',
                    'tooltip' => 'Dikembalikan setelah ' . $days . ' hari'
                ];
            }
            return [
                'text' => 'Dikembalikan',
                'class' => 'bg-success',
                'tooltip' => 'Dikembalikan tepat waktu'
            ];
        }

        // Status untuk buku yang masih dipinjam
        $daysFromBorrow = (int)$this->borrow_date->diffInDays(now());
        $isPast = $this->borrow_date->isPast();

        // Jika tanggal pinjam di masa lalu
        if ($isPast && $daysFromBorrow > 14) {
            return [
                'text' => 'Perlu Dikembalikan',
                'class' => 'bg-danger',
                'tooltip' => 'Terlambat ' . ($daysFromBorrow - 14) . ' hari'
            ];
        }

        if ($isPast) {
            return [
                'text' => 'Sedang Dipinjam',
                'class' => 'bg-warning',
                'tooltip' => 'Sisa ' . max(0, 14 - $daysFromBorrow) . ' hari lagi'
            ];
        }

        // Jika tanggal pinjam di masa depan
        return [
            'text' => 'Data Invalid',
            'class' => 'bg-danger',
            'tooltip' => 'Tanggal peminjaman tidak valid'
        ];
    }

    /**
     * Get late status for display
     * 
     * @return array
     */
    public function getLateStatusAttribute(): array
    {
        // Validasi data yang tidak valid
        if ($this->borrow_date->isFuture()) {
            return [
                'text' => 'Data Invalid',
                'class' => 'bg-danger'
            ];
        }

        // Untuk buku yang sudah dikembalikan
        if ($this->status === 'returned' && $this->return_date) {
            $borrowDays = $this->borrow_date->diffInDays($this->return_date);
            if ($borrowDays > 14) {
                return [
                    'text' => 'Terlambat ' . ($borrowDays - 14) . ' hari',
                    'class' => 'bg-warning'
                ];
            }
            return [
                'text' => 'Tepat Waktu',
                'class' => 'bg-success'
            ];
        }

        // Untuk buku yang masih dipinjam
        // Hitung selisih hari, dengan mempertimbangkan apakah tanggal pinjam di masa lalu atau tidak
        $borrowDays = (int)$this->borrow_date->diffInDays(now());
        $isPast = $this->borrow_date->isPast();
        
        if ($isPast && $borrowDays > 14) {
            return [
                'text' => 'Terlambat ' . ($borrowDays - 14) . ' hari',
                'class' => 'bg-danger'
            ];
        }
        
        if (!$isPast) {
            return [
                'text' => 'Data Invalid',
                'class' => 'bg-danger'
            ];
        }

        return [
            'text' => 'Sisa ' . max(0, 14 - $borrowDays) . ' hari',
            'class' => 'bg-success'
        ];
    }

    /**
     * Check if the borrowing is overdue
     * 
     * @return bool
     */
    public function isOverdue(): bool
    {
        // Jika tanggal pinjam di masa depan, pasti tidak terlambat
        if ($this->borrow_date->isFuture()) {
            return false;
        }

        // Jika sudah dikembalikan, cek durasi peminjaman
        if ($this->status === 'returned' && $this->return_date) {
            return $this->borrow_date->diffInDays($this->return_date) > 14;
        }

        // Jika belum dikembalikan, cek durasi dari tanggal pinjam sampai hari ini
        if ($this->status === 'borrowed') {
            return $this->borrow_date->diffInDays(now()) > 14;
        }

        return false;
    }

    /**
     * Get the number of days the book is/was late
     * 
     * @return int
     */
    public function getLateDays(): int
    {
        // Jika tanggal pinjam di masa depan, tidak ada keterlambatan
        if ($this->borrow_date->isFuture()) {
            return 0;
        }

        // Jika sudah dikembalikan, hitung keterlambatan berdasarkan tanggal kembali
        if ($this->status === 'returned' && $this->return_date) {
            return max(0, $this->borrow_date->diffInDays($this->return_date) - 14);
        }

        // Jika belum dikembalikan, hitung keterlambatan sampai hari ini
        if ($this->status === 'borrowed') {
            return max(0, $this->borrow_date->diffInDays(now()) - 14);
        }

        return 0;
    }
}
