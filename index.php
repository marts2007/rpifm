<?php
set_time_limit(0);
error_reporting(E_ALL);
require('curl.php');
require('tcp.php');
require('ewbf.php');
require('claymore.php');

class rpifm {
  public $curl;
  public $motd = "/help - display this help message".PHP_EOL
                 . "/all - display all farms statistic".PHP_EOL
                 . "/list - display all rigs name".PHP_EOL
                 . "/notify - keep notify".PHP_EOL
                 . "/unotify - stop notify".PHP_EOL
                 . "/farm_name - show farm info by it`s name".PHP_EOL;
  public $cmd = "commands:".PHP_EOL
                . "/reboot - reboot OS".PHP_EOL
                . "/restart - restart miner".PHP_EOL
                . "/shutdown - shutdown the system".PHP_EOL;
  public $vars;
  public $config;
  public $farms;
  public $active = 'false';

  public function __construct($argv){
    $this->config = require('config.php');
    $this->curl = new curl();
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      //винда :(
    } else {
       declare(ticks = 1); // enable signal handling
       pcntl_signal(SIGINT, array($this,'saveAllConfigs'));
       pcntl_signal(SIGTERM, array($this,'saveAllConfigs'));
    }

    if (file_exists('vars.php')) {
      $config = json_decode(file_get_contents('vars.php'));
      $this->vars = $config;
    } else {
      file_put_contents('vars.php'
        , json_encode(array('lastupdateid'=>0,'notifylist'=>array())));
    }

    if (isset($this->config['farms'])) {
      if (count($this->config['farms'])>0) {
        //инициализируем майнер вотчи
        foreach($this->config['farms'] as $name=>$farm) {
          switch ($farm['miner']) {
            case 'claymore':
              $this->farms[$name]= new claymore(array(
                                      'name'=>$name,
                                      'host'=>$farm['host'],
                                      'port'=>$farm['port'],
                                      'psw'=>$farm['psw'],
                                      'gpu'=>$farm['gpu'],
                                      'critemp'=>$farm['critemp'],
                                      'crispeed'=>$farm['crispeed'],
                                      'alerts'=>$farm['alerts']
                                        ));
            break;
            case 'ewbf':
              $this->farms[$name]= new ewbf(array(
                                      'name'=>$name,
                                      'host'=>$farm['host'],
                                      'port'=>$farm['port'],
                                      'gpu'=>$farm['gpu'],
                                      'critemp'=>$farm['critemp'],
                                      'crispeed'=>$farm['crispeed'],
                                      'alerts'=>$farm['alerts']
                                        ));
            break;
          }
        }
      }
    }


    $response = $this->query('getMe');
    if (isset($response->ok)) {
      if ($response->ok=='true') {
        $this->status = true;
      }
    } else var_dump($response);


    if (@$argv[1]=='cron') {
      echo("Starting cron jobs\r\n");
      while(true) {
          if (isset($this->vars->notifylist)){
            if (count($this->vars->notifylist)){
              $text=$this->checkEvents();
              foreach($this->vars->notifylist as $chat_id) {
                if ($text) {
                   $this->query('sendMessage'
                     , array('chat_id'=>$chat_id,'text'=>$text));
                }
              }
            }
          }
        sleep(30);
      }
    }
    else {
      while(true) $this->getUpdates(@$argv[1]);
    }
  }

  private function query($action,$params=null){
      $temp=$this->curl->getUrl('https://api.telegram.org/bot'
        .$this->config['general']['telegramkey'].'/'.$action,$params);
    return json_decode($temp);
  }

  public function getUpdates($arg)
  {
    $response = $this->query('getUpdates'
      , array('offset'=>(float)$this->vars->lastupdateid+1));

    if (isset($response)) {
      if (isset($response->result)) {

        foreach($response->result as $result) {
          if ($result->update_id==$this->vars->lastupdateid) continue;
          $this->vars->lastupdateid = $result->update_id;

          if (isset($result->message)) {
            $string=$result->message->text;
            if(preg_match('~^/(\w+)(@\w+)?$~', $string, $matches))
            {
              $result->message->text=strtolower($matches[1]);
              if ($arg=='watcher') {
                $this->execute($result);
              }
              else {
                $this->parse($result);
              }
            }
          }
        }
      }
    }
    sleep(2);
  }

  public function execute($update)
  {
    $string = $update->message->text;

    $feedback='';
    switch ($string){
      case 'restart':
      $feedback='Restarting miner...'.PHP_EOL;
      $lin='tbc';
      $win='restart_miner.bat';
      break;

      case 'reboot':
      $feedback='rebooting OS...'.PHP_EOL;
      $lin='tbc';
      $win='shutdown /f /r /t 0';
      break;

      case 'shutdown':
      $feedback='Shuting down...'.PHP_EOL;
      $lin='tbc';
      $win='shutdown /f /s /t 0';
      break;

      default:
      foreach($this->farms as $name=>$farm) {
        if(strcasecmp($string, $name)==0) {
          $result=$this->farms[$name]->getData();
          $this->query('sendMessage'
            , array('chat_id'=>$update->message->chat->id
            , 'text'=>$result['text'].PHP_EOL
            . $this->cmd));
          //next command exectly for this rig
          $this->active = 'true';
          break;
        }
        //it's command not for this rig
        $this->active = 'false';
      }
    }

    if ($this->active == 'true')
    {
      $this->query('sendMessage'
        , array('chat_id'=>$update->message->chat->id,'text'=>$feedback));
      if (isset($win) || isset($lin)){
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
          pclose(popen($win, "r"));
          //pclose(popen($win, "r"));
          echo "Command: ".$win.PHP_EOL;
        } else {
          shell_exec("/usr/bin/nohup ".$lin." >/dev/null 2>&1 &");
        }
      }
    }
  }

  public function parse($update)
  {
    $string = $update->message->text;
      $text='';

      switch ($string){

        case 'start':
           $start="Hello, use /help command to see all available options.";
           $this->query('sendMessage'
             , array('chat_id'=>$update->message->chat->id,'text'=>$start));
        break;

        case 'help':
           $this->query('sendMessage'
             , array('chat_id'=>$update->message->chat->id,'text'=>$this->motd));
        break;

        case 'farm_name';
          $string='list';
          $text.="Choose:\r\n";

        case 'list':
          foreach($this->farms as $name=>$farm) {
            $text.="/".$name."\r\n";
          }
          $this->query('sendMessage'
            , array('chat_id'=>$update->message->chat->id,'text'=>$text));
        break;

        case 'all':
          foreach($this->farms as $name=>$farm) {
            $tmp=$farm->getData();
            $text.=$tmp['text']."\r\n";
          }
          $this->query('sendMessage'
          , array('chat_id'=>$update->message->chat->id,'text'=>$text));
        break;

        case 'notify':
          if (!in_array($update->message->chat->id,$this->vars->notifylist)) {
            $this->vars->notifylist[]=$update->message->chat->id;
            $this->saveConfig();
            $this->query('sendMessage'
              ,array('chat_id'=>$update->message->chat->id
              ,'text'=>'ok, would keep you informed!'));
          } else $this->query('sendMessage'
            ,array('chat_id'=>$update->message->chat->id
            ,'text'=>'You are already in my notify list ;)'));
        break;

        case 'unotify':
          if (in_array($update->message->chat->id,$this->vars->notifylist)) {
            $this->vars->notifylist=array_diff($this->vars->notifylist
              , array($update->message->chat->id));
            $this->saveConfig();
            $this->query('sendMessage'
              , array('chat_id'=>$update->message->chat->id
              ,'text'=>'ok, would leave u alone!'));
          }
        break;

        default:

      }
  }

  public function saveConfig(){
    file_put_contents('vars.php',json_encode($this->vars));
  }

  public function saveAllConfigs(){
            $this->saveConfig();
            echo("\r\nAll configs were saved!\r\n");
            die;
  }


  public function checkEvents(){
      //farm6 проверка температур и хешрейта
      $text='';
      //проверка всех ферм
      foreach($this->farms as $name => $farm) {
        if ($farm->alerts) {
          if ($farm->critemp) $text.=$farm->checkTemp();
          if ($farm->crispeed)$text.=$farm->checkSpeed();
        }
      }

      return $text;
  }
}

$rpifm = new rpifm($argv);

?>
