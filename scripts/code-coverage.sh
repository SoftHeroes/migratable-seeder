#!/usr/bin/env sh

vendor/bin/phpunit \
    --colors=never \
    --coverage-text \
    --coverage-html coverage
