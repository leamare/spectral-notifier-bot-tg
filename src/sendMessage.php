<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\Telegram\Types\Message;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

function sendNewMessage(TgLog &$tl, string $body, $recepient, $silent = false, $html = false) {
  $sendMessage = new SendMessage();
  $sendMessage->chat_id = $recepient;
  $sendMessage->text = $body;
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