#!/usr/bin/env sh
rm -rf ./grpc/*
protoc --proto_path=./protos --php_out=./grpc ./protos/*.proto
