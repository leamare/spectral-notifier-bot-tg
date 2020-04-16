Simple telegram notifier I made for myself

It receives socket messages and sends it as a telegram message

It's meant to be used by only one user and it's going to work only for one user.

Usage:
1. Put config data to config.json (based on config.example.json). You need to generate bot token (from botfather) and get your account ID
2. run `composer install`
3. run `php bin/bot.php`
4. run `php bin/alert.php` and pass a message you want to send as cli args