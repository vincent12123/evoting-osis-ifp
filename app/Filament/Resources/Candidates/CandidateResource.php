<?php

namespace App\Filament\Resources\Candidates;

use App\Exceptions\CandidatesLockedException;
use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\Pages\ViewCandidate;
use App\Filament\Resources\Candidates\Schemas\CandidateForm;
use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use App\Models\Candidate;
use App\Models\Election;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Kartu SCR-A3 — paslon milik pemilihan aktif. */
class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'paslon';

    protected static ?string $pluralModelLabel = 'Paslon';

    protected static ?string $navigationLabel = 'Paslon';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CandidateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CandidatesTable::configure($table);
    }

    /** Hanya paslon pemilihan aktif yang tampil & bisa dibuka (juga lewat URL langsung). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('election_id', Election::active()?->id ?? 0);
    }

    /** Teks banner kunci/peringatan untuk halaman paslon, atau null. */
    public static function statusNotice(): ?string
    {
        $election = Election::active();

        if ($election === null) {
            return 'Belum ada pemilihan aktif. Buat atau aktifkan pemilihan di menu Pemilihan.';
        }

        return $election->status->locksCandidates()
            ? CandidatesLockedException::messageFor($election->status)
            : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidates::route('/'),
            'create' => CreateCandidate::route('/create'),
            'view' => ViewCandidate::route('/{record}'),
            'edit' => EditCandidate::route('/{record}/edit'),
        ];
    }
}
