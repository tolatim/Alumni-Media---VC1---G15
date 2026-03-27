const WebSocket = require('ws');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(bodyParser.json());


app.post('/event', (req, res) => {
    const { type, data = {}, audience } = req.body || {};

    if (audience === 'admins') {
        sendToAdmins({ type, data, audience: 'admins' });
        return res.json({ status: 'event processed', delivered_to: 'admins' });
    }

    const getTargetUserId = () => {
        switch (type) {
            case 'connection_request':
                return data.addressee_id;
            case 'accept_request':
            case 'reject':
            case 'unfriend':
                return data.requester_id;
            case 'block':
                return data.blocker_id;
            default:
                return null;
        }
    };

    const targetIdsFromPayload = Array.isArray(data.target_user_ids)
        ? data.target_user_ids
        : (data.target_user_id != null ? [data.target_user_id] : []);

    const recipientIds = targetIdsFromPayload.length
        ? targetIdsFromPayload
        : [getTargetUserId()].filter((value) => value != null);

    const deliveredTo = [];
    const failedTo = [];

    recipientIds.forEach((rawId) => {
        const userId = Number(rawId);
        const targetUser = clients[userId];

        if (targetUser && targetUser.readyState === WebSocket.OPEN) {
            targetUser.send(JSON.stringify({ type, data }));
            deliveredTo.push(userId);
            console.log(`Event sent to user ${userId}`);
            return;
        }

        failedTo.push(userId);
        console.log("User offline or socket closed:", userId);
    });

    res.json({ status: 'event processed', delivered_to: deliveredTo, failed_to: failedTo });
});

const server = app.listen(3000, () => {
    console.log('Event server listening on port 3000');
});

const wss = new WebSocket.Server({ server, path: '/ws'  }, () => {
    console.log('WebSocket server running on port 3000');
});

let clients = {};
const adminClients = new Set();

const sendToAdmins = (payload) => {
    const deadSockets = [];

    adminClients.forEach((ws) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(payload));
        } else {
            deadSockets.push(ws);
        }
    });

    deadSockets.forEach((ws) => adminClients.delete(ws));
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
                const role = String(data.role || '').toLowerCase();
                const channel = String(data.channel || '').toLowerCase();

                if (role === 'admin' || channel === 'admin') {
                    ws.isAdmin = true;
                    adminClients.add(ws);
                }

                console.log("User authenticated:", data.user_id);

            }

        } catch (err) {
            console.log('Invalid message', err);
        }
    });

    ws.on('close', () => {

        if (ws.user_id && clients[ws.user_id] === ws) {

            delete clients[ws.user_id];

            console.log(`User ${ws.user_id} disconnected`);

        }

        if (ws.isAdmin) {
            adminClients.delete(ws);
        }

    });
});

