const WEBSOCKET_URL = import.meta.env.VITE_WEBSOCKET_URL || "ws://localhost:8081";
const POST_EVENTS = new Set(["post_created", "post_updated", "post_deleted"]);

const listeners = new Set();
let socket = null;
let authenticatedUserId = null;

const openSocket = () => {
  if (socket && socket.readyState === WebSocket.OPEN) return;

  socket = new WebSocket(WEBSOCKET_URL);

  socket.addEventListener("open", () => {
    sendAuth();
  });

  socket.addEventListener("message", (event) => {
    try {
      const payload = JSON.parse(event.data);
      if (!payload || !POST_EVENTS.has(payload.type)) return;
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

const sendAuth = () => {
  if (!socket || socket.readyState !== WebSocket.OPEN) return;
  if (!authenticatedUserId) return;
  socket.send(JSON.stringify({ type: "auth", user_id: authenticatedUserId }));
};

const subscribeToPostEvents = (handler) => {
  if (typeof handler !== "function") {
    return () => {};
  }
  listeners.add(handler);
  openSocket();
  return () => {
    listeners.delete(handler);
  };
};

const setPostHubUserId = (userId) => {
  authenticatedUserId = userId;
  sendAuth();
};

export { subscribeToPostEvents, setPostHubUserId };
