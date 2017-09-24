apt update

# install main packages
apt install -y rabbitmq-server postgresql supervisor \
               php7.0-cli php7.0-bcmath php7.0-pgsql php7.0-intl \

# install development packages
apt install -y php7.0-mbstring php7.0-curl php7.0-xml \
               git zip

# enable admin panel for rabbitmq
rabbitmq-plugins enable rabbitmq_management
# enable guest access from all addresses
echo "[{rabbit, [{loopback_users, []}]}]." > /etc/rabbitmq/rabbitmq.config
systemctl restart rabbitmq-server

# enable remote access to postgresql
echo "listen_addresses = '*'" >> /etc/postgresql/9.6/main/postgresql.conf
echo "host	 all             all             192.168.33.0/24         md5" >> /etc/postgresql/9.6/main/pg_hba.conf
su postgres -c "echo \"ALTER USER postgres WITH PASSWORD 'postgres';\" | psql"
systemctl restart postgresql
su postgres -c "psql s billingservice -h localhost -U postgres --password < /vagrant/database/schema.sql"

# install composer
/vagrant/utils/installComposer.sh

# install composer packages from user
su vagrant -c "cd /vagrant; composer install"

# link vagrant dir
#su vagrant -c "cd ~/; ln -s /vagrant"
