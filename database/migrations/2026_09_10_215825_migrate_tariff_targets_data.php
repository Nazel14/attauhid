<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tariffs = DB::table('tariffs')->get();

        foreach ($tariffs as $t) {
            if ($t->target_student_type === 'Donasi') {
                DB::table('tariffs')
                    ->where('id', $t->id)
                    ->update(['is_donation' => true]);
                continue;
            }

            if (in_array($t->target_student_type, ['Reguler', 'Fullday'], true)) {
                DB::table('tariff_targets')->insert([
                    'tariff_id'    => $t->id,
                    'target_type'  => 'student_type',
                    'target_value' => $t->target_student_type,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // 'All' sengaja dilewat: tanpa aturan = berlaku ke semua santri aktif
        }

        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn('target_student_type');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->enum('target_student_type', ['Reguler', 'Fullday', 'All', 'Donasi'])
                  ->default('All')
                  ->after('nominal');
        });

        DB::table('tariffs')->where('is_donation', true)
            ->update(['target_student_type' => 'Donasi']);

        $rules = DB::table('tariff_targets')
            ->where('target_type', 'student_type')
            ->get();

        foreach ($rules as $rule) {
            DB::table('tariffs')
                ->where('id', $rule->tariff_id)
                ->update(['target_student_type' => $rule->target_value]);
        }

        DB::table('tariff_targets')->where('target_type', 'student_type')->delete();
    }
};