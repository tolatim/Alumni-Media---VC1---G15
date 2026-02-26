<template>
  <div class="col-span-6 space-y-6">
    <!-- Create Post -->
    <div class="bg-white rounded-xl shadow p-4">
      <div class="flex items-center gap-3">
        <img src="https://i.pravatar.cc/150" class="w-10 h-10 rounded-full" />
        <input
          type="text"
          placeholder="Share an update with your alumni network..."
          class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none"
        />
      </div>

      <div class="flex justify-around mt-4 text-sm text-gray-600">
        <button class="hover:text-blue-600">Photo</button>
        <button class="hover:text-green-600">Video</button>
        <button class="hover:text-orange-600">Event</button>
      </div>
    </div>

    <!-- Post Card -->
    <div class="bg-white rounded-xl shadow p-4">
      <div class="flex items-center gap-3">
        <img src="https://i.pravatar.cc/51" class="w-10 h-10 rounded-full" />
        <div>
          <h4 class="font-semibold">Sarah Jenkins</h4>
          <p class="text-xs text-gray-500">2h ago • Public</p>
        </div>
      </div>

      <p class="mt-3 text-gray-700">
        Thrilled to announce our new campus recruitment initiative!
      </p>

      <img
        src="https://images.unsplash.com/photo-1562774053-701939374585"
        class="rounded-lg mt-4 w-full h-72 object-cover"
      />

      <div class="flex justify-between text-sm text-gray-500 mt-4">
        <span>124 likes</span>
        <span>18 comments</span>
      </div>

      <div class="flex justify-around mt-4 border-t pt-3 text-gray-600">
        <button class="hover:text-blue-600">Like</button>
        <button class="hover:text-blue-600">Comment</button>
        <button class="hover:text-blue-600">Share</button>
      </div>
    </div>
  </div>
</template>
