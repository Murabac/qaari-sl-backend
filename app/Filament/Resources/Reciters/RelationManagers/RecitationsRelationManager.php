<?php

namespace App\Filament\Resources\Reciters\RelationManagers;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\Surah;
use App\Support\AudioMetadata;
use App\Support\FilamentR2FileUpload;
use App\Support\MediaUrl;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class RecitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'recitations';

    protected static ?string $title = 'Recitations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        $uploaded = $ownerRecord->recitations()->count();

        return "Recitations ({$uploaded} / 114)";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('surah_id')
                    ->label('Surah')
                    ->options(fn (): array => $this->availableSurahOptions())
                    ->searchable()
                    ->optionsLimit(114)
                    ->required()
                    ->unique(
                        table: 'recitations',
                        column: 'surah_id',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                            'reciter_id',
                            $this->getOwnerRecord()->getKey(),
                        ),
                    )
                    ->validationMessages([
                        'unique' => 'This surah already has a recitation for this reciter.',
                    ])
                    ->columnSpanFull(),
                FilamentR2FileUpload::configure(
                    FileUpload::make('audio_url')
                        ->label('Audio file')
                        ->directory('recitations/audio')
                        ->acceptedFileTypes([
                            'audio/mpeg',
                            'audio/mp3',
                            'audio/wav',
                            'audio/x-wav',
                            'audio/mp4',
                            'audio/m4a',
                            'audio/aac',
                            'audio/ogg',
                        ])
                        ->maxSize(204800)
                        ->required()
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            // Only analyze freshly uploaded temp files — not existing R2 paths on edit hydrate.
                            if (! ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                && ! (is_array($state) && ($state[0] ?? null) instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                            ) {
                                return;
                            }

                            $meta = AudioMetadata::fromUpload($state, 'r2');

                            if ($meta['duration'] !== null) {
                                $set('duration', $meta['duration']);
                            }

                            if ($meta['file_size'] !== null) {
                                $set('file_size', $meta['file_size']);
                            }
                        })
                        ->columnSpanFull(),
                ),
                Hidden::make('duration')->dehydrated(),
                Hidden::make('file_size')->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['surah', 'reviewNotes.user']))
            ->defaultSort('surah_id')
            ->emptyStateHeading('No recitations yet')
            ->columns([
                TextColumn::make('surah.number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('surah.name_english')
                    ->label('Surah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RecitationStatus $state): string => $state->label())
                    ->color(fn (RecitationStatus $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(function (?int $state): string {
                        if ($state === null) {
                            return '—';
                        }

                        return sprintf('%d:%02d', intdiv($state, 60), $state % 60);
                    })
                    ->sortable(),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(function (?int $state): string {
                        if ($state === null) {
                            return '—';
                        }

                        if ($state >= 1_048_576) {
                            return number_format($state / 1_048_576, 1).' MB';
                        }

                        return number_format($state / 1024, 1).' KB';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([10, 25, 50, 114])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')
                    ->options(RecitationStatus::options()),
                SelectFilter::make('surah_id')
                    ->label('Surah')
                    ->options(fn (): array => Surah::query()
                        ->orderBy('number')
                        ->get()
                        ->mapWithKeys(fn (Surah $surah): array => [
                            $surah->id => sprintf('%d. %s', $surah->number, $surah->name_english),
                        ])
                        ->all())
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add surah')
                    ->createAnother(false)
                    // Empty URL = no redirect (avoids Filament null Livewire crash after modal save).
                    ->successRedirectUrl(fn (): string => '')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data = $this->applyAudioMetadata($data);
                        $data['created_by'] = Auth::id();
                        $data['status'] = RecitationStatus::Draft;

                        return $data;
                    })
                    ->after(function (): void {
                        Notification::make()
                            ->title('Surah added')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActionsColumnLabel('Actions')
            ->recordActions([
                Action::make('notes')
                    ->label('Notes')
                    ->icon('heroicon-o-microphone')
                    ->visible(fn (Recitation $record): bool => $record->reviewNotes()->exists())
                    ->modalHeading('Review voice notes')
                    ->modalSubmitAction(false)
                    ->form([
                        ViewField::make('notes')
                            ->view('filament.forms.review-notes')
                            ->viewData(fn (Recitation $record): array => [
                                'notes' => $record->reviewNotes()->with('user')->get(),
                            ])
                            ->dehydrated(false),
                    ]),
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Recitation $record): bool => Auth::user()?->can('submit', $record) ?? false)
                    ->action(function (Recitation $record): void {
                        $record->update([
                            'status' => RecitationStatus::PendingReview,
                            'submitted_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Submitted for review')
                            ->success()
                            ->send();
                    }),
                Action::make('play')
                    ->label('Audio')
                    ->icon('heroicon-o-speaker-wave')
                    ->url(fn (Recitation $record): ?string => MediaUrl::temporary('r2', $record->audio_url))
                    ->openUrlInNewTab()
                    ->visible(fn (Recitation $record): bool => filled($record->audio_url)),
                EditAction::make()
                    ->successRedirectUrl(fn (): string => '')
                    ->mutateFormDataUsing(function (array $data, Recitation $record): array {
                        return $this->applyAudioMetadata($data, $record);
                    }),
                DeleteAction::make()
                    ->successRedirectUrl(fn (): string => ''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    /**
     * @return array<int|string, string>
     */
    protected function availableSurahOptions(): array
    {
        $owner = $this->getOwnerRecord();
        $usedSurahIds = $owner->recitations()->pluck('surah_id');

        $current = $this->getMountedTableActionRecord();
        if ($current?->surah_id) {
            $usedSurahIds = $usedSurahIds->reject(
                fn ($id): bool => (int) $id === (int) $current->surah_id,
            );
        }

        return Surah::query()
            ->when(
                $usedSurahIds->isNotEmpty(),
                fn (Builder $query): Builder => $query->whereNotIn('id', $usedSurahIds),
            )
            ->orderBy('number')
            ->get()
            ->mapWithKeys(fn (Surah $surah): array => [
                $surah->id => sprintf(
                    '%d. %s — %s',
                    $surah->number,
                    $surah->name_english,
                    $surah->name_arabic,
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyAudioMetadata(array $data, ?Recitation $existing = null): array
    {
        $audio = $data['audio_url'] ?? null;

        if (is_array($audio)) {
            $audio = $audio[0] ?? null;
        }

        $isNewUpload = $audio instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
        $audioUnchanged = $existing && is_string($audio) && $audio === $existing->audio_url;

        if ($audioUnchanged || (! $isNewUpload && $existing && is_string($audio))) {
            // Existing R2 path on edit — never wipe stored duration/size.
            $data['duration'] = $existing->duration;
            $data['file_size'] = $existing->file_size;

            return $data;
        }

        $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');

        $data['duration'] = $meta['duration'] ?? $existing?->duration ?? ($data['duration'] ?? null);
        $data['file_size'] = $meta['file_size'] ?? $existing?->file_size ?? ($data['file_size'] ?? null);

        return $data;
    }
}
