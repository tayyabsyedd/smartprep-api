<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('chapter'),
                TextInput::make('title')
                    ->required(),
                FileUpload::make('file_path')
                    ->label('PDF file')
                    ->disk('app_storage')
                    ->directory('notes')
                    ->acceptedFileTypes(['application/pdf'])
                    ->required(),
                Select::make('visibility')
                    ->options([
                        'public' => 'Public (everyone)',
                        'enrolled' => 'Enrolled students only',
                    ])
                    ->required()
                    ->default('public'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
