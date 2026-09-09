<?php

return [
    'create_heading' => 'Nouveau ticket',
    'edit_heading' => 'Modifier le ticket',
    'back' => 'Retour à la liste des tickets',
    'assignment_heading' => 'Assignation',
    'current_status' => 'Statut actuel',
    'fields' => [
        'title' => 'Sujet',
        'description' => 'Description',
        'priority' => 'Priorité',
        'technician' => 'Technicien',
    ],
    'placeholders' => [
        'technician' => 'Choisir un technicien',
    ],
    'buttons' => [
        'save' => 'Enregistrer',
        'assign' => 'Assigner',
    ],
    'flash' => [
        'created' => 'Ticket ouvert.',
        'updated' => 'Ticket mis à jour.',
        'assigned' => 'Ticket assigné au technicien.',
    ],
    'errors' => [
        'illegal_transition' => 'Ce ticket ne peut pas passer de :from à :to.',
    ],
    'attributes' => [
        'title' => 'sujet',
        'description' => 'description',
        'priority' => 'priorité',
        'technicianId' => 'technicien',
    ],
    'validation' => [
        'title' => [
            'required' => 'Un sujet est obligatoire.',
            'max' => 'Le sujet ne doit pas dépasser :max caractères.',
        ],
        'description' => [
            'required' => 'Une description est obligatoire.',
            'min' => 'La description doit faire au moins :min caractères.',
        ],
        'priority' => [
            'required' => 'Une priorité est obligatoire.',
            'enum' => 'Cette priorité n\'existe pas.',
        ],
        'technicianId' => [
            'required' => 'Choisissez un technicien.',
            'exists' => 'Ce technicien n\'existe pas.',
        ],
    ],
];
