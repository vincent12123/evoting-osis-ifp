<?php

namespace App\Filament\Resources\Candidates\Schemas;

use App\Enums\CandidateAccent;
use App\Models\Candidate;
use App\Models\Election;
use App\Observers\CandidateObserver;
use App\Support\CandidatePhoto;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** Kartu SCR-A3 — form paslon. Teks validasi persis dari kartu. */
class CandidateForm
{
    public const PHOTO_MESSAGE = 'Foto harus JPG, PNG, atau WebP, maksimal 3 MB.';

    public const MISSIONS_MESSAGE = 'Isi minimal 1 butir misi.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paslon')
                    ->columns(2)
                    ->schema([
                        Select::make('number')
                            ->label('Nomor urut')
                            ->options(collect(range(1, CandidateObserver::MAX_CANDIDATES))
                                ->mapWithKeys(fn (int $n) => [$n => str_pad((string) $n, 2, '0', STR_PAD_LEFT)])
                                ->all())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, $state) => $state ? $set('accent', "paslon-{$state}") : null)
                            ->rules([fn (?Candidate $record): Closure => self::numberRule($record)]),
                        Select::make('accent')
                            ->label('Warna aksen')
                            ->helperText('Dipakai pada nomor dan tombol PILIH paslon ini di layar IFP.')
                            ->options(CandidateAccent::class)
                            ->required()
                            ->rules([fn (?Candidate $record): Closure => self::accentRule($record)]),
                        TextInput::make('chair_name')->label('Nama calon ketua')->required()->maxLength(60),
                        TextInput::make('chair_class')->label('Kelas calon ketua')->maxLength(20),
                        TextInput::make('vice_name')->label('Nama calon wakil ketua')->required()->maxLength(60),
                        TextInput::make('vice_class')->label('Kelas calon wakil ketua')->maxLength(20),
                    ]),

                Section::make('Visi, misi & slogan')
                    ->schema([
                        TextInput::make('tagline')->label('Slogan')->required()->maxLength(100),
                        Textarea::make('vision')->label('Visi')->required()->maxLength(500)->rows(3),
                        Repeater::make('missions')
                            ->label('Misi')
                            ->simple(TextInput::make('text')->label('Butir misi')->required()->maxLength(200))
                            ->required()
                            ->minItems(1)
                            ->maxItems(7)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah butir misi')
                            ->reorderable()
                            ->validationMessages([
                                'required' => self::MISSIONS_MESSAGE,
                                'min' => self::MISSIONS_MESSAGE,
                                'max' => 'Maksimal 7 butir misi.',
                            ]),
                    ]),

                Section::make('Foto')
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Foto paslon')
                            ->helperText('JPG, PNG, atau WebP, maksimal 3 MB. Foto dipotong ke rasio 4:3 dan disimpan sebagai WebP.')
                            ->image()
                            ->disk(CandidatePhoto::DISK)
                            ->directory(CandidatePhoto::DIRECTORY)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(3072)
                            ->required()
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(CandidatePhoto::class)->store($file))
                            ->validationMessages([
                                'required' => 'Foto paslon wajib diunggah.',
                                'image' => self::PHOTO_MESSAGE,
                                'mimetypes' => self::PHOTO_MESSAGE,
                                'mimes' => self::PHOTO_MESSAGE,
                                'max' => self::PHOTO_MESSAGE,
                            ]),
                    ]),
            ]);
    }

    /** Nomor urut unik per pemilihan + batas 6 paslon (AC-14, AC-16). */
    public static function numberRule(?Candidate $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $electionId = $record?->election_id ?? Election::active()?->id;

            $query = Candidate::query()->where('election_id', $electionId);

            if ($record === null && (clone $query)->count() >= CandidateObserver::MAX_CANDIDATES) {
                $fail('Maksimal 6 paslon.');

                return;
            }

            $taken = $query->where('number', (int) $value)
                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                ->exists();

            if ($taken) {
                $fail('Nomor urut '.str_pad((string) $value, 2, '0', STR_PAD_LEFT).' sudah dipakai.');
            }
        };
    }

    /** Warna aksen unik per pemilihan. */
    public static function accentRule(?Candidate $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $electionId = $record?->election_id ?? Election::active()?->id;
            $value = $value instanceof CandidateAccent ? $value->value : $value;

            $taken = Candidate::query()
                ->where('election_id', $electionId)
                ->where('accent', $value)
                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                ->exists();

            if ($taken) {
                $fail('Warna aksen ini sudah dipakai paslon lain.');
            }
        };
    }
}
