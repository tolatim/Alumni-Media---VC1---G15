const WEBSOCKET_URL = import.meta.env.VITE_WEBSOCKET_URL ;
const POST_EVENTS = new Set(["post_created", "post_updated", "post_deleted", "post_comment_updated", "post_like_updated"]);
const RECONNECT_DELAY_MS = 1500;
const CROSS_TAB_CHANNEL = "alumni-media-post-hub";
const TAB_ID = `${Date.now()}-${Math.random().toString(36).slice(2)}`;

const listeners = new Set();
const seenEventIds = new Set();
let socket = null;
let authenticatedUserId = null;
let reconnectTimer = null;
let broadcastChannel = null;
let crossTabBound = false;

const rememberEvent = (eventId) => {
  if (!eventId) return;
  seenEventIds.add(eventId);
  if (seenEventIds.size > 200) {
    const oldest = seenEventIds.values().next().value;
    seenEventIds.delete(oldest);
  }
};

const emitToListeners = (payload) => {
  listeners.forEach((handler) => handler(payload));
};

const broadcastToSiblingTabs = (payload) => {
  try {
    if (typeof BroadcastChannel !== "undefined") {
      if (!broadcastChannel) {
        broadcastChannel = new BroadcastChannel(CROSS_TAB_CHANNEL);
      }
      broadcastChannel.postMessage(payload);
    }
  } catch {
    // ignore broadcast channel failures
  }

  try {
    window.localStorage.setItem(
      CROSS_TAB_CHANNEL,
      JSON.stringify({ ...payload, __storageStamp: Date.now() })
    );
    window.localStorage.removeItem(CROSS_TAB_CHANNEL);
  } catch {
    // ignore storage failures
  }
};

const dispatchPostEvent = (payload, options = {}) => {
  if (!payload || !POST_EVENTS.has(payload.type)) return;

  const eventId =
    payload.__eventId || `${payload.type}:${payload.data?.post_id || payload.data?.postId || "unknown"}:${Date.now()}:${Math.random().toString(36).slice(2)}`;

  if (seenEventIds.has(eventId)) return;
  rememberEvent(eventId);

  const eventPayload = {
    ...payload,
    __eventId: eventId,
    __sourceTabId: payload.__sourceTabId || TAB_ID,
  };

  emitToListeners(eventPayload);

  if (options.broadcast === false) return;
  broadcastToSiblingTabs(eventPayload);
};

const scheduleReconnect = () => {
  if (reconnectTimer || listeners.size === 0) return;
  reconnectTimer = window.setTimeout(() => {
    reconnectTimer = null;
    openSocket();
  }, RECONNECT_DELAY_MS);
};

const clearReconnectTimer = () => {
  if (!reconnectTimer) return;
  window.clearTimeout(reconnectTimer);
  reconnectTimer = null;
};

const closeSocketIfUnused = () => {
  if (listeners.size > 0) return;
  clearReconnectTimer();
  if (!socket) return;
  if (
    socket.readyState === WebSocket.OPEN ||
    socket.readyState === WebSocket.CONNECTING
  ) {
    socket.close();
  }
  socket = null;
};

const bindCrossTabBridge = () => {
  if (crossTabBound || typeof window === "undefined") return;
  crossTabBound = true;

  if (typeof BroadcastChannel !== "undefined") {
    try {
      if (!broadcastChannel) {
        broadcastChannel = new BroadcastChannel(CROSS_TAB_CHANNEL);
      }
      broadcastChannel.addEventListener("message", (event) => {
        const payload = event?.data;
        if (!payload || payload.__sourceTabId === TAB_ID) return;
        dispatchPostEvent(payload, { broadcast: false });
      });
    } catch {
      // ignore broadcast channel failures
    }
  }

  window.addEventListener("storage", (event) => {
    if (event.key !== CROSS_TAB_CHANNEL || !event.newValue) return;

    try {
      const payload = JSON.parse(event.newValue);
      if (!payload || payload.__sourceTabId === TAB_ID) return;
      dispatchPostEvent(payload, { broadcast: false });
    } catch {
      // ignore invalid storage payloads
    }
  });
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

  clearReconnectTimer();

  socket = new WebSocket(WEBSOCKET_URL);

  socket.addEventListener("open", () => {
    sendAuth();
  });

  socket.addEventListener("message", (event) => {
    try {
      const payload = JSON.parse(event.data);
      if (!payload || !POST_EVENTS.has(payload.type)) return;
      dispatchPostEvent(payload);
    } catch {
      // ignore parse errors
    }
  });

  socket.addEventListener("close", () => {
    socket = null;
    if (listeners.size > 0) {
      scheduleReconnect();
    }
  });

  socket.addEventListener("error", () => {
    if (
      socket &&
      (socket.readyState === WebSocket.OPEN ||
        socket.readyState === WebSocket.CONNECTING)
    ) {
      socket.close();
      return;
    }

    socket = null;
    if (listeners.size > 0) {
      scheduleReconnect();
    }
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
  bindCrossTabBridge();
  openSocket();
  return () => {
    listeners.delete(handler);
    closeSocketIfUnused();
  };
};

const setPostHubUserId = (userId) => {
  authenticatedUserId = userId;
  if (listeners.size > 0) {
    openSocket();
  }
  sendAuth();
};

export { dispatchPostEvent, subscribeToPostEvents, setPostHubUserId };
