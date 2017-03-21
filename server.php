<?php
class swooleServer{
    private $redis;
    private $swooleKey;
    public function __construct(){
        $this->redis = new redis();
        $this->redis->connect('192.168.1.180',6380);
        $this->swooleKey = 'swoole';
    }
    public function start (swoole_websocket_server $server){
        echo "start\n";
        //清空redis
        $this->redis->delete($this->swooleKey);
    }
    public function open(swoole_websocket_server $server, $request){
        echo "server: handshake success with fd{$request->fd}\n";
        //$server->push($request->fd, $request->data);
    }
    public function message(swoole_websocket_server $server, $frame){
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $data = json_decode($frame->data,true);
        switch ($data['type']) {
            case 'login':
                foreach ($server->connections as $cfd) {
                        if($cfd != $frame->fd){
                              $msg = "{$data['name']}已登陆聊天";
                              $jsonData = array(
                                'type'=>'login',
                                'fd'=>$frame->fd,
                                'name'=>$data['name'],
                                'msg'=>$msg
                              );
                              $jsonData = json_encode($jsonData,true);
                              $server->push($cfd, $jsonData);
                        }
                }
                $this->redis->hset($this->swooleKey,$frame->fd,$data['name']);
                break;
            
            case 'txt':
                foreach ($server->connections as $cfd) {
                        if($cfd != $frame->fd){
                              $jsonData = array(
                                'type' => 'txt',
                                'msg' => $data['msg'],
                                'fromUserName' => $data['fromUserName']
                              );
                              $jsonData = json_encode($jsonData,true);
                              $server->push($cfd, $jsonData);
                              //$server->push($cfd, "{$frame->data}");
                        }
                }
                break;
            case 'private':
                $jsonData = array(
                'type' => 'private',
                'fromUserName' => $data['fromUserName'],
                'msg' => $data['msg']
                );
                $jsonData = json_encode($jsonData,true);
                $server->push($data['toUserFd'], $jsonData);
                break;
        }
    }

    public function close($ser, $fd){
        echo "client {$fd} closed\n";
        foreach ($ser->connections as $cfd) {
                //除关闭用户外的其他用户发起离线通知
                if($cfd != $fd){
                      $name = $this->redis->hget($this->swooleKey,$fd);
                      $jsonData = array(
                        'fd' => $fd,
                        'msg' => "{$name}已退出聊天",
                        'type' => 'close'
                      );
                      $jsonData = json_encode($jsonData,true);
                      $ser->push($cfd, $jsonData);
                }
        }
        $this->redis->hdel($this->swooleKey,$fd);
    }
}

$swooleServer = new swooleServer();
$server = new swoole_websocket_server("0.0.0.0", 9508);
$server->on('start', array($swooleServer, 'start'));
$server->on('open', array($swooleServer, 'open'));
$server->on('message', array($swooleServer, 'message'));
$server->on('close', array($swooleServer, 'close'));
$server->start();
?>