FROM arm64v8/amazonlinux:2023.3.20240131.0 as build

ARG PHP_VERSION
ARG BISON_VERSION
ARG SWOOLE_VERSION
#
RUN yum update -y
RUN yum clean all
RUN yum groupinstall -y "Development Tools"
RUN yum install tar gzip re2c libxml2-devel openssl-devel sqlite-devel libcurl-devel readline-devel  -y  #   glibc  tar gzip cmake texinfo autoconf gcc gcc-c++ libcurl-devel

ENV BUILD_DIR="/tmp/build"
ENV INI_FILE_LOCATION="/opt/php/php.ini"
ENV FPM_CONF_LOCATION="/opt/php/etc/php-fpm.conf"

WORKDIR ${BUILD_DIR}

## Download the PHP source
RUN curl -vsL https://github.com/php/php-src/archive/php-${PHP_VERSION}.tar.gz | tar -xvz
WORKDIR ${BUILD_DIR}/php-src-php-${PHP_VERSION}
RUN ./buildconf --force
RUN EXTENSION_DIR=/opt/php/ext ./configure --prefix=/opt/ \
    --enable-fpm \
    --enable-sockets \
    --enable-pcntl \
    --with-openssl \
    --with-curl \
#    --with-zip \ #TODO add libzip
#    --with-sodium \ #TODO add libsodium
    --with-gettext \
#    --with-gmp \ #TODO add GNU MP
    --with-zlib \
    --disable-short-tags \
    --without-pear \
    --with-readline \
    --with-config-file-path=/opt/php \
    --with-mysqli=mysqlnd \
    --with-pdo-mysql=mysqlnd

RUN make -j4 && make install

COPY php.ini ${INI_FILE_LOCATION}
COPY php-fpm.conf /opt/php/etc/php-fpm.conf

ENV PATH="$PATH:/opt/bin"
#verify php build
RUN php -v

WORKDIR ${BUILD_DIR}
RUN curl -vsL https://github.com/swoole/swoole-src/archive/refs/tags/v${SWOOLE_VERSION}.tar.gz | tar -xvz
WORKDIR ${BUILD_DIR}/swoole-src-${SWOOLE_VERSION}
RUN phpize
RUN ./configure
RUN make
RUN make install

FROM build as runtime
RUN mkdir -p ~/.aws-lambda-rie \
    && curl -Lo ~/.aws-lambda-rie/aws-lambda-rie https://github.com/aws/aws-lambda-runtime-interface-emulator/releases/latest/download/aws-lambda-rie-arm64 \
    && mv ~/.aws-lambda-rie/aws-lambda-rie /opt/bin/aws-lambda-rie

COPY bootstrap /opt/
COPY emulator/runtime.sh /opt/bin
COPY emulator/server /opt/server
COPY phuntime /opt/phuntime

COPY resources/fpm-function /var/task

RUN chmod +x /opt/bin/runtime.sh
RUN chmod +x /opt/bin/aws-lambda-rie

CMD /opt/bin/runtime.sh
