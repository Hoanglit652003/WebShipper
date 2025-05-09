// websocket-server.js
// Node.js WebSocket server using 'ws' library

const WebSocket = require('ws');
const axios = require('axios');
const querystring = require('querystring');

// Configuration
const WS_PORT = 8081;
const UPDATE_URL = 'http://localhost/admin/update_location.php'; // URL to your PHP API

// Khởi tạo server lắng nghe WS_PORT
const wss = new WebSocket.Server({ port: WS_PORT }, () => {
  console.log(`WebSocket server started on ws://localhost:${WS_PORT}`);
});

// Lưu trữ các kết nối client
const clients = new Set();

wss.on('connection', (ws) => {
  clients.add(ws);
  console.log('New client connected. Total clients:', clients.size);

  ws.on('message', async (message) => {
    try {
      const data = JSON.parse(message);
      const { staffId, lat, lng } = data;

      // 1. Gửi đến các client khác (broadcast)
      for (let client of clients) {
        if (client !== ws && client.readyState === WebSocket.OPEN) {
          client.send(JSON.stringify(data));
        }
      }

      // 2. Gọi PHP API để lưu vào DB
      const postData = querystring.stringify({
        id: staffId,
        lat,
        lng
      });

      try {
        const resp = await axios.post(UPDATE_URL, postData, {
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        console.log('Location update response:', resp.data);
      } catch (err) {
        console.error('Error calling update_location.php:', err.message);
      }

    } catch (err) {
      console.error('Invalid message format:', err);
    }
  });

  ws.on('close', () => {
    clients.delete(ws);
    console.log('Client disconnected. Total clients:', clients.size);
  });

  ws.on('error', (err) => {
    console.error('WebSocket error:', err);
  });
});

process.on('SIGINT', () => {
  console.log('Shutting down server...');
  wss.close(() => process.exit());
});

