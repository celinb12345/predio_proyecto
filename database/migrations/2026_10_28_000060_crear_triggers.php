<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            -- 1) updated_at se actualiza solo en cada UPDATE (también en inserciones SQL directas)
            CREATE OR REPLACE FUNCTION fn_set_updated_at()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                NEW.updated_at := now();
                RETURN NEW;
            END;
            $$;

            DO $$
            DECLARE
                t RECORD;
            BEGIN
                FOR t IN
                    SELECT table_name::text AS table_name
                    FROM information_schema.columns
                    WHERE table_schema = 'public' AND column_name = 'updated_at'
                LOOP
                    EXECUTE format('DROP TRIGGER IF EXISTS trg_%s_updated_at ON %I',
                                   t.table_name, t.table_name);
                    EXECUTE format(
                        'CREATE TRIGGER trg_%s_updated_at BEFORE UPDATE ON %I
                         FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at()',
                        t.table_name, t.table_name);
                END LOOP;
            END
            $$;

            -- 2) Al registrar una liberación, la fecha queda inactiva (RF16)
            CREATE OR REPLACE FUNCTION fn_desactivar_fecha_liberada()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                UPDATE solicitud_predios
                   SET activo = FALSE
                 WHERE id = NEW.solicitud_predio_id;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER trg_liberaciones_desactiva_fecha
                AFTER INSERT ON liberaciones_fecha
                FOR EACH ROW
                EXECUTE FUNCTION fn_desactivar_fecha_liberada();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_liberaciones_desactiva_fecha ON liberaciones_fecha;
            DROP FUNCTION IF EXISTS fn_desactivar_fecha_liberada();
            DROP FUNCTION IF EXISTS fn_set_updated_at();
            SQL);
    }
};