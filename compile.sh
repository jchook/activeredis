#!/bin/bash
file='arc.php'
echo '<?php namespace ActiveRedis; ?>' > $file
for i in `find lib -name "*.php"` 
do
	php -w $i | sed 's/namespace ActiveRedis;//g' >> $file
done
echo 'ding'

