<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        $validated = $this->validate([
            'body' => [$hasAttachments ? 'nullable' : 'required', 'string', 'max:4000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ], [
            'body.required' => __('Write a message or attach a file before sending.'),
            'attachments.max' => __('Attach up to :max files at a time.'),
            'attachments.*.file' => __('Each attachment must be a valid file.'),
            'attachments.*.max' => __('Each attachment must be 10 MB or smaller.'),
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
        $this->resetValidation();

        $this->dispatch('message-created', conversationId: $conversation->id, messageId: $message->id);
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

    public function render(): View
    {
        return view('livewire.chat.message-composer');
    }
}
