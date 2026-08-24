<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\ManageProjects;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A completely ordinary Filament resource. Nothing here knows about tenancy:
 * the plugin sets Resource::scopeToTenant(false) (multi-database safe) and
 * every query runs on the current tenant's own database.
 */
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            Select::make('status')
                ->options(['active' => 'Active', 'paused' => 'Paused', 'done' => 'Done'])
                ->default('active')
                ->required(),
            DatePicker::make('due_on'),
            Textarea::make('description')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'active' => 'success',
                    'paused' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('due_on')->date()->sortable(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProjects::route('/'),
        ];
    }
}
