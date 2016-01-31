#!/usr/bin/env bash
sudo apt-get -qq update

echo "*************Setting up mysql settings.*************"
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'

echo "*************Installing packages*************"
sudo apt-get -qq install -y nginx mysql-server php5-dev php5-mysql php5-fpm php5-xdebug ccze


echo "*************Setting up fpm error logging.*************"
sudo sh -c "sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php5/fpm/php.ini"
sudo sh -c "sed -i 's/;php_admin_flag\[log_errors\] = on/;php_admin_flag\[log_errors\] = on/' /etc/php5/fpm/pool.d/www.conf"
sudo sh -c "sed -i 's!;php_admin_value\[error_log\] = /var/log/fpm-php\.www\.log!php_admin_value\[error_log\] = /var/log/fpm-php\.www\.log!' /etc/php5/fpm/pool.d/www.conf"

echo "*************Creating file for fpm logging.*************"
sudo touch /var/log/fpm-php.www.log
sudo chown www-data.www-data /var/log/fpm-php.www.log

echo "*************Fixing xdebug settings.*************"
sudo cat > xdebug_changes << EOF
xdebug.remote_enable = on
xdebug.remote_connect_back = on
xdebug.idekey = "vagrant"
EOF

sudo sh -c 'cat xdebug_changes >> /etc/php5/mods-available/xdebug.ini'

echo "*************Running mysql install script*************"
sudo mysql_install_db


echo "*************Downloading WP*************"
wget -q http://wordpress.org/latest.tar.gz

echo "*************Creating wordpress database.*************"
mysql -u root -proot -e "CREATE DATABASE wordpress; CREATE USER wordpressuser@localhost IDENTIFIED BY 'password';"

mysql -u root -proot -e "GRANT ALL PRIVILEGES ON wordpress.* TO wordpressuser@localhost IDENTIFIED BY 'password'; FLUSH PRIVILEGES;"

tar xzf latest.tar.gz
cp wordpress/wp-config-sample.php wordpress/wp-config.php

sed -i "s/define('DB_NAME', 'database_name_here');/define('DB_NAME', 'wordpress');/" wordpress/wp-config.php

sed -i "s/define('DB_USER', 'username_here');/define('DB_USER', 'wordpressuser');/" wordpress/wp-config.php

sed -i "s/define('DB_PASSWORD', 'password_here');/define('DB_PASSWORD', 'password');/" wordpress/wp-config.php

export WP_INSTALLED_LOCATION='/wordpress_env'
sudo rm -rf $WP_INSTALLED_LOCATION/*
sudo cp -r wordpress/* $WP_INSTALLED_LOCATION
sudo ln -s /vagrant/src  $WP_INSTALLED_LOCATION/wp-content/plugins/zip-recipes

cd $WP_INSTALLED_LOCATION
sudo chown -R www-data:www-data *

sudo cp /etc/nginx/sites-available/default /etc/nginx/sites-available/wordpress

cat > nginx_wordpress_config << EOF
server {
        listen   8080;


        root $WP_INSTALLED_LOCATION;
        index index.php index.html index.htm;

        location / {
                try_files \$uri \$uri/ /index.php?q=\$uri&\$args;
        }

        error_page 404 /404.html;

        error_page 500 502 503 504 /50x.html;
        location = /50x.html {
              root /usr/share/nginx/www;
        }

        location ~ \.php$ {
                try_files \$uri =404;
                # With php5-fpm:
                fastcgi_pass unix:/var/run/php5-fpm.sock;
                fastcgi_index index.php;
                include fastcgi_params;
                 }


}
EOF

sudo sh -c 'cat nginx_wordpress_config > /etc/nginx/sites-available/wordpress'

rm nginx_wordpress_config

sudo ln -s /etc/nginx/sites-available/wordpress /etc/nginx/sites-enabled/wordpress

sudo rm /etc/nginx/sites-enabled/default

sudo service nginx restart
sudo service php5-fpm restart

echo "*************Running mysql upgrade script (5.6)*************"
touch ~/.mysql_not_upgraded
sudo chmod +x /vagrant/mysql-5.6_install.sh

# This is not working. Idea is for mysql-5.6_install.sh to run once user logs in.
# For now it has to be run manually.

echo "*************** Now run /vagrant/mysql-5.6_install.sh after logging into vagrant machine. ***************"

#sudo cat > mysql-5.6_startup_script.sh <<EOF
#if [ -f ~/.mysql_not_upgraded ]
#then
#        /vagrant/mysql-5.6_install.sh
#        if [ $? == 0 ] # if mysql upgrade ran successfully
#        then
#                rm -rf ~/.mysql_not_upgraded
#        fi
#fi
#EOF
#
#sudo sh -c 'cat mysql-5.6_startup_script.sh >> /etc/skel/.bashrc'
