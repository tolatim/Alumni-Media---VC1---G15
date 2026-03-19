const WebSocket = require('ws');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(bodyParser.json());

const wss = new WebSocket.Server({ port: 8081 }, () => {
    console.log('WebSocket server running on port 8081');
});

let clients = {};
const BROADCAST_EVENTS = new Set(['post_created', 'post_updated', 'post_deleted']);

const broadcastToAllClients = (payload) => {
    const message = JSON.stringify(payload);
    let recipients = 0;
    wss.clients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
            recipients += 1;
        }
    });
    return recipients;
};

wss.on('connection', (ws) => {
    console.log("New client connected");

    ws.on('message', (message) => {
        try {

            const data = JSON.parse(message.toString());

            console.log("Received:", data);

            if (data.type === 'auth') {

                ws.user_id = data.user_id;
                clients[data.user_id] = ws;

                console.log("User authenticated:", data.user_id);

            }

        } catch (err) {
            console.log('Invalid message', err);
        }
    });

    ws.on('close', () => {

        if (ws.user_id) {

            delete clients[ws.user_id];

            console.log(`User ${ws.user_id} disconnected`);

        }

    });
});

app.post('/event', (req, res) => {

    const { type, data } = req.body;
    if (BROADCAST_EVENTS.has(type)) {
        const recipients = broadcastToAllClients({ type, data });
        console.log(`Broadcast event '${type}' to ${recipients} clients`);

        return res.json({
            status: 'event broadcasted',
            recipients,
        });
    }

    let targetUser;

    if(type === 'connection_request'){
        targetUser = clients[data.addressee_id];
    }
    else if(type === 'accept_request'){
        targetUser = clients[data.requester_id]
    }
    else if(type === 'unfriend'){
        targetUser = clients[data.requester_id]
    }
    else if(type === 'reject'){
        targetUser = clients[data.requester_id]
    }
    else if(type === 'block'){
        targetUser = clients[data.blocker_id]
    }

    if (targetUser && targetUser.readyState === WebSocket.OPEN) {

        targetUser.send(JSON.stringify({
            type: type,
            data: data
        }));

        console.log(`Event sent to user ${data.addressee_id}`);

    } else {

        console.log(`User ${data.addressee_id} is offline`);

    }

    res.json({
        status: 'event processed'
    });

});

app.listen(3000, () => {
    console.log('Event server listening on port 3000');
});
