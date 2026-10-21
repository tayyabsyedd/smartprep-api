<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_id')
                    ->required()
                    ->numeric(),
                Select::make('qtype')
                    ->options([
                        'mcq' => 'Multiple choice (single)',
                        'true_false' => 'True / False',
                        'multi_select' => 'Multiple choice (multi)',
                        'text_input' => 'Typed answer (text_input)',
                    ])
                    ->required()
                    ->default('mcq'),
                Textarea::make('question_text')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('options')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('correct_options')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('explanation')
                    ->columnSpanFull(),
                TextInput::make('marks')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('passage_text')
                    ->columnSpanFull(),
            ]);
    }
}
