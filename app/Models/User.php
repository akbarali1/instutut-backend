<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const USER_STATUS       = 1;
    public const STUDENT_STATUS    = 2;
    public const TEACHER_STATUS    = 3;
    public const ADMIN_STATUS      = 4;
    public const SUPERVISOR_STATUS = 9;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'ref_id',
        'ref_bonus',
        'id_unique',
        'eth_address',
        'username',
        'ban',
        'photo',
        'name',
        'year',
        'last_name',
        'email',
        'otasi',
        'rights',
        'money',
        'password',
        'question',
        'rating',
        'phone',
        'intro',
        'status_id',
        'status_name',
        'purchased_error',
        'telegram_id',
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
        'intro'             => 'array',
    ];

    public function active_referal()
    {
        return $this->hasMany(__CLASS__, 'ref_id', 'id')->where('ref_bonus', '=', 1);
    }

    public function referals()
    {
        return $this->hasMany(__CLASS__, 'ref_id', 'id');
    }
}
