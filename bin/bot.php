<?php 

require dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

use \unreal4u\TelegramAPI\HttpClientRequestHandler;
use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\Telegram\Types\Message;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

$config = \json_decode(\file_get_contents("config.json"), true);

$loop = \React\EventLoop\Factory::create();
$handler = new HttpClientRequestHandler($loop);
$tgLog = new TgLog($config['token'], $handler);

$socket = new React\Socket\Server($config['port'], $loop);

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use (&$tgLog, &$config) {
  $ip = trim(parse_url($connection->getRemoteAddress(), PHP_URL_HOST), '[]');
  $addr = $config['sourcealiases'][$ip] ?? $ip;
  $addr = addcslashes($addr, "_*[]()~`>#+-=|{}.!\\");

  $connection->on('data', function($chunk) use (&$tgLog, &$config, $addr) {
    $sendMessage = new SendMessage();
    $sendMessage->chat_id = $config['user'];
    if (strpos($chunk, ">>!SILENT")) {
      $chunk = str_replace(">>!SILENT", "", $chunk);
      $sendMessage->disable_notification = true;
    }
    $sendMessage->text = $chunk . ($config['sourcefooter'] ? "\n\> _from *".$addr."*_" : "");
    $sendMessage->parse_mode = 'MarkdownV2';
    $tgLog->performApiRequest($sendMessage)
      ->then(
        function (Message $message) use ($tgLog) {
          //var_dump($message);
        }, 
        function (\Exception $exception) {
          echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage()."\n";
        }
      );
  });

  $connection->on('error', function(Exception $e) use (&$tgLog, &$config) {
    $sendMessage = new SendMessage();
    $sendMessage->chat_id = $config['user'];
    $sendMessage->parse_mode = 'MarkdownV2';
    $sendMessage->text = '*ERROR*: ' . $e->getMessage();
    $tgLog->performApiRequest($sendMessage)
      ->then(
        function (Message $message) use ($tgLog) {
          //var_dump($message);
        }, 
        function (\Exception $exception) {
          echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage()."\n";
        }
      );
  });
});

//echo "[ OK ] Listening"

$loop->run();