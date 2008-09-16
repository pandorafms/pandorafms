#!/bin/bash

HOST=$2
USER=$4
COMMAND=$5

ssh $USER@$HOST $COMMAND 
