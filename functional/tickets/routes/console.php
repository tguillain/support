<?php

use Functional\Tickets\Models\Ticket;

Schedule::command('model:prune', ['--model' => [Ticket::class]])->daily();
