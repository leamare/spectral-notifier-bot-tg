# Spectral Notifier Bot for Telegram

Simple telegram notifier bot I made for myself to keep track of all the things happening on my servers

It receives socket messages and sends it as a telegram message

It supports sending notifications to groups and channels, as well as users (but currently only reads from users)

It's built using unreal4u/telegram-api lib

## Usage

Usage:
1. Put config data to config.json (based on config.example.json). You need to generate bot token (from botfather) and get your account ID
2. run `composer install`
3. run `php bin/bot.php` - Or you can do it my way: `nohup php bin/bot.php NOTIFIER >/dev/null 2>errlog.out & disown`
4. run `php bin/alert.php` and pass a message you want to send as cli args
  * You can use curl-like parameters, as well as special parameters "body" and "title"
  * Or you can just write everything as a single line

Default commands:
* `/h` - list of commands
* `/re -- Message @@ at time` - reminder (-- is the default args breaker)
* `/time` - current server time

## Additional setup and config explanation

Below is the list of supported config parameters (and required ones) + instructions to set up a webhook

* `token` - (required) your bot token (see bot api page on telegram docs)
* `port` - (required) it is actually a full address that you're going to use to accept messages + port 
  * you can specify only port as an int, but then your bot will be available exclusively for local usage
  * use your public IP address here + port
  * don't forget to open this port for connections in your firewall
* `server` - (required) same as port, but for `alert.php` - address to send messages
* `sourcefooter` - (required) if true adds sender info to the end of the message (like `> from localhost`)
  * you can specify aliases for IP addresses in another parameter
* `polling` - (required) 
  * if true it will be using `polling_` group of parameters and `getUpdates` endpoint to get updates
  * if false it will be using webhook instead
* `polling_timer` - polling period in seconds
* `polling_limit` - number of messages to get from getUpdates
* `webhook_url` - public URL of your webhook
  * Weebhook URL has a `?token=%token%` part. Don't forget about it since notifier's webhook server will oterwise ignore your updates
* `webhook_port` - local port of your webhook
  * The way I use it (and recommend you too) is by having Nginx handle all the connections and https stuff
  * It's the best way to set it up, especially if you have multiple web services on the same server
  * You can pot this to your nginx config for a domain `domain.name` and your webhook will be available on `http(s)://domain.name/notifier/`:
  ```conf
    location /notifier/ {
        proxy_pass http://127.0.0.1:17733/;
        proxy_redirect  http://localhost:17733/ /;
        proxy_read_timeout 60s;

        proxy_set_header          Host            $host;
        proxy_set_header          X-Real-IP       $remote_addr;
        proxy_set_header          X-Forwarded-For $proxy_add_x_forwarded_for;
    }
  ```
* `users` - (required) an object of users and their message subscriptions. You can see an example in the config.example.json
  * a user will get only messages with tags he is subscribed to
  * there are special tags: `_else` for any message without a tag and `_all` for all the messages (means he will get anything)
  * Negative user IDs refer to channels and chats
  * You can use @usernames as well
    * I made a private channel and redirect `_all` messages here, but get everything important (like new ssh connection notification or server errors notifications) directly to my PMs
* `sourcealiases` - (required) and object of ip = alias, replaces "ip" to "alias" text in messsage source footers
* `groups` - (required) object of tags to assign to messages:
  * example: `"tag": { "kv": "Word", "silent": true }`
  * `kv` is key phrase: if it's found in a message then the message gets this tag
  * `silent` says if the message should be sent silently (without notification) or not. If **any** of message's tags is non-silent, then the message will be sent normally
* `argsbr` - a set of symbols to use to break incoming message to get command parameters (default is `  `)
* `commands` - object of commands to execute (if empty bot won't be looking for updates)
  * ` "command": "command_type::command" `
  * Optionally you may pass the third argument - number of command's arguments (`command_type::command::args_num`). See examples in config example
  * To use arguments, you need to use pairs or '%'+number (like `%1`) in the command
  * Supported command types: `uri` (gets URL contents) and `shell` (executes a shell command)
  * URL should be relatively simple, otherwise telegram will reject your message (I use it for a coupmr of simple pages and to send status requests)

## Autoloading, PSR, tests and so on

There's no need to.