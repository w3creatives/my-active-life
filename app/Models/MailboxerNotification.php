<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class MailboxerNotification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MailboxerConversation::class);
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function receipts()
    {
        return $this->hasMany(MailboxerReceipt::class, 'notification_id');
    }

    public function notifiedObject()
    {
        return $this->morphTo(__FUNCTION__, 'notified_object_type', 'notified_object_id');
    }
}
