<?php

namespace Functional\Tickets\Rest\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class UserResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<Model>
     */
    public static $model = User::class;

    /**
     * Read-only projection used by the ticket relations. Never exposes the
     * credential columns, and this layer registers no route for it.
     *
     * @return list<string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'name',
            'email',
        ];
    }
}
