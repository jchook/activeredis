#!/bin/bash

for file in ./src/lib/*
do
	php -l $file
done
