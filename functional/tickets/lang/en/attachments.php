<?php

return [
    'heading' => 'Attachments',
    'field' => 'File',
    'submit' => 'Attach',
    'uploading' => 'Uploading…',
    'empty' => 'No attachment on this ticket.',
    'hint' => 'PDF, image, CSV or plain text, up to :megabytes MB.',
    'uploaded_by' => 'Added by :name — :size KB',
    'detach' => 'Remove the attachment :name',
    'detach_short' => 'Remove',
    'flash' => [
        'attached' => 'Attachment added.',
        'detached' => 'Attachment removed.',
    ],
    'validation' => [
        'required' => 'Choose a file to attach.',
        'max' => 'The file must not exceed :megabytes MB.',
        'mimetypes' => 'This file type is not accepted.',
    ],
];
