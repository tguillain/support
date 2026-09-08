<?php

namespace Functional\Tickets\Models;

use App\Models\User;
use Functional\Tickets\Database\Factories\TicketFactory;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(TicketFactory::class)]
class Ticket extends Model
{
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }
}
