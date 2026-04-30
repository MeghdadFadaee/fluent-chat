<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $teammates = User::factory()
            ->count(10)
            ->create();

        $teammates->take(5)->each(function (User $teammate, int $index) use ($user): void {
            $conversation = Conversation::factory()
                ->direct()
                ->for($user, 'creator')
                ->create(['updated_at' => now()->subMinutes(12 - $index)]);

            ConversationParticipant::factory()
                ->for($conversation)
                ->for($user)
                ->create([
                    'joined_at' => now()->subWeeks(3),
                    'last_read_at' => $index < 2 ? now()->subMinutes(20) : now(),
                ]);

            ConversationParticipant::factory()
                ->for($conversation)
                ->for($teammate)
                ->create([
                    'joined_at' => now()->subWeeks(3),
                    'last_read_at' => now()->subHour(),
                ]);

            $this->seedMessages($conversation, collect([$user, $teammate])->values()->all(), 8, $index);
        });

        $groups = [
            ['name' => 'Product Launch', 'description' => 'Messaging for launch readiness, blockers, and decisions.'],
            ['name' => 'Design Partners', 'description' => 'Feedback loops with design, research, and product.'],
            ['name' => 'Customer Success', 'description' => 'Escalations, onboarding notes, and customer wins.'],
        ];

        foreach ($groups as $groupIndex => $group) {
            $conversation = Conversation::factory()
                ->group()
                ->for($user, 'creator')
                ->create([
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'updated_at' => now()->subMinutes(5 + $groupIndex),
                ]);

            $participants = $teammates
                ->slice($groupIndex * 3, 4)
                ->push($user)
                ->values();

            $participants->each(function (User $participant) use ($conversation, $user): void {
                ConversationParticipant::factory()
                    ->for($conversation)
                    ->for($participant)
                    ->create([
                        'role' => $participant->is($user) ? ConversationParticipant::RoleAdmin : ConversationParticipant::RoleMember,
                        'joined_at' => now()->subMonth(),
                        'last_read_at' => $participant->is($user) ? now()->subMinutes(30) : now()->subDay(),
                    ]);
            });

            $this->seedMessages($conversation, $participants->all(), 14, $groupIndex + 5);
        }
    }

    /**
     * @param  array<int, User>  $participants
     */
    private function seedMessages(Conversation $conversation, array $participants, int $count, int $offset): void
    {
        $startedAt = now()
            ->subDays(3)
            ->addHours($offset * 2);

        for ($messageIndex = 0; $messageIndex < $count; $messageIndex++) {
            $sender = $participants[$messageIndex % count($participants)];
            $createdAt = $startedAt->copy()->addMinutes($messageIndex * 27);

            Message::factory()
                ->for($conversation)
                ->for($sender, 'sender')
                ->create([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
        }

        $conversation->forceFill([
            'updated_at' => $startedAt->copy()->addMinutes($count * 27),
        ])->save();
    }
}
