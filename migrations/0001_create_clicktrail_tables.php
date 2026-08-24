<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicktrail_attribution_records', static function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_id', 128)->index();
            $table->string('first_source')->nullable();
            $table->string('first_medium')->nullable();
            $table->string('first_campaign')->nullable();
            $table->string('first_channel')->nullable()->index();
            $table->timestamp('first_touch_at')->nullable();
            $table->string('last_source')->nullable();
            $table->string('last_medium')->nullable();
            $table->string('last_campaign')->nullable();
            $table->string('last_channel')->nullable()->index();
            $table->timestamp('last_touch_at')->nullable()->index();

            // Normalized consent snapshot at last permitted touch
            // (analytics_storage / advertising_storage / ad_user_data /
            // ad_personalization => granted|denied|unknown|not_applicable).
            $table->json('consent_snapshot')->nullable();

            $table->timestamps();
        });

        Schema::create('clicktrail_diagnostics', static function (Blueprint $table): void {
            $table->id();
            $table->string('reason_key', 128)->unique();
            $table->unsignedBigInteger('count')->default(0);
            $table->json('detail')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicktrail_diagnostics');
        Schema::dropIfExists('clicktrail_attribution_records');
    }
};
