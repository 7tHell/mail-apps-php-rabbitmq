## Mail apps using rabbitMQ

## Notes
You can find on google how to setup rabbitMQ, PHP 7.0+, MYSQL

## Setup
Setup :
- Setup rabbitMQ
- Setup PHP 7.0+
- Setup MYSQL
- Change dir into your apps directory
- Copy .env.example into .env
- Change config into your config
- MYSQL config
- RabbitMQ config
- Mailgun config
- Set your queue name
```sh
php composer.phar update
 ```
```sh
php artisan migrate
 ```
 After migrating database you will see template table basically its for reference only
 so that we can remember content needed for each template
## Features
v1.0.0 :
- Swagger included you can access it using [SERVER-HOST]/swagger
- Message will be queue to rabbit MQ
- Create your own template for sending email
- Integrate with mailgun only currently you can register at :www.mailgun.com
- Unit test not included yet
- Setup your own security access at RabbitMqMiddleware.php

Next release v2.0.0 :
- Mail attachment
- Unit test
- Integration test

## Create task
Swagger docs
```
http://[SERVER-HOST]/swagger/#!/Mail/rabbitMq_mail_create
```

```sh
curl -X POST --header 'Content-Type: application/json' --header 'Accept: application/json' 
'http://[SERVER-HOST]/rabbitMq/mail/create?from=no-reply%40bukamenu.com&to=prasetya%40bukamenu
.com&subject=Testing&template_view=Example&content=%7B%22name%22%3A%22World&#33;%22%7D'
```

## Request parameter
- from : mail sender please make sure use verified domain
- to : recipient use comas separator for multiple address ex : a@gmail.com,b.gmail.com
- cc : cc use comas separator for multiple address ex : a@gmail.com,b.gmail.com
- bcc : bcc use comas separator for multiple address ex : a@gmail.com,b.gmail.com
- subject : email subject
- template_view : template that you want to use you can try using "Example" for trial,
you can create your own template at /resources/views and put your template there
- content : content that you will parse at your template_view you can try using 
"{"name":"Hello"}"

## Consume task
Swagger docs
```
http://[SERVER-HOST]/swagger/#!/Mail/rabbitMq_mail_consume
```
- use for consuming queue from your rabbit MQ
- you just need to call this once you can monitor your rabbitMQ 
using rabbitMQ admin and check if theres error
- when there is exception consumer will stop consuming queue
