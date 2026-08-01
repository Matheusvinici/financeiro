source .env

ENVIRONMENT=${APP_ENV:-production}

if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
    php artisan optimize:clear && 
    php artisan permission:cache-reset && 
    php artisan config:cache && 
    php artisan route:cache && 
    php artisan view:cache
    # rm -rf storage/framework/sessions/*
elif [ "$ENVIRONMENT" = "local" ]; then
    php artisan optimize:clear &&
    php artisan permission:cache-reset
    # rm -rf storage/framework/sessions/*
else
    echo "Ambiente desconhecido: $ENVIRONMENT"
    exit 1
fi