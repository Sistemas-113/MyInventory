<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('total_paid', 20, 2)->default(0)->after('amount');
        });
    }

    public function down()
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn('total_paid');
        });
    }
};
