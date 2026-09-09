<?php

return [
    'create_heading' => 'New ticket',
    'edit_heading' => 'Edit ticket',
    'back' => 'Back to the ticket list',
    'assignment_heading' => 'Assignment',
    'current_status' => 'Current status',
    'fields' => [
        'title' => 'Subject',
        'description' => 'Description',
        'priority' => 'Priority',
        'technician' => 'Technician',
    ],
    'placeholders' => [
        'technician' => 'Choose a technician',
    ],
    'buttons' => [
        'save' => 'Save',
        'assign' => 'Assign',
    ],
    'flash' => [
        'created' => 'Ticket opened.',
        'updated' => 'Ticket updated.',
        'assigned' => 'Ticket assigned to the technician.',
    ],
    'errors' => [
        'illegal_transition' => 'This ticket cannot move from :from to :to.',
    ],
    'attributes' => [
        'title' => 'subject',
        'description' => 'description',
        'priority' => 'priority',
        'technicianId' => 'technician',
    ],
    'validation' => [
        'title' => [
            'required' => 'A subject is required.',
            'max' => 'The subject must not exceed :max characters.',
        ],
        'description' => [
            'required' => 'A description is required.',
            'min' => 'The description must be at least :min characters.',
        ],
        'priority' => [
            'required' => 'A priority is required.',
            'enum' => 'This priority does not exist.',
        ],
        'technicianId' => [
            'required' => 'Choose a technician.',
            'exists' => 'This technician does not exist.',
        ],
    ],
];
