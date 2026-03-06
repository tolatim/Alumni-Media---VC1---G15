<template>
  <Navbar />

  <main class="bg-gray-100 min-h-screen py-6 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto grid grid-cols-12 gap-6">
      <section class="col-span-12 md:col-span-8 md:col-start-3 lg:col-span-6 lg:col-start-4 space-y-4">
        <div class="bg-white rounded-xl shadow p-5 sm:p-6">
          <div class="flex items-center justify-between mb-5">
            <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">Create Post</h1>
            <button
              @click="closeModal"
              class="text-sm text-gray-500 hover:text-gray-700 transition"
            >
              Close
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Title</label>
              <input
                v-model="title"
                type="text"
                maxlength="140"
                placeholder="Enter title..."
                class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <p class="text-xs text-gray-400 mt-1 text-right">{{ title.trim().length }}/140</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Content</label>
              <textarea
                v-model="content"
                rows="6"
                maxlength="2000"
                placeholder="What's on your mind?"
                class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-3 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              ></textarea>
              <div class="text-xs text-gray-400 mt-1 flex items-center justify-between">
                <span>{{ canPost ? 'Ready to post' : 'Add title or content' }}</span>
                <span>{{ content.trim().length }}/2000</span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Images</label>
              <input
                type="file"
                accept="image/*,video/*"
                multiple
                @change="onImagesChange"
                class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <p class="text-xs text-gray-400 mt-1">You can upload up to 10 images.</p>

              <div v-if="imagePreviews.length" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div
                  v-for="(preview, index) in imagePreviews"
                  :key="preview.url"
                  class="relative rounded-lg overflow-hidden border border-gray-200 bg-gray-50"
                >
                  <img
  v-if="preview.type.startsWith('image')"
  :src="preview.url"
  class="w-full h-24 object-cover"
/>

<video
  v-else
  :src="preview.url"
  class="w-full h-24 object-cover"
  controls
></video>
                  <button
                    type="button"
                    @click="removeImage(index)"
                    class="absolute top-1 right-1 text-xs bg-black/70 text-white px-2 py-0.5 rounded"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              @click="closeModal"
              class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition"
            >
              Cancel
            </button>

            <button
              @click="submitPost"
              :disabled="!canPost || isPosting"
              class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-60"
            >
              {{ isPosting ? 'Posting...' : 'Post' }}
            </button>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
          <p class="text-xs uppercase tracking-wide text-gray-400">Preview</p>
          <h2 class="font-semibold text-gray-800 mt-2">
            {{ title.trim() || 'Your title will appear here' }}
          </h2>
          <p class="text-gray-600 mt-2 whitespace-pre-line">
            {{ content.trim() || 'Your post content will appear here.' }}
          </p>
          <p class="text-xs text-gray-500 mt-2">
            {{ images.length }} image(s) selected
          </p>
        </div>
      </section>
    </div>
  </main>
</template>

<script>
import axios from 'axios';
import Navbar from '@/components/ui/nav.vue';
import { getPosts } from '@/services/authService';
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
    async fetchPosts() {
      try {
        const res = await getPosts();
        this.posts = res.data;
      } catch (error) {
        console.error(error);
      }
    },

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

        this.fetchPosts();
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

