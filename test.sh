#!/bin/bash

for file in ./lib/*
do
	php -l $file
done
