<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyMinute();
Schedule::command('push:reminders')->everyMinute();
