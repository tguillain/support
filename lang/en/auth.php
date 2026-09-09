<?php

return [
    /*
     * Framework keys — overriding them here keeps every authentication string
     * in one place and translatable.
     */
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many attempts. Please try again in :seconds seconds.',

    'login' => [
        'eyebrow' => 'Support desk',
        'secured' => 'Secured connection',
        'heading' => 'Sign in',
        'subtitle' => 'Access the support desk.',
        'submit' => 'Sign in',
        'fields' => [
            'email' => 'Email address',
            'password' => 'Password',
            'remember' => 'Keep me signed in',
        ],
    ],
    'logout' => 'Sign out',
    'signed_in_as' => 'Signed in as',
    'error_label' => 'Error',

    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
    ],
    'validation' => [
        'email' => [
            'required' => 'Enter your email address.',
            'email' => 'Enter a valid email address.',
        ],
        'password' => [
            'required' => 'Enter your password.',
        ],
    ],
];
