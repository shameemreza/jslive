const WebSocketServer = require('ws').Server,
        express = require('express'),
        https = require('https'),
        app = express(),
        fs = require('fs');

const pkey = fs.readFileSync(PATH_TO_CERTIFICATE_KEY),
        pcert = fs.readFileSync(PATH_TO_CERTIFICATE_CERT),
        options = {key: pkey, cert: pcert, passphrase: ''};
var wss = null, sslSrv = null, visitors = [], rooms = [], expiryTime = 30*60*1000, maxUsers = 8;

// use express static to deliver resources HTML, CSS, JS, etc)
// from the public folder 
app.use(express.static('public'));

app.use(function (req, res, next) {
    if (req.headers['x-forwarded-proto'] === 'http') {
        return res.redirect(['https://', req.get('Host'), req.url].join(''));
    }
    next();
});


var checkArray = function (array) {
    Object.keys(array).map(function (item) {
        if (array[item] && (!array[item].pageRef || array[item].added + expiryTime < +new Date())) {
            array.splice(item, 1);
            //delete array[item];
        }
    });
    return array;
};


// start server (listen on port 443 - SSL)
sslSrv = https.createServer(options, app).listen(3434);
//console.log("The HTTPS server is up and running");

// create the WebSocket server
//wss = new WebSocketServer({port: 8080});
wss = new WebSocketServer({server: sslSrv});
//console.log("WebSocket Secure server is up and running.");

/** successful connection */
wss.on('connection', function (client) {
//    console.log("A new WebSocket client was connected.");

    var notifyWhoIsonline = function (room, client) {
        var obj = {'action': 'whoIsonline', 'room': room, 'visitors': visitors[room]};
        var message = JSON.stringify(obj);
        var array = rooms[room];
        Object.keys(array).map(function (item) {
//            console.log('notifyWhoIsonline', message);
            if (client === array[item]) {
                array[item].send(message);
            }
        });
    };

    var notifyWhoIsRoom = function (room, client) {
        var obj = {'action': 'whoIsroom', 'room': room, 'count': visitors[room].length};
        var message = JSON.stringify(obj);
        var array = rooms[room];
        Object.keys(array).map(function (item) {
            if (client === array[item]) {
                array[item].send(message);
            }
        });
    };

    var notifyUsersOfConnection = function (room, client, sessionId, visitorId) {

        //echo "User subscribed to room ".$room ."\n";
        var obj = {'action': 'newSub', 'room': room, 'count': visitors[room].length, sessionId: sessionId, visitorId: visitorId};
        var message = JSON.stringify(obj);
        var array = rooms[room];
        Object.keys(array).map(function (item) {
            if (client !== array[item]) {
                array[item].send(message);
            }
        });
    };

    var notifyUsersOfDisconnection = function (room, key) {

        var visitorId = null;
//        console.log(visitors, room, key);
        if (visitors[room][key]) {
            visitorId = visitors[room][key];
            visitors[room].splice(key, 1);
            visitors[room] = checkArray(visitors[room]);
        }

        var obj = {'action': 'imOffline', 'room': room, 'visitors': visitorId, 'count': visitors[room].length};
        var message = JSON.stringify(obj);
        var array = rooms[room];
        Object.keys(array).map(function (item) {
//            console.log('notifyUsersOfDisconnection', item);
            array[item].send(message);
        });
    };

    client.on('close', function () {
        Object.keys(rooms).map(function (room) {
            Object.keys(rooms[room]).map(function (conns) {
                if (rooms[room][conns] === client) {
                    rooms[room].splice(conns, 1);
                    visitors[room] = checkArray(visitors[room]);
                    notifyUsersOfDisconnection(room, conns);
                }
            });
        });
    });

    /** incomming message */
    client.on('message', function (message) {
        /** broadcast message to all clients */
        //wss.broadcast(message, client);

        data = JSON.parse(message);
//        console.log('message', message);
        var action = data.action;
        var room = (data.room) ? data.room : "";

        if (action === 'giveOnline') {
            notifyWhoIsonline(room, client);
        }

        if (action === 'giveCountOnline') {
            notifyWhoIsRoom(room, client);
        }

        if (action === 'setCallerInfo') {
            if (visitors[room]) {
                for (var i = 0; i < visitors[room].length; ++i) {
                    if (visitors[room][i].sessionId == data.sessionId) {
                        visitors[room][i].name = data.callerInfo.name;
                    }
                }
            }
        }

        if ((action === 'subscribe') && room) {


            if (((rooms[room] !== undefined) && rooms[room] !== client) || (rooms[room] === undefined)) {
                if (rooms[room] !== undefined && rooms[room].length >= maxUsers) {
                    var obj = {'action': 'subRejected'};
                    var message = JSON.stringify(obj);
                    client.send(message);
                } else {
                    if (!rooms[room]) {
                        rooms[room] = new Array();
                    }
                    rooms[room].push(client);
                    if (data.visitorId) {
                        var visitorId = data.visitorId;
                        var vis = {'visitorId': visitorId, 'pageRef': data.referrer, 'sessionId': data.sessionId, 'ua': data.ua, 'added': +new Date()};
                        if (!visitors[room]) {
                            visitors[room] = new Array();
                        }
                        visitors[room].push(vis);
                        visitors[room] = checkArray(visitors[room]);
                    }
                    notifyUsersOfConnection(room, client, data.sessionId, data.visitorId);
                }

            } else {
                var obj = {'action': 'subRejected'};
                var message = JSON.stringify(obj);
                client.send(message);
            }
        } else if (room && rooms[room]) {
            //send to everybody subscribed to the room received except the sender
            var array = rooms[room];
            Object.keys(array).map(function (item) {
                if (client !== array[item]) {
                    array[item].send(message);
                }
            });
        }
    });
});



// broadcasting the message to all WebSocket clients.
wss.broadcast = function (data, exclude) {
    var i = 0, n = this.clients ? this.clients.length : 0, client = null;
    if (n < 1)
        return;
//    console.log("Broadcasting message to all " + n + " WebSocket clients.");
    for (; i < n; i++) {
        client = this.clients[i];
        // don't send the message to the sender...
        if (client === exclude)
            continue;
        if (client.readyState === client.OPEN)
            client.send(data);
        else
            console.error('Error: the client state is ' + client.readyState);
    }
};