<template>
  <div
    class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 
           flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12 lg:py-20"
    @mousemove="handleMouse"
    @mouseleave="resetMouse"
  >
    <!-- Ambient Background (Desktop Only) -->
    <div
      class="hidden lg:block absolute w-[500px] h-[500px] 
             bg-indigo-200/30 rounded-full blur-[140px] transition-transform duration-500"
      :style="glow1Style"
    ></div>

    <div
      class="hidden lg:block absolute w-[400px] h-[400px] 
             bg-pink-200/30 rounded-full blur-[140px] transition-transform duration-500"
      :style="glow2Style"
    ></div>

    <!-- Main Card -->
    <div class="relative w-full max-w-3xl">

      <div class="bg-white/80 backdrop-blur-xl border border-gray-100
                  shadow-[0_20px_70px_rgba(0,0,0,0.05)]
                  rounded-3xl sm:rounded-[32px]
                  p-6 sm:p-8 lg:p-12
                  transition-all duration-300">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 mb-10">

          <div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl 
                       font-semibold tracking-tight text-gray-900">
              Create Post
            </h1>
            <p class="text-gray-500 mt-2 text-sm sm:text-base">
              Share your ideas professionally.
            </p>
          </div>

          <button
            class="self-start sm:self-auto
                   px-6 sm:px-8 py-2.5 rounded-full
                   text-sm sm:text-base font-medium text-white
                   bg-gradient-to-r from-indigo-500 to-purple-600
                   shadow-md hover:shadow-lg
                   transition hover:scale-105 active:scale-95"
          >
            Publish
          </button>
        </div>

        <!-- User Section -->
        <div class="flex items-center gap-4 sm:gap-5 mb-8">

          <div class="relative">
            <img
              src="https://via.placeholder.com/100"
              class="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover shadow"
            />
            <span
              class="absolute bottom-0 right-0 w-3.5 h-3.5 
                     bg-emerald-400 border-2 border-white rounded-full"
            ></span>
          </div>

          <div>
            <p class="font-semibold text-gray-900 text-base sm:text-lg">
              TOLA
            </p>
            <p class="text-xs sm:text-sm text-gray-500 uppercase tracking-wide">
              Public Audience
            </p>
          </div>

        </div>

        <!-- Textarea -->
        <div class="mb-8">

          <textarea
            placeholder="What are you thinking about?"
            rows="4"
            class="w-full text-lg sm:text-xl lg:text-2xl
                   text-gray-900 placeholder-gray-300
                   bg-transparent resize-none
                   focus:outline-none leading-relaxed"
          ></textarea>

          <div class="flex justify-between items-center mt-4 text-xs sm:text-sm text-gray-400">
            <div class="hidden sm:flex gap-6">
              <span class="hover:text-indigo-500 cursor-pointer transition">Ideas</span>
              <span class="hover:text-pink-500 cursor-pointer transition">Design</span>
              <span class="hover:text-purple-500 cursor-pointer transition">Projects</span>
            </div>
            <span>0 / 280</span>
          </div>

        </div>

        <!-- Media Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-8">

          <div class="group relative rounded-2xl overflow-hidden bg-gray-100 aspect-video">
            <img
              src="https://via.placeholder.com/600x400"
              class="w-full h-full object-cover transition duration-500 group-hover:scale-105"
            />
            <button
              class="absolute top-3 right-3
                     bg-white/90 backdrop-blur-md
                     px-4 py-1 text-xs sm:text-sm
                     rounded-full shadow
                     opacity-0 group-hover:opacity-100 transition"
            >
              Remove
            </button>
          </div>

          <div
            class="rounded-2xl border-2 border-dashed border-gray-200
                   flex items-center justify-center aspect-video
                   hover:border-indigo-400 hover:bg-indigo-50/40
                   transition cursor-pointer"
          >
            <div class="text-center">
              <div class="text-4xl text-gray-400 mb-3">＋</div>
              <p class="text-gray-600 text-sm sm:text-base font-medium">
                Add Media
              </p>
            </div>
          </div>

        </div>

        <!-- Bottom Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                    gap-4 pt-6 border-t border-gray-100">

          <div class="flex flex-wrap gap-4 sm:gap-6 text-xs sm:text-sm text-gray-500">
            <button class="hover:text-indigo-600 transition">Location</button>
            <button class="hover:text-indigo-600 transition">Tag</button>
            <button class="hover:text-indigo-600 transition">Schedule</button>
          </div>

          <button
            class="px-5 py-2 rounded-full border border-gray-200
                   text-gray-600 text-sm hover:bg-gray-50 transition"
          >
            Save Draft
          </button>

        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from "vue"

const glow1Style = reactive({ transform: "translate(0px,0px)" })
const glow2Style = reactive({ transform: "translate(0px,0px)" })

function handleMouse(e) {
  if (window.innerWidth < 1024) return

  const x = e.clientX / window.innerWidth - 0.5
  const y = e.clientY / window.innerHeight - 0.5

  glow1Style.transform = `translate(${x * 60}px, ${y * 60}px)`
  glow2Style.transform = `translate(${x * -50}px, ${y * -50}px)`
}

function resetMouse() {
  glow1Style.transform = "translate(0px,0px)"
  glow2Style.transform = "translate(0px,0px)"
}
</script>