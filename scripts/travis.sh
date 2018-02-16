#!/usr/bin/env bash

# e causes to exit when one commands returns non-zero
# v prints every line before executing
set -ev

cd ${TRAVIS_BUILD_DIR}

if [[ $TRAVIS_PHP_VERSION = '7.1' ]]
then
    phpunit
fi