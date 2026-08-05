<?php

namespace App\Filament\Pages;

use App\Models\StorySetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageStoryContent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Story Page';

    protected static ?string $navigationLabel = 'Story Page Content';

    protected static ?string $title = 'Story Page Content';

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'story-page-content';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $settings = StorySetting::current();

        $this->form->fill([
            'hero_mission' => $settings->hero_mission,
            'closing_note' => $settings->closing_note,
            'partners_section_enabled' => $settings->partners_section_enabled,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hero')
                    ->description('Mission / origin statement shown at the top of The Story So Far.')
                    ->schema([
                        Textarea::make('hero_mission')
                            ->label('Mission / origin paragraph')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Closing note')
                    ->schema([
                        Textarea::make('closing_note')
                            ->label('Closing note')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Homepage partners')
                    ->description('Show or hide the partners section on the public homepage.')
                    ->schema([
                        Toggle::make('partners_section_enabled')
                            ->label('Show partners section on homepage')
                            ->helperText('When off, the partners block is hidden even if partners exist.')
                            ->default(true),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        StorySetting::current()->update([
            'hero_mission' => $data['hero_mission'],
            'closing_note' => $data['closing_note'],
            'partners_section_enabled' => (bool) ($data['partners_section_enabled'] ?? false),
        ]);

        Notification::make()
            ->title('Story page content saved')
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save changes')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ])->key('form-actions'),
            ]);
    }
}
