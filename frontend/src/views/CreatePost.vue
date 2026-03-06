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
        </div>
      </section>
    </div>
  </main>
</template>

<script>
import axios from 'axios';
import Navbar from '@/components/ui/nav.vue';

export default {
  components: {
    Navbar
  },

  data() {
    return {
      posts: [],
      title: '',
      content: '',
      isPosting: false,
      token: localStorage.getItem('token')
    };
  },

  computed: {
    canPost() {
      return this.title.trim() !== '' || this.content.trim() !== '';
    }
  },

  methods: {
    async fetchPosts() {
      try {
        const res = await axios.get('http://127.0.0.1:8000/api/posts');
        this.posts = res.data;
      } catch (error) {
        console.error(error);
      }
    },

    async submitPost() {
      this.isPosting = true;

      try {
        const res = await axios.post(
          'http://127.0.0.1:8000/api/posts',
          {
            title: this.title,
            content: this.content
          },
          {
            headers: {
              Authorization: `Bearer ${this.token}`
            }
          }
        );

        console.log('Post create:', res.data);
        this.$emit('post-created', res.data);

        this.title = '';
        this.content = '';

        this.fetchPosts();
      } catch (error) {
        console.error(error.response?.data || error);
      } finally {
        this.isPosting = false;
      }
    },

    async deletePost(id) {

      if (!confirm("Are you sure you want to delete this post?")) {
        return;
      }

      try {
        const response = await axios.delete(`http://127.0.0.1:8000/api/posts/${id}`,
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
      this.$router.push('/');
    }
  }
};
</script>

