<?php

use Illuminate\Support\Facades\Schedule;

// ********************* DELETE OLD PRODUCTS JOB ********************* //
Schedule::command('products:delete-old')->monthly();
