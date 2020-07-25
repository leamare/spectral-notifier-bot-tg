<?php 

function parseUpdate(&$u, &$tgLog, &$config) {
  $br = $config['argsbr'] ?? '  ';
  $msg = trim($u->message->text);

  $msg = explode($br, $msg);

  $from = $u->message->from->id;
  if (!isset($config['users'][$from]))
    return;

  $c = strtolower($msg[0]);
  
  if ($c == "/h") {
    $res = "Commands:\n```".implode(', ', array_keys($config['commands']))."\n```";
    sendNewMessage($tgLog, $res, $from, false, false);
  } else if (isset($config['commands'][ $c ])) {
    $cmd = explode('::', $config['commands'][ $c ]);
    if ($cmd[0] === 'shell') {
      if (isset($cmd[2])) {
        for ($i = (int)$cmd[2]; $i > 0; $i--) {
          if (isset($msg[$i])) {
            $cmd[1] = str_replace('%'.$i, addcslashes($msg[$i], '&|'), $cmd[1]);
          }
        }
      }
      $res = shell_exec($cmd[1]);
      $res = "```\n".addcslashes($res, "_*[]()~`>#+-=|{}.!\\")."\n```";
      $html = false;
    } elseif ($cmd[0] === 'uri') {
      if (isset($cmd[2])) {
        for ($i = (int)$cmd[2]; $i > 0; $i--) {
          if (isset($msg[$i])) {
            $cmd[1] = str_replace('%'.$i, $msg[$i], $cmd[1]);
          }
        }
      }
      $html = true;
      $res = file_get_contents($cmd[1]);
      $res = str_replace('<br />', "\n", $res);
    }
    sendNewMessage($tgLog, $res, $from, false, $html);
  }
}