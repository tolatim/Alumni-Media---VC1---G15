<template>
  <div class="flex min-h-screen bg-gray-100">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col">
      <div class="p-6 text-xl font-bold">AlumniAdmin</div>

      <nav class="flex-1 space-y-2 px-4">
        <a class="flex items-center gap-3 px-4 py-2 bg-slate-700 rounded-lg">
          Dashboard
        </a>

        <a class="flex items-center gap-3 px-4 py-2 hover:bg-slate-700 rounded-lg">
          Users
        </a>

        <a class="flex items-center gap-3 px-4 py-2 hover:bg-slate-700 rounded-lg">
          Posts
        </a>

        <a class="flex items-center gap-3 px-4 py-2 hover:bg-slate-700 rounded-lg">
          Reports
        </a>
      </nav>

      <div class="p-4 border-t border-slate-700 flex items-center gap-3">
        <img
          src="https://i.pravatar.cc/40"
          class="w-10 h-10 rounded-full"
        />
        <div>
          <p class="text-sm font-semibold">Sarah Jenkins</p>
          <p class="text-xs text-gray-400">Lead Curator</p>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-6 space-y-6">

      <!-- Navbar -->
      <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow">
        <input
          type="text"
          placeholder="Search alumni, posts, or reports..."
          class="w-1/2 px-4 py-2 border rounded-lg"
        />

        <div class="flex items-center gap-4">
          <button class="text-gray-600">🔔</button>
          <div class="font-medium">Admin Panel</div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-4 gap-6">

        <div class="bg-white p-6 rounded-xl shadow">
          <p class="text-gray-500">Total Users</p>
          <h2 class="text-2xl font-bold">12,450</h2>
        </div>

        <div class="bg-white p-6 rounded-xl shadow">
          <p class="text-gray-500">New Posts (24h)</p>
          <h2 class="text-2xl font-bold">452</h2>
        </div>

        <div class="bg-white p-6 rounded-xl shadow">
          <p class="text-gray-500">Active Reports</p>
          <h2 class="text-2xl font-bold">12</h2>
        </div>

        <div class="bg-white p-6 rounded-xl shadow">
          <p class="text-gray-500">System Status</p>
          <h2 class="text-2xl font-bold text-green-600">Operational</h2>
        </div>

      </div>

      <!-- Chart + Activity -->
      <div class="grid grid-cols-3 gap-6">

        <!-- Chart -->
        <div class="col-span-2 bg-white p-6 rounded-xl shadow">
          <div class="flex justify-between mb-4">
            <h3 class="font-semibold">User Engagement Statistics</h3>
            <select class="border rounded-lg px-2 py-1">
              <option>Last 30 Days</option>
            </select>
          </div>

          <canvas ref="chart"></canvas>
        </div>

        <!-- Activity -->
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="font-semibold mb-4">Recent Activity</h3>

          <ul class="space-y-4 text-sm">
            <li>Marcus T. joined the platform</li>
            <li>Post #8492 was flagged for review</li>
            <li>Class of '98 group verified</li>
            <li>Spam Bot account was banned</li>
          </ul>

        </div>

      </div>

      <!-- Users Table -->
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="flex justify-between mb-4">
          <h3 class="font-semibold">Manage Users</h3>

          <div class="flex gap-2">
            <button class="px-4 py-2 border rounded-lg">Filter</button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">
              + Add User
            </button>
          </div>
        </div>

        <table class="w-full text-sm">
          <thead class="text-gray-500 border-b">
            <tr>
              <th class="text-left py-3">Name</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr class="border-b">
              <td class="py-3 flex items-center gap-3">
                <img src="https://i.pravatar.cc/30" class="rounded-full"/>
                John Doe
              </td>
              <td>Alumnus</td>
              <td class="text-green-600">Active</td>
              <td>2 hours ago</td>
              <td>✏️ 🗑️</td>
            </tr>

            <tr>
              <td class="py-3 flex items-center gap-3">
                <img src="https://i.pravatar.cc/31" class="rounded-full"/>
                Jane Smith
              </td>
              <td>Moderator</td>
              <td class="text-green-600">Active</td>
              <td>14 hours ago</td>
              <td>✏️ 🗑️</td>
            </tr>

          </tbody>
        </table>

      </div>

    </main>

  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import Chart from "chart.js/auto"

const chart = ref(null)

onMounted(() => {
  new Chart(chart.value, {
    type: "bar",
    data: {
      labels: ["1 Oct","5 Oct","10 Oct","15 Oct","20 Oct","25 Oct","30 Oct"],
      datasets: [
        {
          label: "Active Users",
          data: [200,300,450,380,500,420,600],
          backgroundColor: "#94a3b8"
        }
      ]
    }
  })
})
</script>