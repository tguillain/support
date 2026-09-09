<?php

namespace Functional\Tickets\Rest\Concerns;

use App\Models\User;
use Functional\Tickets\Exceptions\UnenforceablePerimeterException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Rest\Http\Requests\RestRequest;

/**
 * The two narrowings every perimetered resource needs.
 *
 * The package types its query hooks with the Builder contract while the
 * framework always passes an Eloquent builder, and the actor arrives as a
 * nullable Authenticatable. Both are resolved here, and both refuse rather
 * than degrade: a perimeter that cannot be applied must stop the request, not
 * return an unrestricted query.
 */
trait ResolvesTicketPerimeter
{
    /**
     * @return EloquentBuilder<Model>
     *
     * @throws UnenforceablePerimeterException
     */
    protected function eloquentBuilder(Builder $query): EloquentBuilder
    {
        if (! $query instanceof EloquentBuilder) {
            throw UnenforceablePerimeterException::forBuilder($query::class);
        }

        return $query;
    }

    /**
     * @throws UnenforceablePerimeterException
     */
    protected function perimeterActor(RestRequest $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw UnenforceablePerimeterException::forMissingActor();
        }

        return $actor;
    }
}
