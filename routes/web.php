<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Runtime DB connectivity probe for Vessl MySQL add-on smoke tests.
 *
 * Proves the deployed Laravel app actually reaches the linked MySQL
 * service with the injected DB_* credentials — not just that the env
 * vars are present. Exercises read (SELECT VERSION/DATABASE/USER) plus a
 * full DDL + write + read round-trip through the DB facade.
 */
Route::get('/db-check', function () {
    try {
        $info = DB::selectOne('select version() as version, database() as db, current_user() as user');

        DB::statement('create table if not exists vessl_smoke (id int auto_increment primary key, created_at timestamp default current_timestamp)');
        DB::table('vessl_smoke')->insert(['created_at' => now()]);
        $count = DB::table('vessl_smoke')->count();

        return response()->json([
            'ok'         => true,
            'connection' => config('database.default'),
            'driver'     => DB::connection()->getDriverName(),
            'version'    => $info->version,
            'database'   => $info->db,
            'user'       => $info->user,
            'rows'       => $count,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'      => false,
            'error'   => $e->getMessage(),
            'db_host' => env('DB_HOST'),
            'db_conn' => env('DB_CONNECTION'),
        ], 500);
    }
});
