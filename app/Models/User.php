<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;

// Agregamos los 3 nuevos campos al arreglo del atributo Fillable
#[Fillable(['name', 'email', 'password', 'taller_id', 'activo', 'rol'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'taller_id',
        'activo',
        'rol',
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

    /**
     * Relación: Un usuario pertenece a un Taller
     */
    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Si el usuario está activo, lo dejamos entrar al panel.
        // Shield ya se encargará de ocultar/mostrar los menús según sus permisos.
        return $this->activo == true;
    }

}
