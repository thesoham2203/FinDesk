<?php

declare(strict_types=1);

/**
 * User Model
 *
 * WHAT: Application user with role-based authorization and organizational hierarchy.
 *
 * WHY: FinDesk users have roles (Admin, Manager, Employee, Accountant) determining
 *      permissions (gates/policies). Users belong to departments and form manager/subordinate
 *      relationships for expense review workflows.
 *
 * IMPLEMENT: Complete. Self-referential manager/subordinates relationships enable
 *            organizational hierarchy. Scopes: byRole(), inDepartment() filter for
 *            authorization and reporting. createdInvoices() tracks invoices created by this user.
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Self-Referential: https://laravel.com/docs/13.x/eloquent-relationships#self-referential-relationships
 * - Authorization (Gates/Policies): https://laravel.com/docs/13.x/authorization
 */

namespace App\Models;

use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'manager_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'role' => UserRole::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Department, User>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, User>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * @return HasMany<User>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * @return HasMany<Expense>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all invoices created by this user.
     *
     * @return HasMany<Invoice>
     */
    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    /**
     * Filter users by role.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeByRole(Builder $query, UserRole $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Filter users who belong to a specific department.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeInDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }
}
