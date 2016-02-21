FROM kdelfour/supervisor-docker
MAINTAINER Gezim Hoxha <hgezim@gmail.com>

# needs to be up here
RUN usermod -u 1000 www-data

RUN echo 'debconf debconf/frontend select Noninteractive' | debconf-set-selections
RUN apt-get update && apt-get install -y nginx php5-dev php5-mysql php5-fpm php5-xdebug ccze vim links vsftpd ftp ssh curl

# Set up fpm error logging
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php5/fpm/php.ini
RUN sed -i 's/;php_admin_flag\[log_errors\] = on/;php_admin_flag\[log_errors\] = on/' /etc/php5/fpm/pool.d/www.conf
RUN sed -i 's!;php_admin_value\[error_log\] = /var/log/fpm-php\.www\.log!php_admin_value\[error_log\] = /var/log/fpm-php\.www\.log!' /etc/php5/fpm/pool.d/www.conf

# Create file for fpm logging
RUN touch /var/log/fpm-php.www.log
RUN chown www-data.www-data /var/log/fpm-php.www.log

# Fix xdebug settings
RUN { \
  echo 'zend_extension="/usr/lib/php5/20121212/xdebug.so"'; \
  echo 'xdebug.remote_enable = on'; \
  echo 'xdebug.remote_connect_back = on'; \
  echo 'xdebug.idekey = "vagrant"'; \
  } >> /etc/php5/mods-available/xdebug.ini

WORKDIR /root/
RUN curl https://wordpress.org/latest.tar.gz -O

RUN tar xzf latest.tar.gz
RUN rm -rf latest.tar.gz
RUN mkdir /usr/share/nginx/html/wordpress && cp -r wordpress/* /usr/share/nginx/html/wordpress/
RUN cp /usr/share/nginx/html/wordpress/wp-config-sample.php /usr/share/nginx/html/wordpress/wp-config.php

EXPOSE 8080

RUN sudo chown -R www-data:www-data /usr/share/nginx/html/wordpress

ADD ./nginx_wordpress /etc/nginx/sites-enabled/

COPY ./supervisor_nginx.conf ./supervisor_fpm.conf ./supervisor_ftp.conf ./supervisor_sshd.conf /etc/supervisor/conf.d/


# create wordpress db if it doesn't exist - actually, let's let this be created by mysql image
# update config file 
RUN sed -i "s/define('DB_NAME', 'database_name_here');/define('DB_NAME', 'wordpress');/"	/usr/share/nginx/html/wordpress/wp-config.php
RUN sed -i "s/define('DB_USER', 'username_here');/define('DB_USER', 'root');/"	/usr/share/nginx/html/wordpress/wp-config.php
RUN sed -i "s/define('DB_PASSWORD', 'password_here');/define('DB_PASSWORD', 'root');/"	/usr/share/nginx/html/wordpress/wp-config.php
RUN sed -i "s/define('DB_HOST', 'localhost');/define('DB_HOST', 'db');/"	/usr/share/nginx/html/wordpress/wp-config.php
RUN sed -i "s/define('WP_DEBUG', false);/define('WP_DEBUG', true);/"	/usr/share/nginx/html/wordpress/wp-config.php

# setup ftp
RUN sed -i "s/anonymous_enable=NO/anonymous_enable=YES/" /etc/vsftpd.conf
RUN sed -i "s!secure_chroot_dir=/var/run/vsftpd/empty!#secure_chroot_dir=/var/run/vsftpd/empty!" /etc/vsftpd.conf
RUN sed -i "s/local_enable=YES/local_enable=NO/" /etc/vsftpd.conf
RUN { echo "anon_root=/usr/share/nginx/html/wordpress/"; } >> /etc/vsftpd.conf
RUN mkdir -p /var/run/vsftpd/empty # needed by chroot_list_file setting

# setup ssh
RUN mkdir /var/run/sshd
RUN sed -i "s/PermitRootLogin without-password/PermitRootLogin yes/" /etc/ssh/sshd_config  

# set root pass
RUN echo "root:Dockerzrdn!" | chpasswd

# that's it!

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]

