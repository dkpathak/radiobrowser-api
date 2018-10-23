# radiobrowser-api
This is the radio browser server part, that provides the api on http://www.radio-browser.info

Send me feature requests, bug reports or extend it yourself. I licenced it freely, you could also start your own server if you wish.

You can find the api documentation on http://www.radio-browser.info/webservice

## Setup
Requirements:
* apache
* mod_php (modules: mysql, db, xml)
* mariadb or mysql

```bash
# install packages (ubuntu 18.04)
sudo apt install apache2 libapache2-mod-php php-db php-xml php-mysql
sudo apt install default-mysql-server
```

Apache config file example
```
<VirtualHost *:80>
	ServerName www.radio-browser.info

	ServerAdmin webmaster@programmierecke.net
	DocumentRoot /var/www/html

	ErrorLog ${APACHE_LOG_DIR}/error.radio.log
	CustomLog ${APACHE_LOG_DIR}/access.radio.log combined

	<Directory /var/www/html/>
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
```

```bash
# create database and user
cat init.sql | mysql

# import database from www.radio-browser.info
wget http://www.radio-browser.info/backups/latest.sql.gz
cat latest.sql.gz | gzip -d | mysql -D radio

# test it
xdg-open http://localhost/webservice/xml/countries
# or just open the link with your favourite browser
```