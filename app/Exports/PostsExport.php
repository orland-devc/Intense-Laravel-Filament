<?php

namespace App\Exports;

use App\Models\Post;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class PostsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $columns;

    public function __construct(?Collection $data = null, ?array $columns = null)
    {
        $this->data = $data;
        $this->columns = $columns ?? [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At'
        ];
    }

    public function collection()
    {
        return $this->data ?? Post::all();
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($post): array
    {
        $mapped = [];
        foreach (array_keys($this->columns) as $column) {
            $value = $post[$column] ?? $post->$column;
            
            if (in_array($column, ['created_at', 'updated_at']) && $value) {
                $value = $value instanceof \Carbon\Carbon 
                    ? $value->format('Y-m-d H:i:s')
                    : \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            }
            
            $mapped[] = $value;
        }
        return $mapped;
    }
}