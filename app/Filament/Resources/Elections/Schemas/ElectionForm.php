<?php

namespace App\Filament\Resources\Elections\Schemas;

use App\Enums\TouchMode;
use App\Models\Election;
use App\Support\LogoImage;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** Kartu SCR-A5 — Pengaturan Pemilihan. */
class ElectionForm
{
    public const SCHOOL_YEAR_MESSAGE = 'Tahun ajaran harus berformat 2026/2027.';

    public const LOGO_MESSAGE = 'Logo harus PNG, JPG, atau WebP, maksimal 2 MB.';

    public const INSTANT_WARNING = 'Pada mode 1x sentuh, sentuhan tidak sengaja langsung menjadi suara dan tidak bisa dibatalkan.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pemilihan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama pemilihan')
                            ->helperText('Tampil di kepala layar IFP.')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('school_year')
                            ->label('Tahun ajaran')
                            ->placeholder('2026/2027')
                            ->required()
                            ->rules([fn (): Closure => self::schoolYearRule()]),
                    ]),

                Section::make('Layar IFP')
                    ->schema([
                        Radio::make('touch_mode')
                            ->label('Mode sentuh')
                            ->options(TouchMode::class)
                            ->default(TouchMode::Confirm)
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get): ?string => self::isInstant($get('touch_mode')) ? self::INSTANT_WARNING : null),
                        Radio::make('reset_delay_seconds')
                            ->label('Jeda layar terima kasih')
                            ->options([2 => '2 detik', 3 => '3 detik', 5 => '5 detik'])
                            ->default(3)
                            ->inline()
                            ->required(),
                        Toggle::make('sound_enabled')
                            ->label('Bunyi bel saat suara tercatat')
                            ->default(true),
                        Toggle::make('confetti_enabled')
                            ->label('Confetti saat suara tercatat')
                            ->default(true),
                    ]),

                // SPEC v1.3 REQ-24 — logo tampil di kepala layar IFP dan kop berita acara.
                Section::make('Logo')
                    ->description('Tampil di kepala layar IFP dan kop berita acara. Tanpa logo, layar menampilkan kotak "OSIS".')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo sekolah / OSIS')
                            ->helperText('PNG, JPG, atau WebP, maksimal 2 MB. PNG dengan latar transparan disarankan. Disimpan maks. 512 piksel.')
                            ->image()
                            ->disk(LogoImage::DISK)
                            ->directory(LogoImage::DIRECTORY)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(2048)
                            ->imagePreviewHeight('120')
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(LogoImage::class)->store($file))
                            ->validationMessages([
                                'image' => self::LOGO_MESSAGE,
                                'mimetypes' => self::LOGO_MESSAGE,
                                'mimes' => self::LOGO_MESSAGE,
                                'max' => self::LOGO_MESSAGE,
                            ]),
                    ]),

                // SPEC v1.1 REQ-16 — keputusan pemilik CP5.
                Section::make('Berita acara')
                    ->description('Isian yang dikosongkan dicetak sebagai garis untuk diisi tangan.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('school_name')
                            ->label('Nama sekolah (kop)')
                            ->maxLength(150),
                        TextInput::make('signing_place')
                            ->label('Tempat penandatanganan')
                            ->placeholder('Sintang')
                            ->maxLength(60),
                        TextInput::make('school_address')
                            ->label('Alamat sekolah (kop)')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Repeater::make('signatories')
                            ->label('Penandatangan')
                            ->schema([
                                TextInput::make('role')->label('Jabatan')->required()->maxLength(60),
                                TextInput::make('name')->label('Nama')->maxLength(100),
                            ])
                            ->columns(2)
                            ->default(Election::DEFAULT_SIGNATORIES)
                            ->maxItems(8)
                            ->reorderable()
                            ->addActionLabel('Tambah penandatangan')
                            ->columnSpanFull(),
                        Toggle::make('witness_signatures')
                            ->label('Kolom tanda tangan saksi untuk setiap paslon')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** Format "2026/2027" dan tahun kedua = tahun pertama + 1. */
    public static function schoolYearRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)
                || ! preg_match('/^(\d{4})\/(\d{4})$/', $value, $m)
                || (int) $m[2] !== (int) $m[1] + 1) {
                $fail(self::SCHOOL_YEAR_MESSAGE);
            }
        };
    }

    private static function isInstant(mixed $state): bool
    {
        return $state === TouchMode::Instant || $state === TouchMode::Instant->value;
    }
}
