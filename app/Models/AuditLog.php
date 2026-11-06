<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'actor_role',
        'action',
        'route_name',
        'method',
        'path',
        'status_code',
        'ip_address',
        'context',
        'old_values',
        'new_values',
        'auditable_type',
        'auditable_id',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'status_code' => 'integer',
            'auditable_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Audit activity is always displayed in Philippine time.
     */
    public function createdAtInManila(): Carbon
    {
        return $this->created_at->timezone('Asia/Manila');
    }

    public function scopeFiltered(Builder $query, string $search = '', string $method = '', string $role = ''): Builder
    {
        return $query
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('user_name', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('path', 'like', "%{$search}%")
                        ->orWhere('route_name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true), function (Builder $query) use ($method) {
                $query->where('method', $method);
            })
            ->when(in_array($role, ['admin', 'customer'], true), function (Builder $query) use ($role) {
                $query->where('actor_role', $role);
            });
    }
}
