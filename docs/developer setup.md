- `git clone --recursive git@github.com:hgezim/zip-recipes.git`

### Setting up Docker ###
Source: `https://docs.docker.com/engine/installation/linux/ubuntulinux/`

Instructions for `Xenial 16.04`

- `sudo apt-get update`
- `sudo apt-get install apt-transport-https ca-certificates`
- `sudo apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D`
- Open `/etc/apt/sources.list.d/docker.list`
- Remove all contents
- Add the following entry:
	- `deb https://apt.dockerproject.org/repo ubuntu-xenial main`
- Save file and close it
- `sudo apt-get update`
- `sudo apt-get purge lxc-docker`
- `apt-cache policy docker-engine`
- `sudo apt-get update`
- `sudo apt-get install linux-image-extra-$(uname -r) linux-image-extra-virtual`
- `sudo apt-get update`
- `sudo apt-get install docker-engine`
- `sudo service docker start`
-       sudo usermod -aG docker `whoami`

### Install Docker Compose ###
Source: `https://docs.docker.com/compose/install/`

    curl -L https://github.com/docker/compose/releases/download/1.8.0/docker-compose-`uname -s`-`uname -m\` > /usr/local/bin/docker-compose
	chmod +x /usr/local/bin/docker-compose

### Install PHP 5.6 ###

- `sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php`
- `sudo apt-get update`
- `sudo apt-get install php5.6 php5.6-xml`

### Run PHP Composer ###

- cd `zip-recipes/src`
- `php ../composer.phar install` # has to be run from `src` dirc


### Run Docker-Compser ###

- `docker-compose up -d` # inside `zip-recipes`
- Navigate to `localhost:8080` and install WP
- Activae Zip Recipes plugin from Plugins