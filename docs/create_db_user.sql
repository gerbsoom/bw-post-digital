CREATE USER 'postdaemon'@'%' IDENTIFIED BY '***';GRANT ALL PRIVILEGES ON *.* TO 'postdaemon'@'%' IDENTIFIED BY '***' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;