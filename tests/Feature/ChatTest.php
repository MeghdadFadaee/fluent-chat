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
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('authenticated users can visit the chat dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Inbox')
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
        ->direct()
        ->for($outsider, 'creator')
        ->create();

    ConversationParticipant::factory()->for($hiddenConversation)->for($outsider)->create();

    $this->actingAs($user);

    Livewire::test(ConversationList::class)
        ->assertSee('Visible Teammate')
        ->assertDontSee('Hidden Teammate');
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
