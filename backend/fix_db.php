<?php
use Illuminate\Support\Facades\Schema;

Schema::table('notifications', function ($table) {
    if (!Schema::hasColumn('notifications', 'notifiable_type')) {
        $table->string('notifiable_type')->default('App\\\\Models\\\\User');
        $table->renameColumn('user_id', 'notifiable_id');
    }
});
echo "Done\n";