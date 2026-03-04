<template>
  <div class="bg-white rounded-lg shadow-lg max-w-xl mx-auto">

    <!-- Header -->
    <div class="flex justify-between px-5 py-4 border-b">
      <h2 class="text-xl font-semibold">Create Post</h2>
      <button @click="closeModal">✕</button>
    </div>

    <!-- Body -->
    <div class="px-5 py-4 space-y-3">

      <!-- Title -->
      <input
        v-model="title"
        type="text"
        placeholder="Enter title..."
        class="w-full p-2 border rounded"
      />

      <!-- Content -->
      <textarea
        v-model="content"
        placeholder="What's on your mind?"
        rows="4"
        class="w-full p-3 border rounded"
      ></textarea>

    </div>

    <!-- Footer -->
    <div class="flex justify-end gap-3 px-5 py-4 border-t">
      <button @click="closeModal"
        class="bg-red-600 text-white px-4 py-2 rounded" >Cancel</button>

      <button
        @click="submitPost"
        :disabled="!canPost || isPosting"
        class="bg-blue-600 text-white px-4 py-2 rounded"
      >
        {{ isPosting ? 'Posting...' : 'Post' }}
      </button>
    </div>

  </div>
</template>

<script>
import axios from 'axios'

export default {

  data() {
    return {
      posts: [],
      title: '',
      content: '',
      isPosting: false
    };
  },

  computed:{
    canPost(){
      return this.title.trim()!== '' || this.content.trim()!=='';
    }
  },

 mounted() {
  const token = localStorage.getItem('token');
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
},
  methods: {
    async fetchPosts(){
      try {
        const res = await axios.get('http://127.0.0.1:8000/api/posts');
        this.posts = res.data;
      } catch (error) {
        console.error(error);
        errorMessage.value = 'Failed to load posts'
      }
    },
    async submitPost() {
      try {
      const res = axios.post('http://127.0.0.1:8000/api/posts',{
        title: this.title,
        content:this.content
      }, 
    );
      
      console.log ('Post create:', res.data);

      this.$emit('post-created', res.data);

      this.title = '';
      this.content = '';

      this.fetchPosts();
    } catch (error){
      console.error(error.response?.data || error);
    } finally {
      this.isPosting = false;
    }
    },

    async deletePost(id) {
      try {
        await axios.delete(`http://127.0.0.1:8000/api/posts/${id}`);
        this.fetchPosts();
      } catch (error) {
        console.error(error);
      }
    },
    closeModal(){
      this.$emit('close');
      this.title = '';
      this.content = '' ;
    }
  },
};
</script>