<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Rest\Concerns\ResolvesTicketPerimeter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Lomkit\Rest\Relations\BelongsTo;
use Lomkit\Rest\Relations\Relation;

class AttachmentResource extends Resource
{
    use ResolvesTicketPerimeter;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<Model>
     */
    public static $model = Attachment::class;

    /**
     * Read-only metadata. The bytes never travel through this endpoint: the
     * disk and the path stay internal, and downloads go through their own
     * authorized route.
     *
     * @return list<string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'original_name',
            'mime_type',
            'size_bytes',
            'created_at',
        ];
    }

    /**
     * @return list<Relation>
     */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('uploader', UserResource::class),
        ];
    }

    public function searchQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->withinVisibleTickets($request, $query);
    }

    public function destroyQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->withinVisibleTickets($request, $query);
    }

    /**
     * Attachments inherit the ticket perimeters instead of declaring their own:
     * the restriction is a subquery on the tickets the caller may read, so it
     * lands in SQL rather than being filtered afterwards.
     */
    private function withinVisibleTickets(RestRequest $request, Builder $query): Builder
    {
        $actor = $this->perimeterActor($request);

        return $this->eloquentBuilder($query)->whereHas(
            'ticket',
            fn (EloquentBuilder $tickets): EloquentBuilder => (new TicketControl)->queried($tickets, $actor),
        );
    }
}
