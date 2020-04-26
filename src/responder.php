<?php 

use \unreal4u\TelegramAPI\HttpClientRequestHandler;
use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\Telegram\Types\Message;
use \unreal4u\TelegramAPI\Telegram\Types\Update;
use \unreal4u\TelegramAPI\Telegram\Types\Custom\UpdatesArray;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use \unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Request;
use React\Http\Server;

include_once __DIR__ . '/parseUpdate.php';

if (!empty($config['commands'])) {
  if ($config['polling']) {
    $firstRun = true;
    $lastPoll = 0;

    $timer = $loop->addPeriodicTimer($config['polling_timer'] ?? 5, function () use (&$tgLog, &$config, &$firstRun, &$lastPoll) {
      $updates = new GetUpdates();
      $updates->allowed_updates[] = 'message';
      $updates->limit = $config['polling_limit'] ?? 100;
      $updates->offset = -$updates->limit;
      $tgLog->performApiRequest($updates)
        ->then(
          function (UpdatesArray $upd) use ($tgLog, &$config, &$firstRun, &$lastPoll) {
            $br = $config['argsbr'] ?? '  ';
            foreach ($upd->data as $u) {
              if ($firstRun) {
                $lastPoll = $u->update_id;
                continue;
              } else {
                if ($lastPoll >= $u->update_id)
                  continue;
              }

              parseUpdate($u, $tgLog, $config);
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
  } else {
    $server = new React\Http\Server(function (ServerRequestInterface $request) use (&$tgLog, &$config) {
      $queryParams = $request->getQueryParams();
      if ($queryParams['token'] != $config['token']) 
        return new Response( 403, [ 'Content-Type' => 'application/json' ], json_encode([ 'message' => 'bruh' ]) );

      $data = (string)$request->getBody();
      $updateData = json_decode($data, true);

      $update = new Update($updateData);
      parseUpdate($update, $tgLog, $config);
      
      return new Response( 200, [ 'Content-Type' => 'application/json' ], json_encode([ 'message' => 'ok' ]) );
    });
    $socket = new React\Socket\Server($config['webhook_port'], $loop);
    $server->listen($socket);

    $setWebhook = new SetWebhook();
    $setWebhook->url = str_replace('%token%', $config['token'], $config['webhook_url']);

    $tgLog->performApiRequest($setWebhook);
  }
}