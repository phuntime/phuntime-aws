#!/usr/bin/env bash
set -eou pipefail

while [[ "$#" -gt 0 ]]; do
    case $1 in
        --php-version) php_version="$2"; shift ;;
        --swoole-version) swoole_version="$2"; shift ;;
        --bison-version) bison_version="$2"; shift ;;
        --output|-o) output_dir="$2"; shift ;;
        --fpm) build_fpm="$2"; shift ;;
        *) echo "Unknown parameter passed: $1"; exit 1 ;;
    esac
    shift
done

printf "[build.sh] Build START\n"
rm -rf ./build

docker build -t="phuntime-lambda-build" \
  --target build \
  --build-arg PHP_VERSION="${php_version}" \
  --build-arg BISON_VERSION="${bison_version}" \
  --build-arg SWOOLE_VERSION="${swoole_version}" .
CONTAINER_ID=$(docker run -it -d phuntime-lambda-build:latest)

printf "[build.sh][debug] Checking PHP version\n"
docker exec -it "$CONTAINER_ID" /opt/bin/php -v

printf "[build.sh][debug] Checking Swoole version\n"
docker exec -it $CONTAINER_ID /opt/bin/php -i | grep swoole

printf "[build.sh][debug] list installed extensions\n"
docker exec -it $CONTAINER_ID /opt/bin/php -m
#
printf "[build.sh][debug] Copying artifacts to ${output_dir}\n"
mkdir -p $output_dir/bin
mkdir -p $output_dir/php
mkdir -p $output_dir/phuntime
mkdir -p $output_dir/php/etc
mkdir -p $output_dir/sbin
docker cp $CONTAINER_ID:/opt/php/ext $output_dir/php
docker cp $CONTAINER_ID:/opt/bin/php $output_dir/bin
docker cp $CONTAINER_ID:/opt/sbin/php-fpm $output_dir/sbin/php-fpm
cp bootstrap $output_dir
#
cp -R phuntime $output_dir
cp php.ini $output_dir/php
cp php-fpm.conf $output_dir/php/etc/
#
#
#printf "[build.sh][debug] add fpm related things\n"
#cp ./fpm-bootstrap $output_dir/bootstrap
#cp -r ./../../src $output_dir
#cp ./../../composer.json $output_dir
#cp ./../../composer.lock $output_dir
#(cd $output_dir && composer install --no-dev --optimize-autoloader)
#
#printf "[build.sh][debug] fix file permissions\n"
#chmod +x $output_dir/bootstrap
#chmod +x $output_dir/bin/php
#chmod +x $output_dir/bin/php-fpm

(cd $output_dir && zip -r layer.zip .)