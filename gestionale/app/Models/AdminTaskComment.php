<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTaskComment extends Model
{
    protected $table = 'admin_task_comments';

    public $timestamps = true;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'admin_task_id',
        'comment',
        'created_by',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AdminTask::class, 'admin_task_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }
}