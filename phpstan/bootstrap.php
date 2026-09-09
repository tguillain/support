<?php

/*
 * Livewire only registers assertSeeLivewire() and friends as TestResponse
 * macros when the application boots in the testing environment
 * (SupportTesting::provide() returns early otherwise). Booting the analyser in
 * that environment tells it what the suite actually has available, rather than
 * suppressing the resulting errors.
 */

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
