#!/bin/bash
docker build --rm=true --pull --no-cache --build-arg BRANCH="develop" --build-arg DB_PASS="pandora" -t pandorafms/pandorafms:7 . && \
[ "$QA_ENV" == "" ] && \
docker push pandorafms/pandorafms:7
