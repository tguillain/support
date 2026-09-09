<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Rest\Actions\AssignTicketAction;
use Functional\Tickets\Rest\Actions\CloseTicketAction;
use Functional\Tickets\Rest\Actions\ReopenTicketAction;
use Functional\Tickets\Rest\Actions\ResolveTicketAction;
use Functional\Tickets\Rest\Actions\StartTicketProgressAction;
use Functional\Tickets\Rest\Actions\UnassignTicketAction;
use Functional\Tickets\Rest\Concerns\ResolvesTicketPerimeter;
use Functional\Tickets\Rest\Instructions\SearchTitleInstruction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Lomkit\Rest\Actions\Action;
use Lomkit\Rest\Http\Requests\MutateRequest;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Lomkit\Rest\Instructions\Instruction;
use Lomkit\Rest\Relations\BelongsTo;
use Lomkit\Rest\Relations\HasMany;
use Lomkit\Rest\Relations\Relation;

class TicketResource extends Resource
{
    use ResolvesTicketPerimeter;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<Model>
     */
    public static $model = Ticket::class;

    /**
     * The fields the client may select, mutate, filter and sort on.
     *
     * Deliberately excludes requester_id, assigned_technician_id, updated_at
     * and deleted_at: the foreign keys are reachable through the declared
     * relations, the timestamps are internal bookkeeping.
     *
     * @return list<string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'title',
            'description',
            'status',
            'priority',
            'created_at',
            'resolved_at',
        ];
    }

    /**
     * @return list<Relation>
     */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('requester', UserResource::class)
                ->requiredOnCreation()
                ->prohibitedOnUpdate(),

            BelongsTo::make('assignedTechnician', UserResource::class),

            HasMany::make('comments', CommentResource::class),
        ];
    }

    /**
     * One entry per business transition. Status is not writable through
     * `mutate`, so these actions are the only way it can ever change.
     *
     * @return list<Action>
     */
    public function actions(RestRequest $request): array
    {
        return [
            AssignTicketAction::make(),
            UnassignTicketAction::make(),
            StartTicketProgressAction::make(),
            ResolveTicketAction::make(),
            CloseTicketAction::make(),
            ReopenTicketAction::make(),
        ];
    }

    /**
     * @return list<Instruction>
     */
    public function instructions(RestRequest $request): array
    {
        return [
            SearchTitleInstruction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(RestRequest $request): array
    {
        return [
            'title' => ['string', 'max:255'],
            'description' => ['string'],
            'status' => [Rule::enum(TicketStatus::class)],
            'priority' => [Rule::enum(TicketPriority::class)],
            'resolved_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createRules(RestRequest $request): array
    {
        return [
            'title' => ['required'],
            'description' => ['required'],
            'priority' => ['required'],
            'status' => ['prohibited'],
        ];
    }

    /**
     * A ticket may not be born anywhere but at the start of the lifecycle, and
     * may not jump states afterwards: every change goes through an action.
     *
     * @return array<string, mixed>
     */
    public function updateRules(RestRequest $request): array
    {
        return [
            'status' => ['prohibited'],
        ];
    }

    /**
     * @param  array<string, mixed>  $requestBody
     */
    public function mutating(MutateRequest $request, array $requestBody, Model $ticket): void
    {
        if ($ticket instanceof Ticket && ! $ticket->exists) {
            $ticket->status = TicketStatus::initial();
        }
    }

    /**
     * Apply the ticket perimeters to every read.
     *
     * The package calls this hook inside its own `where(...)` subquery — the
     * documented articulation point between a Resource and a Control — so the
     * restriction lands in the SQL, before any row is hydrated. Includes go
     * through the same hook via Lomkit\Rest\Relations\Relation.
     */
    public function searchQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->perimetered($request, $query);
    }

    public function destroyQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->perimetered($request, $query);
    }

    public function restoreQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->perimetered($request, $query);
    }

    public function forceDeleteQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->perimetered($request, $query);
    }

    /**
     * Apply the ticket perimeters to a query the package hands over. The hooks
     * call this inside their own where(...) subquery, so the restriction lands
     * in the SQL before any row is hydrated.
     */
    private function perimetered(RestRequest $request, Builder $query): Builder
    {
        return (new TicketControl)->queried(
            $this->eloquentBuilder($query),
            $this->perimeterActor($request),
        );
    }
}
