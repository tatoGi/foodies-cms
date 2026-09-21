<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('languages', 'country_code')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->string('country_code', 2)->nullable()->after('code');
                $table->index('country_code');
            });
        }

        if (Schema::hasColumn('languages', 'country_code')) {
            $defaultByLocale = [
                'ka' => 'GE',
                'en' => 'US',
                'ru' => 'RU',
                'tr' => 'TR',
                'de' => 'DE',
                'fr' => 'FR',
            ];

            $languages = DB::table('languages')
                ->select('id', 'code', 'country_code')
                ->get();

            foreach ($languages as $language) {
                $current = trim((string) ($language->country_code ?? ''));
                if ($current !== '') {
                    continue;
                }

                $localeCode = strtolower(trim((string) ($language->code ?? '')));
                $countryCode = $defaultByLocale[$localeCode] ?? strtoupper(substr($localeCode, 0, 2));
                if (strlen($countryCode) !== 2) {
                    $countryCode = 'US';
                }

                DB::table('languages')
                    ->where('id', $language->id)
                    ->update(['country_code' => $countryCode]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('languages', 'country_code')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropIndex('languages_country_code_index');
                $table->dropColumn('country_code');
            });
        }
    }
};
