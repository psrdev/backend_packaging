<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PackingPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'uploaded_by',
        'photo_path',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
