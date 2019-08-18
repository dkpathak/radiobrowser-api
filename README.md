# radiobrowser-api

## Deprecation warning
THIS PROJECT IS DEPRECATED!

Please use https://github.com/segler-alex/radiobrowser-api-rust instead for future development. It is an all in one alternative to 
* radiobrowser-api (https://github.com/segler-alex/radiobrowser-api)
* stream-check (https://github.com/segler-alex/stream-check-rust)
* radiobrowser-cleanup-tasks (https://github.com/segler-alex/radiobrowser-cleanup-tasks-rust)

## About
This is the radio browser server part, that provides the api on http://www.radio-browser.info

Send me feature requests, bug reports or extend it yourself. I licenced it freely, you could also start your own server if you wish.

You can find the api documentation on http://www.radio-browser.info/webservice

## Setup
You can do a native setup or a docker setup

### native setup
Requirements:
* apache
* mod_php (modules: mysql, db, xml)
* mariadb or mysql

```bash
# install packages (ubuntu 18.04)
sudo apt install apache2 libapache2-mod-php php-db php-xml php-mysql
sudo apt install default-mysql-server

# enable apache modules
sudo a2enmod rewrite headers
```

Mysql/MariaDB needs to be in utf8mb4 mode. Ensure that the following 2 lines exist in your database config: (somewhere in /etc/mysql)
```
character-set-server  = utf8mb4
collation-server      = utf8mb4_general_ci
```

Apache config file example
```
<VirtualHost *:80>
	ServerName www.radio-browser.info

	ServerAdmin webmaster@programmierecke.net
	DocumentRoot /var/www/radio

	ErrorLog ${APACHE_LOG_DIR}/error.radio.log
	CustomLog ${APACHE_LOG_DIR}/access.radio.log combined

	<Directory /var/www/radio/>
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
```

```bash
# clone repository
git clone https://github.com/segler-alex/radiobrowser-api /var/www/radio

# create database and user
cat /var/www/radio/init.sql | mysql

# import database from www.radio-browser.info
wget http://www.radio-browser.info/backups/latest.sql.gz
cat latest.sql.gz | gzip -d | mysql -D radio

# test it
xdg-open http://localhost/webservice/xml/countries
# or just open the link with your favourite browser
```

### docker setup
```bash
# build docker image
git clone https://github.com/segler-alex/radiobrowser-api
docker build -t radioapi radiobrowser-api

# import database from www.radio-browser.info
wget http://www.radio-browser.info/backups/latest.sql.gz

# start database and api
docker network create radionetwork
docker run -d --network radionetwork \
	-v $(pwd)/latest.sql.gz:/docker-entrypoint-initdb.d/latest.sql.gz \
	-e MYSQL_RANDOM_ROOT_PASSWORD=yes \
	-e MYSQL_DATABASE=radio \
	-e MYSQL_USER=radiouser \
	-e MYSQL_PASSWORD=password \
	--name dbserver \
	--hostname dbserver \
	mariadb:10.1 \
	--character-set-server=utf8mb4 \
	--collation-server=utf8mb4_unicode_ci
docker run -d --network radionetwork -p 80:80 --name radioapi radioapi

# test it
xdg-open http://localhost/webservice/xml/countries
# or just open the link with your favourite browser
```

## Development
### Simple (with docker)
You don't have to setup apache, php and mysql yourself.
First you have to change a line in db.php
```php
// from
$db = new PDO('mysql:host=localhost;dbname=radio', 'radiouser', 'password');
// to
$db = new PDO('mysql:host=dbserver;dbname=radio', 'radiouser', 'password');
```
Then do some docker magic:
```bash
# build image
docker build . -t api
# import database from www.radio-browser.info
wget http://www.radio-browser.info/backups/latest.sql.gz
# start database and api
docker network create radionetwork
docker run -d --network radionetwork \
	-p 3306:3306 \
	--rm \
	-v $(pwd)/latest.sql.gz:/docker-entrypoint-initdb.d/latest.sql.gz \
	-e MYSQL_ROOT_PASSWORD=12345678 \
	-e MYSQL_DATABASE=radio \
	-e MYSQL_USER=radiouser \
	-e MYSQL_PASSWORD=password \
	--name dbserver \
	--hostname dbserver \
	mariadb:10.1 \
	--character-set-server=utf8mb4 \
	--collation-server=utf8mb4_unicode_ci
# wait some time until db import was successfull
# you can check on it by looking at the logs (press CTRL-C to get out)
docker logs -f dbserver

# run server and mount current working dir inside
docker run --rm --name api --network radionetwork -p 8080:80 -v $(pwd):/var/www/html/ api
```
Now you can just edit the files directly inplace and see the result.