<?php 

require dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

use \unreal4u\TelegramAPI\HttpClientRequestHandler;
use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\Telegram\Types\Message;
use \unreal4u\TelegramAPI\Telegram\Types\Update;
use \unreal4u\TelegramAPI\Telegram\Types\Custom\UpdatesArray;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use \unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;

require dirname(__DIR__) . '/src/sendMessage.php';

$config = \json_decode(\file_get_contents("config.json"), true);

$loop = \React\EventLoop\Factory::create();
$handler = new HttpClientRequestHandler($loop);
$tgLog = new TgLog($config['token'], $handler);

$socket = new React\Socket\Server($config['port'], $loop);

foreach ($config['users'] as $u => $st) {
  sendNewMessage($tgLog, "Up and running", $u, true);
}

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use (&$tgLog, &$config) {
  $ip = trim(parse_url($connection->getRemoteAddress(), PHP_URL_HOST), '[]');
  $addr = $config['sourcealiases'][$ip] ?? $ip;
  $addr = addcslashes($addr, "_*[]()~`>#+-=|{}.!\\");

  $connection->on('data', function($chunk) use (&$tgLog, &$config, $addr) {
    $silent = true;
    $groups = [];

    foreach($config['groups'] as $gr => $des) {
      if (stripos($chunk, $des['kv']) !== false) {
        $groups[] = $gr;
        if (!$des['silent'] && $silent) $silent = false;
      }
    }
    if (empty($groups)) $groups[] = '_else';
    $groups[] = '_all';

    if ($config['sourcefooter'])
      $chunk .= "\n\> _from *".$addr."*_";
    foreach ($config['users'] as $u => $st) {
      $send = false;
      foreach ($st as $gr) {
        if (in_array($gr, $groups)) {
          $send = true;
          break;
        }
      }
      if ($send)
        sendNewMessage($tgLog, $chunk, $u, $silent);
    }
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

include_once dirname(__DIR__) . '/src/responder.php';

$loop->run();