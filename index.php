<?php
set_time_limit(0);
error_reporting(E_ALL);
require('curl.php');
require('tcp.php');
require('ewbf.php');
require('claymore.php');



class rpifm {
  public $curl;
  public $motd = "commands: \r\n"
                 . "/help - display this help message\r\n"
                 . "/all - display all ferms statistic\r\n"
                 . "/list - display all rigs name\r\n"
                 . "/notify - keep notify\r\n"
                 . "/unotify - stop notify\r\n"
                 . "/ferm_name - show ferm info by it`s name\r\n";
  public $vars;
  public $config;
  public $ferms;



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
      file_put_contents('vars.php',json_encode(array('lastupdateid'=>0,'notifylist'=>array())));
    }

    if (isset($this->config['ferms'])) {
      if (count($this->config['ferms'])>0) {
        //инициализируем майнер вотчи
        foreach($this->config['ferms'] as $name=>$ferm) {
          switch ($ferm['miner']) {
            case 'claymore':
              $this->ferms[$name]= new claymore(array(
                                      'name'=>$name,
                                      'host'=>$ferm['host'],
                                      'port'=>$ferm['port'],
                                      'psw'=>$ferm['psw'],
                                      'gpu'=>$ferm['gpu'],
                                      'critemp'=>$ferm['critemp'],
                                      'crispeed'=>$ferm['crispeed'],
                                      'alerts'=>$ferm['alerts']
                                        ));
            break;
            case 'ewbf':
              $this->ferms[$name]= new ewbf(array(
                                      'name'=>$name,
                                      'host'=>$ferm['host'],
                                      'port'=>$ferm['port'],
                                      'gpu'=>$ferm['gpu'],
                                      'critemp'=>$ferm['critemp'],
                                      'crispeed'=>$ferm['crispeed'],
                                      'alerts'=>$ferm['alerts']
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
      while(1==1) {
          if (isset($this->vars->notifylist)){
            if (count($this->vars->notifylist)){
              $text=$this->checkEvents();
              foreach($this->vars->notifylist as $chat_id) {
                if ($text) {
                   $this->query('sendMessage',array('chat_id'=>$chat_id,'text'=>$text));
                }
              }
            }
          }
        sleep(2);
      }
    } else
    while(1==1) $this->getUpdates();
  }

  private function query($action,$params=null){
      $temp=$this->curl->getUrl('https://api.telegram.org/bot'.$this->config['general']['telegramkey'].'/'.$action,$params);
    return json_decode($temp);
  }

  public function getUpdates(){

    $response = $this->query('getUpdates',array('offset'=>(float)$this->vars->lastupdateid));

    if (isset($response)) {
      if (isset($response->result)) {

        foreach($response->result as $result) {
          if ($result->update_id==$this->vars->lastupdateid) continue;
          $this->vars->lastupdateid = $result->update_id;

          if (isset($result->message)) {
              $this->parse($result);
          }
        }
      }
    }


    sleep(1);
  }

  public function parse($update){
    $string = strtolower($update->message->text);
    if(strpos($string, '/') !== false) {

      switch ($string){
        case '/start':
           $start="Hello, use /help command to see all available options.";
           $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>$start));
        break;

        case '/help':
           $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>$this->motd));
        break;

        case '/list':
          $text='';
          foreach($this->ferms as $name=>$ferm) {
            $text.="/".$name."\r\n";
          }
          $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>$text));
        break;

        case '/all':
          $text='';
          foreach($this->ferms as $name=>$ferm) {
            $tmp=$ferm->getData();
            $text.=$tmp['text']."\r\n";
          }
          $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>$text));
        break;

        case '/notify':
          if (!in_array($update->message->chat->id,$this->vars->notifylist)) {
            $this->vars->notifylist[]=$update->message->chat->id;
            $this->saveConfig();
            $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>'ok, would keep you informed!'));
          } else $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>'You are already in my notify list ;)'));
        break;

        case '/unotify':
          if (in_array($update->message->chat->id,$this->vars->notifylist)) {
            $this->vars->notifylist=array_diff($this->vars->notifylist, array($update->message->chat->id));
            $this->saveConfig();
            $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>'ok, would leave u alone!'));
          }
        break;

        default:
        $string=substr($string, 1);

          if (isset($this->ferms[$string])) {
            $result=$this->ferms[$string]->getData();
            if (is_array($result)) {
              $this->query('sendMessage',array('chat_id'=>$update->message->chat->id,'text'=>$result['text']."\r\n"));
            }
          }
      }
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
      //ferm6 проверка температур и хешрейта
      $text='';
      //проверка всех ферм
      foreach($this->ferms as $name => $ferm) {
        if ($ferm->alerts) {
          if ($ferm->critemp) $text.=$ferm->checkTemp();
          if ($ferm->crispeed)$text.=$ferm->checkSpeed();
        }
      }

      return $text;
  }
}

$rpifm = new rpifm($argv);

?>
