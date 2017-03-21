<?php 
  $redis = new redis();
  $redis->connect('192.168.1.180',6380);
  $onlineUsers = $redis->hgetall('swoole');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Title</title>
<script type="text/javascript" src="./jquery.json.js"></script>
<script type="text/javascript" src="./jquery.js"></script>
</head>
<body>
<div id="msg"></div>
<input type="text" id="text">
<select id="userOption">
    <option value="">请选择..</option>
  <?php foreach($onlineUsers as $k=>$user):?>
    <option value="<?php echo $k?>"><?php echo $user;?></option>
  <?php endforeach;?>
</select>
<input type="submit" value="发送数据" onclick="song()">
</body>
<script>
var msg = document.getElementById("msg");
var wsServer = 'ws://192.168.1.180:9508';
//调用websocket对象建立连接：
//参数：ws/wss(加密)：//ip:port （字符串）
var websocket = new WebSocket(wsServer);
var name = GetQueryString('name');
  //onopen监听连接打开
websocket.onopen = function (evt) {
    //alert(JSON.stringify(evt));
  var type = 'login';
  websocket.send('{"name":"'+name+'","type":"'+type+'"}');
  // var msg = new Object();
  // var msg.name = GetQueryString('name');
  // var msg.type = 'login';
  // alert((JSON.stringify(msg));
  //websocket.send(name);
  //websocket.readyState 属性：
  /*
  CONNECTING  0  The connection is not yet open.
  OPEN  1  The connection is open and ready to communicate.
  CLOSING  2  The connection is in the process of closing.
  CLOSED  3  The connection is closed or couldn't be opened.
  */
  //msg.innerHTML = websocket.readyState;
};


 //监听连接关闭
websocket.onclose = function (evt) {
  alert('server closed');
};
 
//onmessage 监听服务器数据推送
websocket.onmessage = function (evt) {
  var jsondata = eval('('+evt.data+')');
  if(jsondata.type == 'login'){
    var optionVal = jsondata.fd;
    var optionTxt = jsondata.name;
    $("#userOption").append('<option value="'+optionVal+'">'+optionTxt+'</option>');
  }
  if(jsondata.type == 'close'){
    $('#userOption').children('option[value="'+jsondata.fd+'"]').remove();
  }
  if(jsondata.type == 'txt'){
    jsondata.msg = jsondata.fromUserName+"对大家说："+jsondata.msg;
  }
  if(jsondata.type == 'private'){
    jsondata.msg = jsondata.fromUserName+"悄悄的对你说："+jsondata.msg;
  }
  msg.innerHTML += jsondata.msg +'<br>';
//    console.log('Retrieved data from server: ' + evt.data);
};
//监听连接错误信息
//  websocket.onerror = function (evt, e) {
//    console.log('Error occured: ' + evt.data);
//  };
function GetQueryString(name)
{
     var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     var r = window.location.search.substr(1).match(reg);
     if(r!=null)return  unescape(r[2]); return null;
}

function song(){
  var text = document.getElementById('text').value;
  document.getElementById('text').value = '';
  var fd = $("#userOption").val();
  var data;
  //private
  if(fd){
    var toUserName = $("#userOption").children("option[value='"+fd+"']").html();
    data = '{"type":"private","msg":"'+text+'","toUserFd":"'+fd+'","toUserName":"'+toUserName+'","fromUserName":"'+name+'"}';
    var fromUserMsg = "你悄悄的对"+toUserName+"说:"+text;
    msg.innerHTML += fromUserMsg +'<br>';
  }else{
  //public
    data = '{"type":"txt","msg":"'+text+'","fromUserName":"'+name+'"}';
    var publicMsg = "你对大家说："+text
    msg.innerHTML += publicMsg +'<br>';
  }
  //alert(data);
  //向服务器发送数据
  websocket.send(data);
}
</script>
</html>