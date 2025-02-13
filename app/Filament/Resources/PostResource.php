<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use App\Exports\PostsExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Model;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('featured_image')
                    ->image()
                    ->directory('posts')
                    ->visibility('public')
                    ->imageEditor(),
                Forms\Components\RichEditor::make('content')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('export-custom')
                    ->label('Custom Export')
                    ->icon('heroicon-o-cog')
                    ->form([
                        Forms\Components\Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'excel' => 'Excel',
                                'pdf' => 'PDF',
                                'csv' => 'CSV',
                            ])
                            ->required(),
                        Forms\Components\CheckboxList::make('columns')
                            ->label('Select Columns')
                            ->options([
                                'id' => 'ID',
                                'title' => 'Title',
                                'status' => 'Status',
                                'content' => 'Content',
                                'created_at' => 'Created At',
                                'updated_at' => 'Updated At',
                            ])
                            ->required(),
                    ])
                    ->action(function (Post $record, array $data): void {
                        $items = collect([$record])->map(fn ($post) => 
                            array_intersect_key($post->toArray(), array_flip($data['columns']))
                        );
                        
                        $this->handleExport($items, $data['columns'], $data['format']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->form([
                            Forms\Components\Select::make('format')
                                ->label('Export Format')
                                ->options([
                                    'excel' => 'Excel',
                                    'pdf' => 'PDF',
                                    'csv' => 'CSV',
                                ])
                                ->required(),
                            Forms\Components\CheckboxList::make('columns')
                                ->label('Select Columns')
                                ->options([
                                    'id' => 'ID',
                                    'title' => 'Title',
                                    'status' => 'Status',
                                    'content' => 'Content',
                                    'created_at' => 'Created At',
                                    'updated_at' => 'Updated At',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $items = $records->map(fn ($post) => 
                                array_intersect_key($post->toArray(), array_flip($data['columns']))
                            );
                            
                            $this->handleExport($items, $data['columns'], $data['format']);
                        }),
                ]),
            ]);
    }

    protected function handleExport(Collection $items, array $columns, string $format): void
    {
        $timestamp = now()->format('Y-m-d-H-i-s');
        $filename = "posts-export-{$timestamp}";
    
        match ($format) {
            'excel' => Excel::download(
                new PostsExport($items, $columns),
                "{$filename}.xlsx"
            ),
            'pdf' => PDF::loadView('exports.posts', [
                'items' => $items,
                'columns' => $columns,
            ])->download("{$filename}.pdf"),
            'csv' => response()->streamDownload(function () use ($items, $columns) {
                $csv = fopen('php://output', 'w');
                fputcsv($csv, array_values($columns));
                foreach ($items as $item) {
                    $row = [];
                    foreach (array_keys($columns) as $key) {
                        $value = $item[$key] ?? '';
                        if (in_array($key, ['created_at', 'updated_at']) && $value) {
                            $value = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                        }
                        $row[] = $value;
                    }
                    fputcsv($csv, $row);
                }
                fclose($csv);
            }, "{$filename}.csv"),
            default => throw new \InvalidArgumentException('Invalid export format')
        };
    }

    protected function exportToExcel(Collection $items, array $columns, string $filename): void
    {
        Excel::download(
            new PostsExport($items, $columns),
            "{$filename}.xlsx"
        );
    }

    protected function exportToPDF(Collection $items, array $columns, string $filename): void
    {
        $pdf = Pdf::loadView('exports.posts', [
            'items' => $items,
            'columns' => $columns,
        ]);
        
        $pdf->download("{$filename}.pdf");
    }

    protected function exportToCSV(Collection $items, array $columns, string $filename): void
    {
        $handle = fopen("php://temp", 'r+');
        
        // Write headers
        fputcsv($handle, array_values($columns));
        
        // Write data
        foreach ($items as $item) {
            fputcsv($handle, array_values($item));
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        Storage::put("exports/{$filename}.csv", $content);
        
        response()->download(
            storage_path("app/exports/{$filename}.csv")
        )->deleteFileAfterSend();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function afterSave(Model $record, array $data): void
    {
        if ($data['status'] === 'published' && $record->wasChanged('status')) {
            User::role('admin')->each(function ($user) use ($record) {
                $user->notify(new NewPostNotification($record));
            });

            FilamentNotification::make()
                ->title('Post Published')
                ->success()
                ->body('The post has been published successfully.')
                ->send();
        }
    }
}