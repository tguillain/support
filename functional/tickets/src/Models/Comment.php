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

#[UseFactory(CommentFactory::class)]
#[UsePolicy(CommentsPolicy::class)]
class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'author_id',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
