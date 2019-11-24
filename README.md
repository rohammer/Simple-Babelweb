# Simple-Babelweb
A very simple Babel Web for babeld v1.8
* Just clone it to your favorite httpd directory.

## index.php
The php version for gateways
### Requirements
* php
* netcat-openbsd (ipv6) 

## babel.html
The haserl version for slim devices without php
### Requirements
* haserl
* netcat with ipv6 support

## Apache 2 settings
To avoid OOM Killer it's usefull to reduce the MaxConnectionsPerChild on apache2. On Debian open /etc/apache2/mods-enabled/mpm_prefork.conf and reduce MaxConnectionsPerChild.
Do not use more then 10 MaxConnectionsPerChild per Gigabyte RAM on your System.
