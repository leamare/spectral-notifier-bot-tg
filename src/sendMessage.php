<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\Telegram\Types\Message;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

function sendNewMessage(TgLog &$tl, string $body, $recepient, $silent = false, $html = false) {
  $sendMessage = new SendMessage();
  $sendMessage->chat_id = $recepient;
  $sendMessage->text = addcslashes($body, '-');
  $sendMessage->disable_notification = $silent;
  $sendMessage->parse_mode = $html ? 'HTML' : 'MarkdownV2';
  $tl->performApiRequest($sendMessage)
    ->then(
      function (Message $message) {
        //var_dump($message);
      }, 
      function (\Exception $exception) {
        echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage()."\n";
      }
    );
}

function setReminder(array $msg, &$config, TgLog $tl, $from) {
  global $loop;

  $br = $config['argsbr'] ?? '  ';
  $msg = implode($br, $msg);
  $remind = explode('@@', $msg);

  if (isset($remind[1])) {
    $timer = strtotime($remind[1])-time();
    if ($timer <= 0) $timer = 3600;
  } else $timer = 3600;

  sendNewMessage($tl, "Set reminder to ".date(DATE_RFC850, time()+$timer), $from, false, $html);
  $loop->addTimer($timer, function() use ($remind, $tl, $from) {
    sendNewMessage($tl, $remind[0], $from, false, false);
  });
}