#!/usr/bin/env bash

set -e
set -x

CURRENT_BRANCH="13.x"

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 || true
}

git pull origin $CURRENT_BRANCH

remote auth git@github.com:heritage/auth.git
remote broadcasting git@github.com:heritage/broadcasting.git
remote bus git@github.com:heritage/bus.git
remote cache git@github.com:heritage/cache.git
remote collections git@github.com:heritage/collections.git
remote conditionable git@github.com:heritage/conditionable.git
remote concurrency git@github.com:heritage/concurrency.git
remote config git@github.com:heritage/config.git
remote console git@github.com:heritage/console.git
remote container git@github.com:heritage/container.git
remote contracts git@github.com:heritage/contracts.git
remote cookie git@github.com:heritage/cookie.git
remote database git@github.com:heritage/database.git
remote encryption git@github.com:heritage/encryption.git
remote events git@github.com:heritage/events.git
remote filesystem git@github.com:heritage/filesystem.git
remote hashing git@github.com:heritage/hashing.git
remote http git@github.com:heritage/http.git
remote image git@github.com:heritage/image.git
remote json-schema git@github.com:heritage/json-schema.git
remote log git@github.com:heritage/log.git
remote macroable git@github.com:heritage/macroable.git
remote mail git@github.com:heritage/mail.git
remote notifications git@github.com:heritage/notifications.git
remote pagination git@github.com:heritage/pagination.git
remote pipeline git@github.com:heritage/pipeline.git
remote process git@github.com:heritage/process.git
remote queue git@github.com:heritage/queue.git
remote reflection git@github.com:heritage/reflection.git
remote redis git@github.com:heritage/redis.git
remote routing git@github.com:heritage/routing.git
remote session git@github.com:heritage/session.git
remote support git@github.com:heritage/support.git
remote testing git@github.com:heritage/testing.git
remote translation git@github.com:heritage/translation.git
remote validation git@github.com:heritage/validation.git
remote view git@github.com:heritage/view.git

split 'src/Heritage/Auth' auth
split 'src/Heritage/Broadcasting' broadcasting
split 'src/Heritage/Bus' bus
split 'src/Heritage/Cache' cache
split 'src/Heritage/Collections' collections
split 'src/Heritage/Conditionable' conditionable
split 'src/Heritage/Concurrency' concurrency
split 'src/Heritage/Config' config
split 'src/Heritage/Console' console
split 'src/Heritage/Container' container
split 'src/Heritage/Contracts' contracts
split 'src/Heritage/Cookie' cookie
split 'src/Heritage/Database' database
split 'src/Heritage/Encryption' encryption
split 'src/Heritage/Events' events
split 'src/Heritage/Filesystem' filesystem
split 'src/Heritage/Hashing' hashing
split 'src/Heritage/Http' http
split 'src/Heritage/Image' image
split 'src/Heritage/JsonSchema' json-schema
split 'src/Heritage/Log' log
split 'src/Heritage/Macroable' macroable
split 'src/Heritage/Mail' mail
split 'src/Heritage/Notifications' notifications
split 'src/Heritage/Pagination' pagination
split 'src/Heritage/Pipeline' pipeline
split 'src/Heritage/Process' process
split 'src/Heritage/Queue' queue
split 'src/Heritage/Reflection' reflection
split 'src/Heritage/Redis' redis
split 'src/Heritage/Routing' routing
split 'src/Heritage/Session' session
split 'src/Heritage/Support' support
split 'src/Heritage/Testing' testing
split 'src/Heritage/Translation' translation
split 'src/Heritage/Validation' validation
split 'src/Heritage/View' view
