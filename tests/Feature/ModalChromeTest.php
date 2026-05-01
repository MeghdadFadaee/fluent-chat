<?php

test('flux modals hide the built in top right close button', function () {
    $modalDeclarations = collect([
        resource_path('views/livewire/chat/conversation-details-panel.blade.php'),
        resource_path('views/livewire/chat/conversation-list.blade.php'),
        resource_path('views/livewire/settings/delete-user-form.blade.php'),
        resource_path('views/livewire/settings/security.blade.php'),
    ])->flatMap(function (string $path): array {
        preg_match_all('/<flux:modal(?![\\w.-])(?:[^"\'>]|"[^"]*"|\'[^\']*\')*>/s', file_get_contents($path), $matches);

        return $matches[0];
    });

    expect($modalDeclarations)->not->toBeEmpty();

    $modalDeclarations->each(fn (string $declaration) => expect($declaration)->toContain(':closable="false"'));
});
