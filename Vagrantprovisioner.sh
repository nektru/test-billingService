apt update

# install packages
apt install -y php-cli rabbitmq-server postgresql

# enable admin panel for rabbitmq
rabbitmq-plugins enable rabbitmq_management
# enable guest access from all addresses
echo "[{rabbit, [{loopback_users, []}]}]." > /etc/rabbitmq/rabbitmq.config
systemctl restart rabbitmq-server
