<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MessageComposer extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $conversationId;

    public string $body = '';

    public bool $emojiPickerOpen = false;

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $attachments = [];

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;

        $this->conversation();
    }

    public function sendMessage(): void
    {
        $this->body = trim($this->body);
        $hasAttachments = $this->hasAttachments();
        $maxAttachmentFiles = $this->maxAttachmentFiles();
        $maxAttachmentFileSizeKilobytes = $this->maxAttachmentFileSizeKilobytes();
        $maxAttachmentFileSizeLabel = $this->maxAttachmentFileSizeLabel();

        $validated = $this->validate([
            'body' => [$hasAttachments ? 'nullable' : 'required', 'string', 'max:4000'],
            'attachments' => ['array', 'max:'.$maxAttachmentFiles],
            'attachments.*' => ['file', 'max:'.$maxAttachmentFileSizeKilobytes],
        ], [
            'body.required' => __('Write a message or attach a file before sending.'),
            'attachments.max' => __('Attach up to :max files at a time.', ['max' => $maxAttachmentFiles]),
            'attachments.*.file' => __('Each attachment must be a valid file.'),
            'attachments.*.max' => __('Each attachment must be :size or smaller.', ['size' => $maxAttachmentFileSizeLabel]),
        ]);

        $conversation = $this->conversation();

        Gate::authorize('sendMessage', $conversation);

        $message = DB::transaction(function () use ($conversation, $validated): Message {
            $latestMessage = null;
            $body = trim((string) ($validated['body'] ?? ''));

            if ($body !== '') {
                $latestMessage = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => Auth::id(),
                    'type' => Message::TypeText,
                    'body' => $body,
                ]);
            }

            foreach ($this->attachments as $attachment) {
                $metadata = [
                    'disk' => 'local',
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                ];

                $path = $attachment->store(
                    path: "chat-attachments/{$conversation->id}",
                    options: 'local',
                );

                $metadata['path'] = $path;

                $latestMessage = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => Auth::id(),
                    'type' => Message::TypeFile,
                    'body' => $metadata['original_name'],
                    'metadata' => $metadata,
                ]);
            }

            $conversation->touch();

            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', Auth::id())
                ->update(['last_read_at' => now()]);

            return $latestMessage;
        });

        $this->reset('body', 'attachments');
        $this->emojiPickerOpen = false;
        $this->resetValidation();

        $this->dispatch('message-created', conversationId: $conversation->id, messageId: $message->id);
    }

    public function toggleEmojiPicker(): void
    {
        $this->emojiPickerOpen = ! $this->emojiPickerOpen;
    }

    public function appendEmoji(string $code): void
    {
        $emoji = $this->emojiForCode($code);

        if ($emoji === null) {
            return;
        }

        $body = rtrim($this->body);
        $this->body = $body === '' ? $emoji : $body.' '.$emoji;
        $this->emojiPickerOpen = false;

        $this->resetValidation('body');
    }

    public function removeAttachment(int $index): void
    {
        if (! array_key_exists($index, $this->attachments)) {
            return;
        }

        unset($this->attachments[$index]);

        $this->attachments = array_values($this->attachments);

        $this->resetValidation('attachments');
        $this->resetValidation("attachments.{$index}");
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function emojiOptions(): array
    {
        return [
            ['code' => '1F600', 'label' => __('Grinning face')],
            ['code' => '1F604', 'label' => __('Smiling face')],
            ['code' => '1F602', 'label' => __('Laughing face')],
            ['code' => '1F60D', 'label' => __('Heart eyes')],
            ['code' => '1F44B', 'label' => __('Wave')],
            ['code' => '1F44D', 'label' => __('Thumbs up')],
            ['code' => '1F44F', 'label' => __('Clap')],
            ['code' => '1F64C', 'label' => __('Raised hands')],
            ['code' => '1F525', 'label' => __('Fire')],
            ['code' => '2728', 'label' => __('Sparkles')],
            ['code' => '2705', 'label' => __('Check mark')],
            ['code' => '1F680', 'label' => __('Rocket')],
            ['code' => '1F4A1', 'label' => __('Idea')],
            ['code' => '1F440', 'label' => __('Eyes')],
            ['code' => '1F4CC', 'label' => __('Pin')],
            ['code' => '1F4CE', 'label' => __('Paperclip')],
            ['code' => '2764', 'label' => __('Heart')],
            ['code' => '1F389', 'label' => __('Party')],
        ];
    }

    public function attachmentLimitSummary(): string
    {
        return __('Up to :count files, :size each', [
            'count' => $this->maxAttachmentFiles(),
            'size' => $this->maxAttachmentFileSizeLabel(),
        ]);
    }

    private function conversation(): Conversation
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->findOrFail($this->conversationId);

        Gate::authorize('sendMessage', $conversation);

        return $conversation;
    }

    private function hasAttachments(): bool
    {
        return collect($this->attachments)
            ->filter()
            ->isNotEmpty();
    }

    private function maxAttachmentFiles(): int
    {
        return (int) config('chat.attachments.max_files', 5);
    }

    private function maxAttachmentFileSizeKilobytes(): int
    {
        return (int) config('chat.attachments.max_file_size_kilobytes', 2 * 1024 * 1024);
    }

    private function maxAttachmentFileSizeLabel(): string
    {
        return Number::fileSize($this->maxAttachmentFileSizeKilobytes() * 1024);
    }

    private function emojiForCode(string $code): ?string
    {
        $code = strtoupper($code);
        $isAllowed = collect($this->emojiOptions())
            ->contains(fn (array $emoji): bool => $emoji['code'] === $code);

        if (! $isAllowed) {
            return null;
        }

        return html_entity_decode('&#x'.$code.';', ENT_QUOTES, 'UTF-8');
    }

    public function render(): View
    {
        return view('livewire.chat.message-composer');
    }
}
