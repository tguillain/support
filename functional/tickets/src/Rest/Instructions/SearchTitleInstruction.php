<?php

namespace Functional\Tickets\Rest\Instructions;

use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Instructions\Instruction;

/**
 * Free-text search on the ticket title.
 *
 * Exposed as an instruction rather than by adding `title` to the filterable
 * set: the client gets one narrow, named capability instead of a general
 * `like` operator on the column.
 */
class SearchTitleInstruction extends Instruction
{
    /**
     * @param  array<string, mixed>  $fields
     * @param  Builder<Ticket>  $query
     */
    public function handle(array $fields, Builder $query): void
    {
        $query->whereLike('title', '%'.$fields['value'].'%');
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'value' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }
}
