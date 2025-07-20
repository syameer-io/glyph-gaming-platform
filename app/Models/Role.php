<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'name',
        'color',
        'position',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('server_id')
            ->withTimestamps();
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }
}