<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function __invoke(Message $message): StreamedResponse
    {
        $message->loadMissing('conversation');

        Gate::authorize('view', $message->conversation);

        $path = $message->attachmentPath();

        abort_unless($message->isFile() && $path, 404);
        abort_unless(Storage::disk($message->attachmentDisk())->exists($path), 404);

        return Storage::disk($message->attachmentDisk())->download($path, $message->attachmentName());
    }
}
