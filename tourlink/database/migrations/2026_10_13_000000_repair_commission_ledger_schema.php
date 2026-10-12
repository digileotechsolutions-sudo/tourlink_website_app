<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $bookingUnique = collect(Schema::getIndexes('commissions'))->first(
            static fn (array $index): bool => $index['unique'] && $index['columns'] === ['booking_id'],
        );

        if ($bookingUnique) {
            Schema::table('commissions', function (Blueprint $table) use ($bookingUnique): void {
                $table->dropUnique($bookingUnique['name']);
            });
        }

        $columns = Schema::getColumnListing('commissions');
        $indexes = Schema::getIndexes('commissions');
        $foreignKeys = Schema::getForeignKeys('commissions');
        $indexedColumns = collect($indexes)->flatMap(static fn (array $index): array => $index['columns'])->all();
        $uniqueColumns = collect($indexes)
            ->filter(static fn (array $index): bool => $index['unique'])
            ->flatMap(static fn (array $index): array => $index['columns'])
            ->all();
        $foreignColumns = collect($foreignKeys)->flatMap(static fn (array $foreignKey): array => $foreignKey['columns'])->all();

        Schema::table('commissions', function (Blueprint $table) use ($columns, $indexedColumns, $uniqueColumns, $foreignColumns): void {
            if (! in_array('event_type', $columns, true)) {
                $table->string('event_type', 30)->default('LEGACY');
            }

            if (! in_array('payment_id', $columns, true)) {
                $table->string('payment_id', 36)->nullable();
            }

            if (! in_array('refund_id', $columns, true)) {
                $table->string('refund_id', 36)->nullable();
            }

            if (! in_array('event_key', $columns, true)) {
                $table->string('event_key', 80)->nullable();
            }

            if (! in_array('payment_id', $indexedColumns, true)) {
                $table->index('payment_id');
            }

            if (! in_array('refund_id', $uniqueColumns, true)) {
                $table->unique('refund_id');
            }

            if (! in_array('event_key', $uniqueColumns, true)) {
                $table->unique('event_key');
            }

            if (! in_array('payment_id', $foreignColumns, true)) {
                $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            }

            if (! in_array('refund_id', $foreignColumns, true)) {
                $table->foreign('refund_id')->references('id')->on('refunds')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
    }
};
