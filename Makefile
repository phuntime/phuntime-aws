PHP_84_VERSION = "8.4.6"
PHP_83_VERSION = "8.3.7"
BISON_VERSION = "3.8.2"
SWOOLE_VERSION = "6.0.2"


aws-83-fpm:
	 ./build.sh \
 		--php-version ${PHP_83_VERSION} \
 		--bison-version ${BISON_VERSION} \
 		--swoole-version ${SWOOLE_VERSION} \
 		--output $(shell pwd)/build/aws-runtime

aws-84-fpm:
	 ./build.sh \
 		--php-version ${PHP_84_VERSION} \
 		--bison-version ${BISON_VERSION} \
 		--swoole-version ${SWOOLE_VERSION} \
 		--output $(shell pwd)/build/aws-runtime

runtime:
	docker build -t="phuntime-runtime" \
      --target runtime \
      --build-arg PHP_VERSION="${PHP_84_VERSION}" \
      --build-arg BISON_VERSION="${bison_version}" \
      --build-arg SWOOLE_VERSION=${SWOOLE_VERSION} .
