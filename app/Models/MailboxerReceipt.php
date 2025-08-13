<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MailboxerReceipt extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function notification()
    {
        return $this->belongsTo(MailboxerNotification::class, 'notification_id');
    }

    public function receiver()
    {
        return $this->morphTo();
    }
}
