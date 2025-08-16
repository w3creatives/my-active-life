<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MailboxerConversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function notifications()
    {
        return $this->hasMany(MailboxerNotification::class, 'conversation_id');
    }

    public function optOuts()
    {
        return $this->hasMany(MailboxerConversationOptOut::class, 'conversation_id');
    }

    /**
     * Get all unique participants (receiver_type, receiver_id) via receipts.
     */
    public function participants()
    {
        return MailboxerReceipt::query()
            ->select('receiver_type', 'receiver_id')
            ->whereIn('notification_id', $this->notifications()->pluck('id'))
            ->distinct();
    }
}
