#!/bin/bash

# Iniciar o PHP-FPM
php-fpm -D

# Iniciar o Nginx
nginx -g 'daemon off;'
