<?php

use App\Livewire\Chat\ConversationDetailsPanel;
use App\Livewire\Chat\ConversationHeader;
use App\Livewire\Chat\ConversationList;
use App\Livewire\Chat\ConversationView;
use App\Livewire\Chat\MessageComposer;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('authenticated users can visit the chat dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Messages')
        ->assertSee('Profile settings')
        ->assertSee('Password and 2FA')
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation');
});

test('conversation list only shows conversations the user participates in', function () {
    $user = User::factory()->create();
    $teammate = User::factory()->create(['name' => 'Visible Teammate']);
    $outsider = User::factory()->create(['name' => 'Hidden Teammate']);

    $visibleConversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($visibleConversation)->for($user)->create();
    ConversationParticipant::factory()->for($visibleConversation)->for($teammate)->create();

    $hiddenConversation = Conversation::factory()
        ->group()
        ->for($outsider, 'creator')
        ->create(['name' => 'Hidden Group']);

    ConversationParticipant::factory()->for($hiddenConversation)->for($outsider)->create();

    $this->actingAs($user);

    Livewire::test(ConversationList::class)
        ->assertSee('Visible Teammate')
        ->assertSee('Opening')
        ->assertDontSee('Hidden Group');
});

test('users can create direct conversations', function () {
    $user = User::factory()->create();
    $teammate = User::factory()->create(['name' => 'Direct Partner']);

    $this->actingAs($user);

    Livewire::test(ConversationList::class)
        ->call('openCreateConversation')
        ->call('toggleMember', $teammate->id)
        ->call('createConversation')
        ->assertHasNoErrors();

    $conversation = Conversation::query()
        ->where('type', Conversation::TypeDirect)
        ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
        ->whereHas('participants', fn ($query) => $query->where('user_id', $teammate->id))
        ->first();

    expect($conversation)->not->toBeNull();
    expect($conversation->participants()->count())->toBe(2);
});

test('users can create group conversations with members', function () {
    $user = User::factory()->create();
    $firstMember = User::factory()->create(['name' => 'Launch Lead']);
    $secondMember = User::factory()->create(['name' => 'Design Lead']);

    $this->actingAs($user);

    Livewire::test(ConversationList::class)
        ->set('createType', Conversation::TypeGroup)
        ->set('groupName', 'Launch Room')
        ->call('toggleMember', $firstMember->id)
        ->call('toggleMember', $secondMember->id)
        ->call('createConversation')
        ->assertHasNoErrors();

    $conversation = Conversation::query()
        ->where('type', Conversation::TypeGroup)
        ->where('name', 'Launch Room')
        ->first();

    expect($conversation)->not->toBeNull();
    expect($conversation->participants()->count())->toBe(3);
});

test('participants can send messages', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    $this->actingAs($user);

    Livewire::test(MessageComposer::class, ['conversationId' => $conversation->id])
        ->set('body', 'This looks ready to ship.')
        ->call('sendMessage')
        ->assertSet('body', '');

    expect(Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->where('body', 'This looks ready to ship.')
        ->exists())->toBeTrue();
});

test('participants can send file messages', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    $this->actingAs($user);

    Livewire::test(MessageComposer::class, ['conversationId' => $conversation->id])
        ->set('attachments', [
            UploadedFile::fake()->create('handoff.pdf', 64, 'application/pdf'),
        ])
        ->call('sendMessage')
        ->assertHasNoErrors()
        ->assertSet('body', '')
        ->assertSet('attachments', []);

    $message = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->where('type', Message::TypeFile)
        ->first();

    expect($message)->not->toBeNull();
    expect($message->attachmentName())->toBe('handoff.pdf');

    Storage::disk('local')->assertExists($message->attachmentPath());
});

test('file metadata is captured before temporary uploads are moved', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    $this->actingAs($user);

    $originalEnvironment = app()->environment();
    $content = str_repeat('x', 131072);

    try {
        app()->instance('env', 'local');

        Livewire::test(MessageComposer::class, ['conversationId' => $conversation->id])
            ->set('attachments', [
                UploadedFile::fake()->createWithContent('screenshot.txt', $content),
            ])
            ->call('sendMessage')
            ->assertHasNoErrors();
    } finally {
        app()->instance('env', $originalEnvironment);
    }

    $message = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('type', Message::TypeFile)
        ->first();

    expect($message)->not->toBeNull();
    expect($message->metadata)
        ->toMatchArray([
            'original_name' => 'screenshot.txt',
            'size' => 131072,
        ]);
});

test('participants can download shared files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    Storage::disk('local')->put('chat-attachments/'.$conversation->id.'/handoff.pdf', 'file contents');

    $message = Message::factory()
        ->file([
            'path' => 'chat-attachments/'.$conversation->id.'/handoff.pdf',
            'original_name' => 'handoff.pdf',
            'mime_type' => 'application/pdf',
            'size' => 13,
        ])
        ->for($conversation)
        ->for($user, 'sender')
        ->create();

    $this->actingAs($user)
        ->get(route('messages.attachment.download', $message))
        ->assertOk()
        ->assertDownload('handoff.pdf');

    $this->actingAs($outsider)
        ->get(route('messages.attachment.download', $message))
        ->assertForbidden();
});

test('conversation details files action shows shared files', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    Message::factory()
        ->file([
            'original_name' => 'launch-plan.pdf',
            'path' => 'chat-attachments/'.$conversation->id.'/launch-plan.pdf',
            'size' => 42000,
        ])
        ->for($conversation)
        ->for($user, 'sender')
        ->create();

    $this->actingAs($user);

    Livewire::test(ConversationDetailsPanel::class, ['conversationId' => $conversation->id])
        ->call('openFiles')
        ->assertSet('showFilesModal', true)
        ->assertSee('launch-plan.pdf')
        ->assertSee('Conversation files');
});

test('participants can add people to a direct conversation', function () {
    $user = User::factory()->create();
    $teammate = User::factory()->create(['name' => 'Mina Partner']);
    $newMember = User::factory()->create(['name' => 'Nima Added']);
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();
    ConversationParticipant::factory()->for($conversation)->for($teammate)->create();

    $this->actingAs($user);

    Livewire::test(ConversationDetailsPanel::class, ['conversationId' => $conversation->id])
        ->call('openAddMembers')
        ->set('groupName', 'Project Room')
        ->call('toggleMember', $newMember->id)
        ->call('addMembers')
        ->assertHasNoErrors();

    $conversation->refresh();

    expect($conversation->type)->toBe(Conversation::TypeGroup);
    expect($conversation->name)->toBe('Project Room');
    expect($conversation->participants()->where('user_id', $newMember->id)->exists())->toBeTrue();
});

test('selected conversations render the stream header and details', function () {
    $user = User::factory()->create();
    $teammate = User::factory()->create(['name' => 'Mina Partner']);
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();
    ConversationParticipant::factory()->for($conversation)->for($teammate)->create();

    Message::factory()
        ->for($conversation)
        ->for($teammate, 'sender')
        ->create(['body' => 'The latest prototype feels much faster.']);

    $this->actingAs($user);

    Livewire::test(ConversationView::class, ['conversationId' => $conversation->id])
        ->assertSee('The latest prototype feels much faster.');

    Livewire::test(ConversationHeader::class, ['conversationId' => $conversation->id])
        ->assertSee('Mina Partner');

    Livewire::test(ConversationDetailsPanel::class, ['conversationId' => $conversation->id])
        ->assertSee('Mina Partner');
});

test('empty messages are rejected', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->direct()
        ->for($user, 'creator')
        ->create();

    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    $this->actingAs($user);

    Livewire::test(MessageComposer::class, ['conversationId' => $conversation->id])
        ->set('body', '   ')
        ->call('sendMessage')
        ->assertHasErrors(['body' => 'required']);
});
