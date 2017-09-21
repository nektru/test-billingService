apt update

# install main packages
apt install -y rabbitmq-server postgresql \
               php-cli

# install development packages
apt install -y php-mbstring php-curl php-xml
               git zip

# enable admin panel for rabbitmq
rabbitmq-plugins enable rabbitmq_management
# enable guest access from all addresses
echo "[{rabbit, [{loopback_users, []}]}]." > /etc/rabbitmq/rabbitmq.config
systemctl restart rabbitmq-server

# install composer
/vagrant/utils/installComposer.sh

# switch to user
su vagrant
# go to project dir
cd /vagrant
# install composer packages
composer install
