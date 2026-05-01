# Fluent Chat

Fluent Chat is a modern Laravel chat platform built with Laravel 13, Livewire 4, Flux UI, Tailwind CSS 4, and Fortify. It focuses on a polished SaaS-style messaging experience with direct conversations, group conversations, profile settings, file sharing, and responsive chat layouts.

## Features

- Premium responsive chat dashboard with conversation sidebar, message stream, sticky headers, composer, and details panel.
- Direct and group conversations with member management.
- Conversation search, message search, unread badges, online indicators, pinned conversations, and muted conversations.
- Message composer with multiline input, Enter to send, Shift + Enter for new lines, emoji picker, attachment upload UI, loading states, and validation.
- File messages with download support and a conversation details "Files" browser.
- User profile settings for name, email, password, appearance, and two-factor authentication.
- Fortify-backed authentication with verified dashboard access.
- Demo seed data for realistic conversations and messages.
- Pest feature tests covering core chat flows.

## Tech Stack

| Layer      | Technology                       |
|------------|----------------------------------|
| Backend    | Laravel 13, PHP 8.4              |
| UI         | Livewire 4, Flux UI, Blade       |
| Styling    | Tailwind CSS 4, Vite             |
| Auth       | Laravel Fortify                  |
| Database   | MySQL by default in this project |
| Testing    | Pest 4, Laravel testing tools    |
| Formatting | Laravel Pint                     |

## Main Routes

| Route                            | Purpose                                |
|----------------------------------|----------------------------------------|
| `/`                              | Public welcome page                    |
| `/dashboard`                     | Authenticated chat dashboard           |
| `/settings/profile`              | Profile settings                       |
| `/settings/security`             | Password and two-factor authentication |
| `/settings/appearance`           | Appearance settings                    |
| `/messages/{message}/attachment` | Authenticated file download route      |

When served through Laravel Herd, the dashboard resolves to:

```text
https://fluent-chat.test/dashboard
```

## Requirements

- PHP 8.4+
- Composer
- Node.js and npm
- MySQL or another Laravel-supported database
- Laravel Herd, Valet, Sail, or another local PHP environment

## Installation

Clone the project and install dependencies:

```bash
composer install
npm install
```

Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`. For the current local setup, this project uses MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fluent_chat_db
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seed demo data:

```bash
php artisan migrate --seed
```

Build frontend assets:

```bash
npm run build
```

For active frontend development, run:

```bash
npm run dev
```

## Demo Account

The database seeder creates a demo user:

```text
Email: test@example.com
Password: password
```

## Development Commands

```bash
# Run the Vite dev server
npm run dev

# Build production assets
npm run build

# Run the test suite
php artisan test --compact

# Run only chat feature tests
php artisan test --compact tests/Feature/ChatTest.php

# Format dirty PHP files
vendor/bin/pint --dirty --format agent

# Clear cached Laravel state
php artisan optimize:clear
```

## Data Model

The chat system is centered on four main models:

- `User`: authenticated account, profile, security settings, and sent messages.
- `Conversation`: direct or group conversation.
- `ConversationParticipant`: per-user conversation membership, role, read state, mute state, and pin state.
- `Message`: text or file message belonging to a conversation and sender.

Important relationships:

- A conversation has many participants.
- A conversation has many messages.
- A message belongs to a conversation.
- A message belongs to a sender.
- A user belongs to many conversations through participants.
- A user has many messages.

## Livewire Components

Core chat UI is split into focused Livewire components:

- `ChatPage`: dashboard shell and selected conversation state.
- `ConversationList`: search, create conversation modal, unread counts, pinned and muted indicators.
- `ConversationHeader`: selected conversation header and message search toggle.
- `ConversationView`: message stream, grouped messages, search results, and loading states.
- `MessageComposer`: text, emoji, and file sending.
- `ConversationDetailsPanel`: participants, add members, files, mute, and pin actions.

Settings components live under `App\Livewire\Settings`.

## File Uploads

File messages are stored on Laravel's configured filesystem disk. The app captures attachment metadata before moving the temporary Livewire upload and serves files through an authenticated download route.

Useful environment value:

```env
FILESYSTEM_DISK=local
```

## Testing

Run the main test suite:

```bash
php artisan test --compact
```

The chat feature tests cover authenticated dashboard access, conversation visibility, direct and group creation, message sending, emoji insertion, file messages, file downloads, details panel files, member management, message search, and empty message validation.

## Production Notes

- Run `php artisan migrate --force` during deployment.
- Run `npm run build` before serving production assets.
- Use a queue worker if queued jobs are introduced.
- Keep `APP_DEBUG=false` in production.
- Configure mail before enabling real user-facing email flows.
- Configure HTTPS and secure session/cookie settings for production.

## Future Enhancements

- Real-time broadcasting for live message delivery and typing indicators.
- Read receipts and delivery receipts.
- Message reactions, edits, deletes, and replies.
- Drag-and-drop attachment uploads.
- Conversation-level roles and moderation controls.
- Notification preferences beyond mute state.
