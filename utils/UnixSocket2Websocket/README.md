UnixSocket2WebSocket
====================

This is a simple websockets push server. It receives messages from UNIX socket and retransmits to clients connected to websockets.

Current version requires Python 3.6 and [Websockets module](https://websockets.readthedocs.io/en/stable/intro.html) 8.1. 
There is also version compatible with Python 3.5 and websockets 7.0.

Usage
-----

Start the server:

    socket2ws.py /opt/config.json

Example connection from web browser:

```javascript
var websocket = new WebSocket("ws://127.0.0.1:8443/path/");
websocket.onmessage = function(e) {
    console.log(e.data);
}
```

Above code waits for incoming messages and prints to the console when it's received.

Example PHP code sending messages to websockets through this server:

```PHP
    $fp = stream_socket_client("unix:///tmp/web.socket", $errno, $errstr, 5);
    if (!$fp) {
        error_log("$errstr ($errno)");
    } else {
        $path = '/path/';
        $msg = "Hello World!"
        fwrite($fp, "$path\0$msg");
        fclose($fp);
    }
```

Example Bash script sending messages to websockets through this server:

```Bash
echo -e "/path/\0Hello World!" | socat -d -d - UNIX-CONNECT:/tmp/web.socket
```

Message sent to the UNIX soecket should contain path string and message string separated by null byte, in the form of `/path/\0Message string`. Message string can be also JSON encoded data.
