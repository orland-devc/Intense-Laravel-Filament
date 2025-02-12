<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'status',
        'featured_image',
    ];

    public function getFilamentName(): string
    {
        return $this->title;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'content'];
    }

}
