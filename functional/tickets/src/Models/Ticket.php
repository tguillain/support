<?php

namespace Functional\Tickets\Models;

use App\Models\User;
use Functional\Tickets\Database\Factories\TicketFactory;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Policies\TicketsPolicy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $requester_id
 * @property int|null $assigned_technician_id
 * @property string $title
 * @property string $description
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property Carbon|null $resolved_at
 * @property int|null $resolution_hours
 * @property bool|null $sla_met
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $requester
 * @property-read User|null $assignedTechnician
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> controlled()
 * @method static \Illuminate\Database\Eloquent\Builder<static> uncontrolled()
 */
#[UseFactory(TicketFactory::class)]
#[UsePolicy(TicketsPolicy::class)]
class Ticket extends Model
{
    use HasControl;

    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use Prunable;
    use SoftDeletes;

    private const RETENTION_DAYS = 90;

    protected $fillable = [
        'requester_id',
        'assigned_technician_id',
        'title',
        'description',
        'status',
        'priority',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return self::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }
}
