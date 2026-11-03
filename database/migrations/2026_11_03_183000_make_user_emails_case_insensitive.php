<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An e-mail names the same account whatever its case: the e-mails already stored move to lower case, as the
 * application now stores them, and a unique index on lower(email) refuses two differing only in case. The plain
 * unique index on "email" stays, since the sign-in looks the e-mail up as it is stored. Rolling back drops the new
 * index; the e-mails stay in lower case.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws RuntimeException when accounts differ only in the case of their e-mail, which must be merged by hand
     */
    public function up(): void
    {
        $sharedEmails = DB::table('users')->selectRaw('lower(email) AS email')->groupByRaw('lower(email)')->havingRaw('count(*) > 1')->pluck('email');
        if ($sharedEmails->isNotEmpty()) {
            throw new RuntimeException('Accounts differ only in the case of their e-mail: '.$sharedEmails->implode(', ').'. Merge or delete them before migrating.');
        }

        DB::table('users')->whereRaw('email <> lower(email)')->update(['email' => DB::raw('lower(email)')]);
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX users_email_lower_unique');
    }
};
