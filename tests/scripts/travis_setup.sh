#!/bin/bash
mysql -u root -e 'create database drupal;'
mysql -u root -e "GRANT ALL PRIVILEGES ON drupal.* To 'drupal'@'localhost' IDENTIFIED BY 'drupal';"

# Java (Oracle)
sudo apt-get install -y software-properties-common
sudo apt-get install -y python-software-properties
sudo add-apt-repository -y ppa:webupd8team/java
sudo apt-get update
echo debconf shared/accepted-oracle-license-v1-1 select true | sudo debconf-set-selections
echo debconf shared/accepted-oracle-license-v1-1 seen true | sudo debconf-set-selections
sudo apt-get install -y oracle-java8-installer
sudo update-java-alternatives -s java-8-oracle
sudo apt-get install -y oracle-java8-set-default
export JAVA_HOME=/usr/lib/jvm/java-8-oracle

# phpcpd
#sudo apt-get install -y phpcpd

cd $HOME
pear channel-discover pear.drush.org
pear upgrade --force Console_GetoptPlus
pear upgrade --force pear
pear channel-discover pear.drush.org

# Drush
cd /tmp
php -r "readfile('https://s3.amazonaws.com/files.drush.org/drush.phar');" > drush
php drush core-status
chmod +x drush
sudo mv drush /opt
sudo ln -s /opt/drush /usr/bin/drush

phpenv rehash

cd $HOME
drush dl drupal-8.2.2 --drupal-project-rename=drupal
cd $HOME/drupal
drush si minimal --db-url=mysql://drupal:drupal@localhost/drupal --yes
drush runserver --php-cgi=$HOME/.phpenv/shims/php-cgi localhost:8081 &>/tmp/drush_webserver.log &

ln -s $ISLANDORA_DIR modules/islandora
drush en -y rdf
drush en -y responsive_image
drush en -y syslog
drush en -y serialization
drush en -y basic_auth
drush en -y rest

drush dl rdfui --dev
drush en -y rdfui
drush en -y rdf_builder

drush dl restui
drush en -y restui

drush dl inline_entity_form
drush en -y inline_entity_form

drush dl media_entity
drush en -y media_entity

drush dl media_entity_image
drush en -y media_entity_image

drush dl search_api
drush -y pm-uninstall search
drush en -y search_api

cd $HOME/drupal/modules
git clone https://github.com/DiegoPino/claw-jsonld.git
drush en -y jsonld

drush -y dl bootstrap
drush -y en bootstrap
drush -y config-set system.theme default bootstrap

drush cr
# The shebang in this file is a bogeyman that is haunting the web test cases.
rm /home/travis/.phpenv/rbenv.d/exec/hhvm-switcher.bash
sleep 20

# Install drupal-console
cd $HOME
composer global require drupal/coder
composer global require 'sebastian/phpcpd=*'
export PATH="\$PATH:$HOME/.composer/vendor/bin"

$HOME/.composer/vendor/bin/phpcs --config-set installed_paths ~/.composer/vendor/drupal/coder/coder_sniffer
