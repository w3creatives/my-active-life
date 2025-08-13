<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MailboxerConversationOptOut extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function conversation()
    {
        return $this->belongsTo(MailboxerConversation::class, 'conversation_id');
    }

    public function unsubscriber()
    {
        return $this->morphTo();
    }
}
