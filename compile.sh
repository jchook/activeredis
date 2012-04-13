#!/bin/bash
file='arc.php'
echo '<?php namespace ActiveRedis; ' > $file
for i in `find lib -name "*.php"` 
do
	php -w $i | sed 's/^<?php//' | sed 's/?>$//' | sed 's/namespace ActiveRedis;//' >> $file
done
echo 'ding'

