<?php

require dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = \json_decode(\file_get_contents("config.json"), true);
// -d body="$fname" -d title="Ranked Fetched"

$argline = implode(' ', array_slice($argv, 1));

if (strpos($argline, '-d ') !== false) {
  $args = explode('-d ', $argline);
  $data = "";

  foreach ($args as $a) {
    if (empty($a)) continue;
    $arg = explode('=', $a);
    $arg[1] = trim($arg[1], " \"");
    if ($arg[0] === 'title')
      $data = "\> _*".addcslashes($arg[1], "_*[]()~`>#+-=|{}.!\\")."*_\n".$data;
    else
      $data = $data."\n".addcslashes($arg[1], "_*[]()~`>#+-=|{}.!\\");
  }
} else {
  $data = $argline;
}

$loop = React\EventLoop\Factory::create();
$connector = new React\Socket\Connector($loop);

$connector->connect($config['server'])->then(function (React\Socket\ConnectionInterface $connection) use ($data) {
    $connection->write($data."\n");
    $connection->end();
  },
  function (Exception $exception) {
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage()."\n";
  }
);

$loop->run();