## Server setup 

> Below instructions are working only on **AlmaLinux 9**
> 
>For other OS's need to be adapted accordingly

## Starting point
A server with AlmaLinux 9 freshly installed, with root access and updated 

### Update OS
dnf update

### Create a new user with sudo permissions

`useradd dotkernel`

`passwd dotkernel`

`usermod -aG wheel dotkernel`

### reboot

### SSH to the server as new user

###  Install various utilities

`sudo dnf install -y dnf-utils`

`sudo dnf install zip unzip socat wget`

### PHP

`sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm`

`sudo dnf module enable php:remi-8.4`

`sudo dnf install -y php php-cli php-common php-intl`

### Start PHP-FPM

`sudo systemctl start php-fpm`

`sudo systemctl enable php-fpm`

### Install and verify swoole

`sudo dnf install php-pecl-swoole6`

`php -i | grep swoole`

### Valkey

`sudo dnf install valkey`

`sudo systemctl enable valkey`

`sudo systemctl start valkey`

`sudo valkey-cli ping`

### Valkey PHP module

`sudo dnf install php-pecl-redis`

`php -i | grep redis`

### GIT

`sudo dnf install git`

### Composer
`wget https://getcomposer.org/installer -O composer-installer.php`

`sudo chmod 777 /usr/local/bin`

`php composer-installer.php --filename=composer --install-dir=/usr/local/bin`

### Firewall setup

In order to add a minimum level of security, a firewall needs to be installed and allows  
connections from outside only to certain ports, from certain IP's.

> Firewall setup is not mandatory

`sudo dnf install firewalld`

`sudo systemctl enable firewalld`

> Before starting the firewall, be sure you will not be locked outside 

`sudo firewall-offline-cmd --zone=public --add-port=22/tcp --permanent`

`sudo systemctl start firewalld`

> By default, Swoole runs on port 8556. You can change that in the configuration file.

`sudo firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="YOUR_IP_ADDRESS" port port="8556" protocol="tcp" accept'`

`sudo firewall-cmd --reload`

## NOW THE SERVER IS READY
