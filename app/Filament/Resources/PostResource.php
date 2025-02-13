<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Notifications\NewPostNotification;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Model;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;
use SplTempFileObject;


use App\Exports\PostsExport;
use Filament\Actions\ExportAction;


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
                    ->modalContent(view('filament.resources.post-resource.pages.export-settings'))
                    ->action(function (array $data) {
                        $format = $data['format'];
                        $selectedColumns = array_filter($data['columns']);
                        
                        // Get posts with selected columns
                        $posts = Post::select($selectedColumns)->get();
                        
                        // Create column mapping
                        $columns = array_combine(
                            $selectedColumns,
                            array_map(fn($col) => ucwords(str_replace('_', ' ', $col)), $selectedColumns)
                        );

                        return match ($format) {
                            'excel' => Excel::download(new PostsExport($columns), 'posts.xlsx'),
                            'pdf' => self::generatePDF($posts, $columns),
                            'csv' => self::generateCSV($posts, $columns),
                            default => null
                        };
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(fn ($records) => Excel::download(new PostsExport(), 'posts.xlsx')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export All')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => Excel::download(new PostsExport(), 'posts.xlsx')),
                    
                Tables\Actions\Action::make('export-pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => self::generatePDF(Post::all())),
                    
                Tables\Actions\Action::make('export-csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => self::generateCSV(Post::all())),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
            // Send email notifications to all admin users
            User::role('admin')->each(function ($user) use ($record) {
                $user->notify(new NewPostNotification($record));
            });

            // Show in-app notification
            FilamentNotification::make()
                ->title('Post Published')
                ->success()
                ->body('The post has been published successfully.')
                ->send();
        }
    }



    protected static function generatePDF($posts, array $columns = null)
    {
        $pdf = Pdf::loadView('exports.posts', [
            'items' => $posts,
            'columns' => $columns ?? [
                'id' => 'ID',
                'title' => 'Title',
                'content' => 'Content',
                'status' => 'Status',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At'
            ]
        ]);
        return $pdf->download('posts.pdf');
    }

    protected static function generateCSV($posts, array $columns = null)
    {
        return response()->streamDownload(function () use ($posts, $columns) {
            $csv = Writer::createFromFileObject(new SplTempFileObject());
            
            // Set headers
            $headers = $columns ? array_values($columns) : ['ID', 'Title', 'Status', 'Created At'];
            $csv->insertOne($headers);
            
            // Add data
            foreach ($posts as $post) {
                $row = [];
                foreach (array_keys($columns ?? ['id' => '', 'title' => '', 'status' => '', 'created_at' => '']) as $field) {
                    $value = $post->{$field};
                    if (in_array($field, ['created_at', 'updated_at']) && $value) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $row[] = $value;
                }
                $csv->insertOne($row);
            }
        }, 'posts.csv');
    }

    protected function exportToExcel($items, $columns)
    {
        $fileName = 'export.xlsx';

        Excel::create($fileName, function($excel) use ($items, $columns) {
            $excel->sheet('Sheet 1', function($sheet) use ($items, $columns) {
                $sheet->fromArray($items, null, 'A1', false, false);
                $sheet->row(1, $columns);
            });
        })->download('xlsx');
    }

    protected function exportToPDF($items, $columns)
    {
        $pdf = PDF::loadView('filament.resources.post-resource.pages.export-pdf', compact('items', 'columns'));
        return $pdf->download('export.pdf');
    }

    // Method to export to CSV
    protected function exportToCSV($items, $columns)
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne($columns);
        $csv->insertAll($items);
        
        return response((string) $csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="export.csv"',
        ]);
    }
}
