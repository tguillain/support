<?php

namespace Functional\Tickets\Rest\Controllers;

use Functional\Tickets\Rest\Resources\TicketResource;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Lomkit\Rest\Http\Controllers\Controller;
use Lomkit\Rest\Http\Requests\SearchRequest;
use Lomkit\Rest\Http\Resource;

class TicketsController extends Controller
{
    /**
     * The resource the controller corresponds to.
     *
     * @var class-string<\Lomkit\Rest\Http\Resource>
     */
    public static $resource = TicketResource::class;

    /**
     * Fields the client may filter and sort on.
     *
     * The package validates both `filters` and `sorts` against the resource's
     * `fields()` (see Lomkit\Rest\Rules\Search\SearchSort) — v2.23 has no
     * separate filterable/sortable declaration. This narrower whitelist is
     * therefore enforced here: title and description stay readable but not
     * queryable, and free-text title search goes through SearchTitleInstruction.
     *
     * @var list<string>
     */
    private const QUERYABLE_FIELDS = [
        'status',
        'priority',
        'created_at',
    ];

    protected function beforeSearch(SearchRequest $request): void
    {
        $this->assertFieldsAreQueryable(
            $this->filteredFields($request->input('search.filters', [])),
            'search.filters',
        );

        $this->assertFieldsAreQueryable(
            Arr::pluck($request->input('search.sorts', []), 'field'),
            'search.sorts',
        );
    }

    /**
     * Collect every field referenced by a filter tree, descending into groups.
     *
     * @param  array<int, array<string, mixed>>  $filters
     * @return list<string>
     */
    private function filteredFields(array $filters): array
    {
        $fields = [];

        foreach ($filters as $filter) {
            if (isset($filter['nested'])) {
                $fields = array_merge($fields, $this->filteredFields($filter['nested']));

                continue;
            }

            if (isset($filter['field'])) {
                $fields[] = $filter['field'];
            }
        }

        return $fields;
    }

    /**
     * @param  list<string>  $fields
     *
     * @throws ValidationException
     */
    private function assertFieldsAreQueryable(array $fields, string $key): void
    {
        $rejected = array_values(array_diff(array_filter($fields), self::QUERYABLE_FIELDS));

        if ($rejected === []) {
            return;
        }

        throw ValidationException::withMessages([
            $key => sprintf(
                'Only [%s] may be filtered or sorted on. Rejected: [%s].',
                implode(', ', self::QUERYABLE_FIELDS),
                implode(', ', $rejected),
            ),
        ]);
    }
}
