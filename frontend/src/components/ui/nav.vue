<template>
  <nav class="flex justify-between items-center px-8 py-4 shadow-md bg-white sticky top-0 z-50">
    
    <!-- Logo -->
    <div>
      <h2 class="text-xl font-bold text-teal-600">
        Alumni Media
      </h2>
    </div>

    <!-- Menu -->
    <div class="flex items-center gap-6">
      
      <RouterLink 
        to="/"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Home
      </RouterLink>

      <RouterLink 
        to="/connection"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Connection
      </RouterLink>

      <RouterLink 
        to="/jobs"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Jobs
      </RouterLink>

      <RouterLink 
        to="/message"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Message
      </RouterLink>

      <!-- Profile -->
      <RouterLink 
        to="/show"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500"
      >
        <img 
          src="https://i.pravatar.cc/150"
          alt="User Profile" 
          class="w-full h-full object-cover"
        >
      </RouterLink>

    </div>
  </nav>
</template>