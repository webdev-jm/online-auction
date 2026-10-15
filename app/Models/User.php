<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\AuctionSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'google_id',
        'hub_user_id',
        'accepts_terms_and_conditions',
        'accepted_terms_version',
        'dark_mode',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'accepted_terms_version' => 'integer',
        'dark_mode' => 'boolean',
    ];

    public function company() {
        return $this->belongsTo('App\Models\Company');
    }

    public function biddings(): HasMany
    {
        return $this->hasMany(Bidding::class);
    }

    public function hasAcceptedCurrentTerms(): bool
    {
        return (int) $this->accepts_terms_and_conditions === 1
            && (int) $this->accepted_terms_version >= (int) app(AuctionSettings::class)->get('terms_version');
    }

    public function hasDeclinedTerms(): bool
    {
        return (int) $this->accepts_terms_and_conditions === 2;
    }
}
