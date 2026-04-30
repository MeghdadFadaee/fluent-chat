<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

#[Fillable(['conversation_id', 'user_id', 'type', 'body', 'metadata', 'edited_at'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    public const string TypeText = 'text';

    public const string TypeFile = 'file';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'edited_at' => 'datetime',
        ];
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFile(): bool
    {
        return $this->type === self::TypeFile;
    }

    public function attachmentDisk(): string
    {
        return (string) data_get($this->metadata, 'disk', 'local');
    }

    public function attachmentPath(): ?string
    {
        $path = data_get($this->metadata, 'path');

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function attachmentName(): string
    {
        $name = data_get($this->metadata, 'original_name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return $this->body ?: __('Attachment');
    }

    public function attachmentMimeType(): ?string
    {
        $mimeType = data_get($this->metadata, 'mime_type');

        return is_string($mimeType) && $mimeType !== '' ? $mimeType : null;
    }

    public function attachmentSize(): int
    {
        return (int) data_get($this->metadata, 'size', 0);
    }

    public function formattedAttachmentSize(): string
    {
        $size = $this->attachmentSize();

        return $size > 0 ? Number::fileSize($size) : __('Unknown size');
    }
}
