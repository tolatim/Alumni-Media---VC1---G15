const WEBSOCKET_URL =
  import.meta.env.VITE_WEBSOCKET_URL || "ws://localhost:8081";
const CONNECTION_EVENTS = new Set([
  "connection_request",
  "accept_request",
  "unfriend",
  "reject",
  "block",
]);

const listeners = new Set();
let socket = null;
let authenticatedUserId = null;

const sendAuth = () => {
  if (!socket || socket.readyState !== WebSocket.OPEN) return;
  if (!authenticatedUserId) return;
  socket.send(JSON.stringify({ type: "auth", user_id: authenticatedUserId }));
};

const closeSocketIfUnused = () => {
  if (listeners.size > 0) return;
  if (!socket) return;
  if (
    socket.readyState === WebSocket.OPEN ||
    socket.readyState === WebSocket.CONNECTING
  ) {
    socket.close();
  }
  socket = null;
};

const openSocket = () => {
  if (typeof WebSocket === "undefined") return;
  if (
    socket &&
    (socket.readyState === WebSocket.OPEN ||
      socket.readyState === WebSocket.CONNECTING)
  ) {
    return;
  }

  socket = new WebSocket(WEBSOCKET_URL);

  socket.addEventListener("open", () => {
    sendAuth();
  });

  socket.addEventListener("message", (event) => {
    try {
      const payload = JSON.parse(event.data);
      if (!payload || !CONNECTION_EVENTS.has(payload.type)) return;
      listeners.forEach((handler) => handler(payload));
    } catch {
      // ignore parse errors
    }
  });

  socket.addEventListener("close", () => {
    socket = null;
  });

  socket.addEventListener("error", () => {
    socket = null;
  });
};

const subscribeToConnectionEvents = (handler) => {
  if (typeof handler !== "function") {
    return () => {};
  }

  listeners.add(handler);
  openSocket();

  return () => {
    listeners.delete(handler);
    closeSocketIfUnused();
  };
};

const setConnectionHubUserId = (userId) => {
  authenticatedUserId = userId;
  sendAuth();
};

export { setConnectionHubUserId, subscribeToConnectionEvents };
