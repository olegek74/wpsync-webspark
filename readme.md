# Wpsync Webspark

Плагин устанавливается как обычный любой плагин.

Он имеет настройки:

'Api Url' - ссылка на ресурс где находится json файл для синхронизации

'Last update date' - дата последнего обновления базы. по умолчанию равно 0. потом . после первого запуска изменится

'Password' - пароль для предотвращения случайного запуска посторонними

'Period' - период обновления базы товаров (через сколько часов)

'Author' - это значение для поля `post_author` в таблице wp_posts

Обновление товаров запускается по ссылке http://[you_domain]/export-json/passwod/[password]
где:
- [you_domain] - доменное имя сайтв
- [password] - значение из поля Password

Для запуска плагина необходима настройка CRON - задачи на сервере и наличие программы wget



