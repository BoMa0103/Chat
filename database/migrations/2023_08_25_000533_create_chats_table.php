<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id_first')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id_second')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
        // Создание триггера для проверки уникальности комбинации ключей
        DB::unprepared('
            CREATE TRIGGER unique_combination BEFORE INSERT ON chats
            FOR EACH ROW
            BEGIN
                IF EXISTS (
                    SELECT * FROM chats WHERE
                    (user_id_first = NEW.user_id_first AND user_id_second = NEW.user_id_second)
                    OR
                    (user_id_first = NEW.user_id_second AND user_id_second = NEW.user_id_first)
                ) THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Duplicate combination of keys";
                END IF;
            END;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS unique_combination');
        Schema::dropIfExists('chats');
    }
};
