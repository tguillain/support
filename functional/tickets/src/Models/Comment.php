<?php

namespace Functional\Tickets\Models;

use App\Models\User;
use Functional\Tickets\Database\Factories\CommentFactory;
use Functional\Tickets\Policies\CommentsPolicy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $author_id
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Ticket $ticket
 * @property-read User $author
 */
#[UseFactory(CommentFactory::class)]
#[UsePolicy(CommentsPolicy::class)]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'author_id',
        'body',
    ];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
