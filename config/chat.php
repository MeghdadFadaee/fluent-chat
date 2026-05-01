<?php

return [
    'attachments' => [
        'max_files' => (int) env('CHAT_ATTACHMENT_MAX_FILES', 5),
        'max_file_size_kilobytes' => (int) env('CHAT_ATTACHMENT_MAX_FILE_SIZE_KB', 2 * 1024 * 1024),
        'livewire_max_upload_time' => (int) env('CHAT_LIVEWIRE_MAX_UPLOAD_TIME', 60),
    ],
];
