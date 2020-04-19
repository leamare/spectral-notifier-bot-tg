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
use \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use \unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;

require dirname(__DIR__) . '/src/sendMessage.php';

$config = \json_decode(\file_get_contents("config.json"), true);
$firstRun = true;
$lastPoll = 0;

$loop = \React\EventLoop\Factory::create();
$handler = new HttpClientRequestHandler($loop);
$tgLog = new TgLog($config['token'], $handler);

$socket = new React\Socket\Server($config['port'], $loop);

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use (&$tgLog, &$config) {
  $ip = trim(parse_url($connection->getRemoteAddress(), PHP_URL_HOST), '[]');
  $addr = $config['sourcealiases'][$ip] ?? $ip;
  $addr = addcslashes($addr, "_*[]()~`>#+-=|{}.!\\");

  $connection->on('data', function($chunk) use (&$tgLog, &$config, $addr) {
    $silent = false;
    if (strpos($chunk, ">>!SILENT") !== false) {
      $chunk = str_replace(">>!SILENT", "", $chunk);
      $silent = true;
    }
    $chunk .= ($config['sourcefooter'] ? "\n\> _from *".$addr."*_" : "");
    foreach ($config['users'] as $u => $st) {
      if ($silent && !$st) continue;
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

if (!empty($config['commands'])) {
  $timer = $loop->addPeriodicTimer($config['polling_timer'] ?? 5, function () use (&$tgLog, &$config, &$firstRun, &$lastPoll) {
    $updates = new GetUpdates();
    $updates->allowed_updates[] = 'message';
    $tgLog->performApiRequest($updates)
      ->then(
        function (UpdatesArray $upd) use ($tgLog, &$config, &$firstRun, &$lastPoll) {
          foreach ($upd->data as $u) {
            if ($firstRun) {
              $lastPoll = $u->update_id;
              continue;
            } else {
              if ($lastPoll >= $u->update_id)
                continue;
            }
            $msg = trim(strtolower($u->message->text));
            $from = $u->message->from->id;
            if (!isset($config['users'][$from]))
              continue;
            
            $lastPoll = $u->update_id;
            if (isset($config['commands'][ $msg ])) {
              $cmd = explode('::',$config['commands'][ $msg ]);
              if ($cmd[0] === 'shell') {
                $res = shell_exec($cmd[1]);
              } elseif ($cmd[0] === 'uri') {
                $res = file_get_contents($cmd[1]);
              }
              $res = addcslashes($aresddr, "_*[]()~`>#+-=|{}.!\\");
              sendNewMessage($tgLog, $res, $from);
            }
          }
          if ($firstRun) {
            $firstRun = false;
          }
        }, 
        function (\Exception $exception) {
          echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage()."\n";
        }
      );
  });
}

$loop->run();