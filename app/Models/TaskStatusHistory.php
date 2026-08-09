<?php

namespace App\Models;

use Database\Factories\TaskStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatusHistory extends Model
{
    /** @use HasFactory<TaskStatusHistoryFactory> */
    use HasFactory;
}
