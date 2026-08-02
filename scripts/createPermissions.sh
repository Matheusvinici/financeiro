#!/bin/bash
php artisan db:seed --class=PermissionSeeder
bash clearCaches.sh
