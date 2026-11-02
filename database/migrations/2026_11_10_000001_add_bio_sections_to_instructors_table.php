<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            // The page's «نبذة» as sections: a list of {title, body} (body is HTML cleaned by
            // App\Support\SafeHtml). `bio` stays as the plain text of the first section.
            $table->json('bio_sections')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn('bio_sections');
        });
    }
};
