<?php 

function parseUpdate(&$u, &$tgLog, &$config) {
  $msg = trim(strtolower($u->message->text));

  $msg = explode($br, $msg);

  $from = $u->message->from->id;
  if (!isset($config['users'][$from]))
    return;
  
  $lastPoll = $u->update_id;
  if (isset($config['commands'][ $msg[0] ])) {
    $cmd = explode('::', $config['commands'][ $msg[0] ]);
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