cd backend/lib/vendor/
composer install/update
[composer dump-autoload]
# create postdaemon user
cd ../../propel/
[path_to_propel]/propel sql:build --overwrite
[path_to_propel]/propel model:build
[path_to_propel]/propel sql:insert
[path_to_propel]/propel config:convert
php create-sample-db-content.php
