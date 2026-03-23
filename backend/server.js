const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);
    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });
});

app.post('/event', (req, res) => {
    const { type, data } = req.body;
    console.log('Event received from Laravel:', type, data);
    io.emit(type, data);
    res.json({ status: 'ok' });
});

server.listen(3000, () => {
    console.log('WebSocket server running on http://localhost:3000');
});