<?php

namespace App\Models;

use Database\Factories\WorkflowUndoEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowUndoEntry extends Model
{
    /** @use HasFactory<WorkflowUndoEntryFactory> */
    use HasFactory;
}
