#!/bin/bash
docker build --rm=true --build-arg BRANCH="develop" --build-arg DB_PASS="pandora" -t pandorafms/pandorafms:7 . && \
docker push pandorafms/pandorafms:7
