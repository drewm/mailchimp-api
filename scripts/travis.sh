#!/bin/bash

cd ${TRAVIS_BUILD_DIR}

if [[ $TRAVIS_PHP_VERSION = '7.1' ]]; then
    phpunit
fi