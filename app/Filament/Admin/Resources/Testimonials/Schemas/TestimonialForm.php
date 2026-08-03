<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Testimonials\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('location')->maxLength(100),
                Select::make('rating')->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'])->default(5),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            FileUpload::make('avatar_path')->image()->maxSize(2048)->disk('public')->directory('testimonials'),
            Textarea::make('quote')->required()->rows(4),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
