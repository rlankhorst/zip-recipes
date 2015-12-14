#!/usr/bin/env bash

wget http://dev.mysql.com/get/mysql-apt-config_0.5.3-1_all.deb -O mysql-apt-config.deb
sudo dpkg -i mysql-apt-config.deb
sudo apt-get update -y
sudo apt-get install mysql-server

sudo service mysql restart