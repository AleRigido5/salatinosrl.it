<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AdminTaskTag extends Model
{
    protected $table = 'admin_task_tags';

    protected $fillable = ['name', 'slug'];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(AdminTask::class, 'admin_task_tag_task', 'admin_task_tag_id', 'admin_task_id');
    }

    protected static function booted()
    {
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Trova un tag per nome (case-insensitive) o lo crea se non esiste.
     * Usato quando l'utente digita parole chiave libere nel form del task.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);
        $slug = Str::slug($name);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }
}