<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    protected $with = ['permissions'];

    protected $appends = [
        'avatar',
        'name',
        'permis',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarAttribute() {
        if($this->avatar_url) {
            if (Str::startsWith($this->avatar_url, 'https://')) {
                return $this->avatar_url;
            } else {
                return asset('storage/'. $this->avatar_url);
            }
        }
        return 'assets/img/avatar.svg';
    }

    public function getNameAttribute() {
        return $this->first_name.' '.$this->last_name;
    }

    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    public function permissions() {
        return $this->belongsToMany(Permission::class)->withPivot('value');
    }

    public function getPermisAttribute() {
        $result = [];
        foreach ($this->permissions as $permission) {
            $v = $permission->code.'_'.($permission->pivot->value==1?'R':'W');
            array_push($result, $v);
            if ($permission->pivot->value == 2) {
                array_push($result, $permission->code.'_R');
            }
        }
        return $result;
    }
}
