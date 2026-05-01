<?php

namespace App\Models;

use Database\Factories\ConversationParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'user_id', 'role', 'joined_at', 'last_read_at', 'muted_until', 'pinned_at'])]
class ConversationParticipant extends Model
{
    /** @use HasFactory<ConversationParticipantFactory> */
    use HasFactory;

    public const string RoleAdmin = 'admin';

    public const string RoleMember = 'member';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_read_at' => 'datetime',
            'muted_until' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    public function isMuted(): bool
    {
        return $this->muted_until !== null && $this->muted_until->isFuture();
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
