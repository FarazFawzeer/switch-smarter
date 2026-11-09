<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Technician team: PPM (route-based), Breakdown & Repair (no fixed route), or both
        Schema::table('users', function (Blueprint $table) {
            $table->string('work_category', 20)->default('both')->after('type');
        });

        // ---------- Stores: item master ----------
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('part_name', 191);
            $table->string('category', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('part_number', 100)->nullable()->index();
            $table->string('unit', 30)->default('Nos');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('stock_qty', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->string('store_location', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ---------- Material requests (MRN) ----------
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('mrn_no', 30)->nullable()->unique(); // issued when approved
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('elevator_unit_id')->nullable()->constrained('elevator_units')->nullOnDelete();
            $table->decimal('quantity_requested', 12, 2);
            $table->decimal('quantity_issued', 12, 2)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending / approved / rejected / issued / cancelled
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_remarks', 500)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->string('issue_remarks', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // ---------- Stock movements (history for every item) ----------
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 20); // receive / issue / adjust
            $table->decimal('quantity', 12, 2); // + in, - out
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('balance_after', 12, 2);
            $table->foreignId('material_request_id')->nullable()->constrained('material_requests')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inventory_item_id', 'created_at']);
        });

        // New shift timings (Revision 8)
        $now = now();
        foreach ([['Morning', '08:00:00', '17:00:00'], ['Evening', '16:00:00', '01:00:00'], ['Night', '23:00:00', '08:00:00']] as [$name, $start, $end]) {
            $exists = DB::table('shifts')->where('name', $name)->exists();
            $exists
                ? DB::table('shifts')->where('name', $name)->update(['start_time' => $start, 'end_time' => $end, 'updated_at' => $now])
                : DB::table('shifts')->insert(['name' => $name, 'start_time' => $start, 'end_time' => $end, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('inventory_items');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('work_category');
        });

        DB::table('shifts')->where('name', 'Evening')->update(['start_time' => '13:00:00', 'end_time' => '21:00:00']);
        DB::table('shifts')->where('name', 'Night')->update(['start_time' => '21:00:00', 'end_time' => '05:00:00']);
    }
};
