const WebSocket = require('ws');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(bodyParser.json());

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

const sendToRecipients = (recipients, payload) => {
    const uniqueRecipients = [...new Set((Array.isArray(recipients) ? recipients : [])
        .map((id) => Number(id))
        .filter((id) => Number.isInteger(id) && id > 0))];

    let delivered = 0;

    uniqueRecipients.forEach((recipientId) => {
        const target = clients[recipientId];
        if (target && target.readyState === WebSocket.OPEN) {
            target.send(JSON.stringify(payload));
            delivered += 1;
        }
    });

    return delivered;
};

const resolveLegacyRecipient = (type, data) => {
    if (type === 'connection_request') return data.addressee_id;
    if (type === 'accept_request') return data.requester_id;
    if (type === 'unfriend') return data.requester_id;
    if (type === 'reject') return data.requester_id;
    if (type === 'block') return data.blocker_id;
    return null;
};

app.post('/event', (req, res) => {
    const { type, data = {}, recipients, audience } = req.body || {};

    if (audience === 'admins') {
        sendToAdmins({
            type,
            data,
            audience: 'admins',
        });

        return res.json({
            status: 'event processed',
            delivered_to: 'admins',
        });
    }

    const targetRecipients = Array.isArray(recipients) && recipients.length
        ? recipients
        : [resolveLegacyRecipient(type, data)].filter(Boolean);

    const delivered = sendToRecipients(targetRecipients, {
        type,
        data,
    });

    if (delivered > 0) {
        console.log(`Event ${type} sent to ${delivered} recipient(s)`);
    } else {
        console.log(`No connected recipients for ${type}`);
    }

    res.json({
        status: 'event processed',
        delivered,
    });
});

const server = app.listen(3000, () => {
    console.log('Event server listening on port 3000');
});

const wss = new WebSocket.Server({ server, path: '/ws' }, () => {
    console.log('WebSocket server running on port 3000');
});

wss.on('connection', (ws) => {
    console.log('New client connected');

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message.toString());

            console.log('Received:', data);

            if (data.type === 'auth') {
                if (ws.user_id && clients[ws.user_id] === ws) {
                    delete clients[ws.user_id];
                }

                ws.user_id = data.user_id;
                clients[data.user_id] = ws;
                const role = String(data.role || '').toLowerCase();
                const channel = String(data.channel || '').toLowerCase();

                if (role === 'admin' || channel === 'admin') {
                    ws.isAdmin = true;
                    adminClients.add(ws);
                }

                console.log('User authenticated:', data.user_id);
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
