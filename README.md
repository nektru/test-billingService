# Сервис балланса пользователей

Построено на базе PHP7, PostgreSQL, RabbitMQ и Supervisor

## Установка и настройка

### Девелоперское окружение

Для управления зависимостями проекта используется [vagrant](https://www.vagrantup.com/downloads.html), настроенный для работы с [virtualbox](https://www.virtualbox.org/). Так же потребуется плагин [vagrant-vbguest](https://github.com/dotless-de/vagrant-vbguest) для автоматической установки дополнений гостевой системы.

Запуск виртуалки с сервисом осуществляется таким образом:
```bash
git clone git@github.com:nektru/test-billingService.git
cd test-billingService
vagrant up
```

### Прочие окружения

Для работы на окружениях, отличающихся от разработческих, где уже все настроено, может потребоваться следующая информация:
- Конфиги управляюся через файл [config.ini](config.ini)
- Зависимости и пример настройки окружения можно подсмотреть в [VagrantProvisioner.sh](VagrantProvisioner.sh)
- Схему базы данных можно найти в [schema.sql](database/schema.sql)

## Доступы к системам окружения

После запуска виртуалки следующие сервисы должны юыть доступны

### Vagrant

Доступ внутрь виртуалки может быть получен командой `vagrant ssh` из корня проекта. Проект монтируется в директорию /vagrant гостевой машины.

### RabbitMQ

Админка доступна по адресу http://192.168.33.10:15672/
Брокер доступен извне по адресу 192.168.33.10:5672

Логин - guest
Пароль - guest

### PostgreSQL

Открыт внеший доступ для основной базы
```
pgsql:host=192.168.33.10;dbname=billingservice;user=postgres;password=postgres
```
И для тестовой базы
```
pgsql:host=192.168.33.10;dbname=billingservice;user=postgres;password=postgres
```

### Сервис баланса

Сервис баланка имеет cli-интерфейс для доступа с хост-машины.
Для работы требуются такие расширения как `php-intl`, `php-bcmath`
Помощь по использованию и аргументам можно получить с помощью команды `./billingService.php help query`




