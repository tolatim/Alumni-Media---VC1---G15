<script setup>
import {ref, onMounted} from 'vue';
const logins = ref([])

onMounted(() => {
  const ws = new WebSocket('ws://localhost:8081');

  ws.onopen = () =>  console.log('Connected to websocket server')

  ws.onmessage = (event) => {
    const message = JSON.parse(event.data)

    if(message.type === 'login'){
      logins.value = [...logins.value, message.data]
    }
  }

  ws.onclose = () => console.log('Websocket disconnected')
})
</script>

<template>
  <div>
    <h2>Recent Login</h2>
    <ul>
      <li v-for="login in logins" :key="login.id">
        {{ login.first_name }} {{ login.last_name }}
      </li>
    </ul>
  </div>
</template>