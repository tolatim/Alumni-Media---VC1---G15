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
        const sockets = clients.get(userId);

        if (!sockets || sockets.size === 0) {
            failedTo.push(userId);
            console.log("User offline or socket closed:", userId);
            return;
        }

        let sent = false;
        const deadSockets = [];

        sockets.forEach((socket) => {
            if (socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({ type, data }));
                sent = true;
                return;
            }
            deadSockets.push(socket);
        });

        deadSockets.forEach((socket) => sockets.delete(socket));
        if (sockets.size === 0) {
            clients.delete(userId);
        }

        if (sent) {
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

const clients = new Map();
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
                const key = Number(data.user_id);
                const existing = clients.get(key) || new Set();
                existing.add(ws);
                clients.set(key, existing);
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

        if (ws.user_id != null) {
            const key = Number(ws.user_id);
            const existing = clients.get(key);
            if (existing) {
                existing.delete(ws);
                if (existing.size === 0) {
                    clients.delete(key);
                }
            }
            console.log(`User ${ws.user_id} disconnected`);
        }

        if (ws.isAdmin) {
            adminClients.delete(ws);
        }

    });
});

