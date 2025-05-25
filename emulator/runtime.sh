#!/bin/bash
#
# Entrypoint for local aws lambda emulator
#

echo 'Lambda emulator is starting...'

chmod +x /opt/bootstrap
mkdir -p /var/task
#ls -al /opt/bin/php
#ls -al /opt

php /opt/server/server.php &
/opt/bin/aws-lambda-rie
#/opt/bootstrap


# Wait for any process to exit
#wait -n

# Exit with status of process that exited first
#exit $?
