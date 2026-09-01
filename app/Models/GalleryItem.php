<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'image', 'caption', 'sort_order'])]
class GalleryItem extends Model
{
    use RecordsActivity, SoftDeletes;
}
