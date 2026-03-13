// server.js
const WebSocket = require('ws');
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(cors()); // allow Laravel/other frontend
app.use(bodyParser.json());

// WebSocket server on port 8081
const wss = new WebSocket.Server({ port: 8081 }, () => {
    console.log('WebSocket server running on ws://localhost:8081');
});

// Broadcast helper
function broadcast(data) {
    console.log('Broadcasting:', data); // debug
    console.log('Clients connected:', wss.clients.size);
    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

// HTTP endpoint for Laravel to send events
app.post('/event', (req, res) => {
    const { type, data } = req.body;
    console.log('Received POST:', type, data); // debug

    if (type === 'new_user') {
        const user = {
            id: data.id,
            first_name: data.first_name || data.name?.split(' ')[0] || 'Unknown',
            last_name: data.last_name || data.name?.split(' ')[1] || ''
        };
        broadcast({ type, data: user });
    }
    else if(type === 'login') {
        const user = {
            id: data.id,
            first_name: data.first_name || data.name?.split(' ')[0] || 'Unknown',
            last_name: data.last_name || data.name?.split(' ')[1] || ''
        };
        broadcast({ type, data: user });
    }

    res.sendStatus(200);
});
// Start HTTP server on port 3000 (avoiding 8080 conflict)
app.listen(3000, () => console.log('HTTP server running on port 3000'));