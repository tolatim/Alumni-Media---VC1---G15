#!/bin/sh

if [ ! -d "vendor" ]; then
  composer install
fi

php-fpm
