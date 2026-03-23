<template>
  <Navbar />

  <main class="min-h-screen bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 px-4 sm:px-6">
    <div class="mx-auto max-w-7xl grid grid-cols-12 gap-6">
      <section class="col-span-12 md:col-span-10 md:col-start-2 lg:col-span-8 lg:col-start-3 space-y-5">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_10px_40px_rgba(15,23,42,0.08)]">
          <div class="border-b border-slate-100 bg-gradient-to-r from-cyan-50 via-white to-blue-50 px-5 py-5 sm:px-7">
            <div class="flex items-center justify-between">
              <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Create Post</h1>
              <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                Draft
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">Share an update with text, images, or video.</p>
          </div>

          <div class="p-5 sm:p-7">
            <div class="mb-5 flex items-center justify-end">
              <button
                @click="closeModal"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
              >
                Close
              </button>
            </div>

            <div class="space-y-5">
              <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Title</label>
                <input
                  v-model="title"
                  type="text"
                  maxlength="140"
                  placeholder="Write a strong title..."
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                />
                <p class="mt-1 text-right text-xs text-slate-400">{{ title.trim().length }}/140</p>
              </div>

              <div>
                <div class="mb-2 flex items-center justify-between">
                  <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Content</label>
                  <span class="text-xs text-slate-400">{{ content.trim().length }}/2000</span>
                </div>
                <textarea
                  v-model="content"
                  rows="7"
                  maxlength="2000"
                  placeholder="What are you building, learning, or sharing today?"
                  class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                ></textarea>
                <div class="mt-2 flex items-center justify-between text-xs">
                  <span class="font-medium text-slate-500">{{ canPost ? 'Ready to publish' : 'Add title, content, or media' }}</span>
                </div>
              </div>

              <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-4">
                <div class="mb-2 flex items-center justify-between">
                  <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Media</label>
                  <span class="text-xs font-medium text-slate-500">{{ images.length }}/10 selected</span>
                </div>
                <label
                  for="post-media-input"
                  class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-cyan-300 hover:bg-cyan-50/40"
                >
                  <span>Add images or videos</span>
                  <span class="rounded-md bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">Browse</span>
                </label>
                <input
                  id="post-media-input"
                  type="file"
                  accept="image/*,video/*"
                  multiple
                  @change="onImagesChange"
                  class="hidden"
                />
                <p class="mt-2 text-xs text-slate-400">You can upload up to 10 files.</p>
              </div>

              <div v-if="imagePreviews.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div
                  v-for="(preview, index) in imagePreviews"
                  :key="preview.url"
                  class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                  <img
                    v-if="preview.type.startsWith('image')"
                    :src="preview.url"
                    class="h-28 w-full object-cover sm:h-32"
                  />

                  <video
                    v-else
                    :src="preview.url"
                    class="h-28 w-full object-cover sm:h-32"
                    controls
                  ></video>
                  <button
                    type="button"
                    @click="removeImage(index)"
                    class="absolute right-2 top-2 rounded-md bg-black/75 px-2 py-1 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-7 flex justify-end gap-3">
              <button
                @click="closeModal"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
              >
                Cancel
              </button>

              <button
                @click="submitPost"
                :disabled="!canPost || isPosting"
                class="rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-cyan-700 hover:to-blue-700 disabled:opacity-60"
              >
                {{ isPosting ? 'Posting...' : 'Post Now' }}
              </button>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preview</p>
            <span class="text-xs font-medium text-slate-500">{{ images.length }} media</span>
          </div>
          <h2 class="mt-2 text-lg font-semibold text-slate-800">
            {{ title.trim() || 'Your title will appear here' }}
          </h2>
          <p class="mt-2 whitespace-pre-line text-slate-600">
            {{ content.trim() || 'Your post content will appear here.' }}
          </p>
        </div>
      </section>
    </div>
  </main>
</template>

<script>
import axios from 'axios';
import Navbar from '@/components/ui/nav.vue';
import { createPost } from '@/services/authService';

export default {
  components: {
    Navbar
  },

  data() {
    return {
      posts: [],
      title: '',
      content: '',
      images: [],
      imagePreviews: [],
      isPosting: false,
      token: localStorage.getItem('token')
    };
  },

  computed: {
    canPost() {
      return this.title.trim() !== '' || this.content.trim() !== '' || this.images.length > 0;
    }
  },

  methods: {
    async submitPost() {
      this.isPosting = true;

      try {
        const formData = new FormData();
        formData.append('title', this.title);
        formData.append('content', this.content);
        this.images.forEach((file) => {
          if (file.type.startsWith('image/')) {
    formData.append('images[]', file)

  } else if (file.type.startsWith('video/')) {
    formData.append('videos[]', file)

  }
        });

        const res = await createPost(formData, {
  headers: {
    Authorization: `Bearer ${this.token}`,
    Accept: 'application/json'
  }
});

        console.log('Post create:', res.data);
        this.$emit('post-created', res.data);

        this.title = '';
        this.content = '';
        this.clearImages();

      } catch (error) {
        console.error(error.response?.data || error);
      } finally {
        this.isPosting = false;
      }
    },

    onImagesChange(event) {
      const selectedFiles = Array.from(event.target.files || []);
      if (!selectedFiles.length) {
        return;
      }

      const remainingSlots = Math.max(0, 10 - this.images.length);
      const filesToAdd = selectedFiles.slice(0, remainingSlots);

      filesToAdd.forEach((file) => {
        this.images.push(file);
        this.imagePreviews.push({
  name: file.name,
  url: URL.createObjectURL(file),
  type: file.type
});
      });

      event.target.value = '';
    },

    removeImage(index) {
      const preview = this.imagePreviews[index];
      if (preview?.url) {
        URL.revokeObjectURL(preview.url);
      }
      this.images.splice(index, 1);
      this.imagePreviews.splice(index, 1);
    },

    clearImages() {
      this.imagePreviews.forEach((preview) => {
        if (preview?.url) {
          URL.revokeObjectURL(preview.url);
        }
      });
      this.images = [];
      this.imagePreviews = [];
    },

    async deletePost(id) {

      if (!confirm("Are you sure you want to delete this post?")) {
        return;
      }

      try {
        const response = await axios.delete(`http://127.0.00.1:8000/api/posts/${id}`,
        {
          headers: {
            Authorization: `Bearer ${this.token}`
          }
        });
        this.fetchPosts();
      } catch (error) {
        console.error(error);
      }
    },

    closeModal() {
      this.$emit('close');
      this.title = '';
      this.content = '';
      this.clearImages();
      this.$router.push('/');
    }
  }
};
</script>
