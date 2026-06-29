<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users that belong to this group.
     * Many-to-Many relationship via group_user pivot table.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user')
                    ->withPivot('has_agreed_rules')
                    ->withTimestamps();
    }

    /**
     * Get the topics in this group.
     * One-to-Many relationship.
     */
    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    /**
     * Get the quizzes for this group.
     * One-to-Many relationship.
     */
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}