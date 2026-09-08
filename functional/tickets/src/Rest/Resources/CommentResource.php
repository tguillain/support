<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Lomkit\Rest\Relations\BelongsTo;
use Lomkit\Rest\Relations\Relation;

class CommentResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<Model>
     */
    public static $model = Comment::class;

    /**
     * @return list<string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'body',
            'created_at',
        ];
    }

    /**
     * @return list<Relation>
     */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('author', UserResource::class)
                ->requiredOnCreation(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(RestRequest $request): array
    {
        return [
            'body' => ['string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createRules(RestRequest $request): array
    {
        return [
            'body' => ['required'],
        ];
    }
}
