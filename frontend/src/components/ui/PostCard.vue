<template>
  <article
    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
  >
    <div class="border-b border-slate-100 px-5 pt-5 pb-4">
      <div class="flex items-start justify-between gap-3">
        <RouterLink
          v-if="authorId"
          :to="`/profile/${authorId}`"
          class="flex items-center gap-3 rounded-lg transition hover:bg-slate-50"
        >
          <img :src="post.user?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-full border border-slate-200 object-cover">
          <div>
            <h4 class="font-semibold text-slate-900">{{ post.user?.name || 'Unknown user' }}</h4>
            <p class="text-xs text-slate-500">{{ formatDate(post.created_at) }}</p>
          </div>
        </RouterLink>
        <div v-else class="flex items-center gap-3">
          <img :src="post.user?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-full border border-slate-200 object-cover">
          <div>
            <h4 class="font-semibold text-slate-900">{{ post.user?.name || 'Unknown user' }}</h4>
            <p class="text-xs text-slate-500">{{ formatDate(post.created_at) }}</p>
          </div>
        </div>

        <div v-if="canDeletePost" class="relative">
          <button
            type="button"
            @click.stop="togglePostActionsMenu(post.id)"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 focus-visible:ring-offset-1"
            aria-label="Post actions"
            title="Post actions"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-4 w-4"
              fill="currentColor"
              aria-hidden="true"
            >
              <circle cx="12" cy="5" r="2" />
              <circle cx="12" cy="12" r="2" />
              <circle cx="12" cy="19" r="2" />
            </svg>
          </button>

          <div
            v-if="openPostActionsMenu"
            class="absolute right-0 z-20 mt-2 w-36 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
          >
            <button
              type="button"
              @click="handleStartEdit"
              class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
              </svg>
              <span>Edit</span>
            </button>
            <button
              type="button"
              @click="handleDeletePost"
              class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
            >
              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 6h18" />
                <path d="M8 6V4h8v2" />
                <path d="M19 6l-1 14H6L5 6" />
                <path d="M10 11v6" />
                <path d="M14 11v6" />
              </svg>
              <span>Delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-4 px-5 py-4">
      <template v-if="isEditing">
        <input
          v-model="editTitle"
          type="text"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
          placeholder="Post title"
        >
        <textarea
          v-model="editContent"
          rows="4"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
          placeholder="Post content"
        ></textarea>
        <label class="block">
          <span class="mb-1 block text-xs font-semibold text-slate-600">Update media (images/videos)</span>
          <input
            type="file"
            multiple
            accept="image/*,video/*"
            @change="onEditMediaSelected"
            class="block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700 file:mr-2 file:rounded-md file:border-0 file:bg-cyan-50 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-cyan-700 hover:file:bg-cyan-100"
          >
        </label>
        <p v-if="editMediaFiles.length" class="text-xs text-slate-500">
          Selected: {{ editImageCount }} image(s), {{ editVideoCount }} video(s)
        </p>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="cancelEdit"
            :disabled="isSavingEdit"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveEdit"
            :disabled="isSavingEdit"
            class="inline-flex items-center rounded-lg border border-cyan-200 bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
          >
            {{ isSavingEdit ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </template>
      <template v-else>
        <h4 v-if="post.title" class="text-lg font-semibold text-slate-900 break-words">
          {{ post.title }}
        </h4>
        <p
          v-if="post.content"
          class="whitespace-pre-line break-words text-[15px] leading-relaxed text-slate-700"
        >
          {{ post.content }}
        </p>
      </template>

      <div v-if="post.media?.length" class="space-y-2">
        <template v-if="shouldCondenseMedia && !isMediaExpanded">
          <div
            v-if="post.media[0]"
            class="overflow-hidden rounded-xl border border-slate-200"
          >
            <img
              v-if="isImageMedia(post.media[0])"
              :src="getMediaSrc(post.media[0])"
              alt="Post image"
              class="h-full max-h-80 w-full object-cover"
            >
            <video
              v-else-if="isVideoMedia(post.media[0])"
              :src="getMediaSrc(post.media[0])"
              class="h-full max-h-80 w-full bg-black object-cover"
              controls
              preload="metadata"
            ></video>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="(media, index) in post.media.slice(1, 4)"
              :key="media.id ?? media.file_path ?? media.media_url"
              type="button"
              @click="isMediaExpanded = true"
              class="relative overflow-hidden rounded-xl border border-slate-200 text-left"
            >
              <img
                v-if="isImageMedia(media)"
                :src="getMediaSrc(media)"
                alt="Post image"
                class="h-32 w-full object-cover"
              >
              <video
                v-else-if="isVideoMedia(media)"
                :src="getMediaSrc(media)"
                class="h-32 w-full bg-black object-cover"
                preload="metadata"
              ></video>

              <div
                v-if="index === 2 && hiddenMediaCount > 0"
                class="absolute inset-0 flex items-center justify-center bg-black/55 text-xl font-semibold text-white"
              >
                +{{ hiddenMediaCount }}
              </div>
            </button>
          </div>
        </template>

        <template v-else>
          <div
            :class="[
              'grid gap-2 overflow-hidden rounded-xl',
              post.media.length === 1 ? 'grid-cols-1' : 'grid-cols-2'
            ]"
          >
            <template
              v-for="media in post.media"
              :key="media.id ?? media.file_path ?? media.media_url"
            >
              <img
                v-if="isImageMedia(media)"
                :src="getMediaSrc(media)"
                alt="Post image"
                class="h-full max-h-80 w-full rounded-lg border border-slate-200 object-cover"
              >
              <video
                v-else-if="isVideoMedia(media)"
                :src="getMediaSrc(media)"
                class="h-full max-h-80 w-full rounded-lg border border-slate-200 bg-black object-cover"
                controls
                preload="metadata"
              ></video>
            </template>
          </div>

          <div v-if="shouldCondenseMedia" class="flex justify-end">
            <button
              type="button"
              @click="isMediaExpanded = false"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Show less media
            </button>
          </div>
        </template>
      </div>
    </div>

    <div class="space-y-3 border-t border-slate-100 px-5 py-3">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <button
            type="button"
            :disabled="likeSubmitting"
            @click="toggleLike"
            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:opacity-60"
            :class="isPostLiked ? 'border-pink-200 bg-pink-50 text-pink-700 hover:bg-pink-100 focus-visible:ring-pink-300' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-300'"
            :aria-label="isPostLiked ? 'Unlike post' : 'Like post'"
            :title="isPostLiked ? 'Unlike' : 'Like'"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-4 w-4"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
              />
            </svg>
            <span>{{ likesCount }}</span>
          </button>

          <button
            type="button"
            :disabled="commentsLoading"
            @click="toggleComments"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-1 disabled:opacity-60"
            :aria-label="commentsOpen ? 'Hide comments' : 'Show comments'"
            :title="commentsOpen ? 'Hide comments' : 'Show comments'"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              aria-hidden="true"
            >
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
            <span>{{ commentsCount }}</span>
          </button>
        </div>

      </div>
      <p class="text-xs font-medium text-slate-500">
        {{ post.media?.length ? `${post.media.length} media` : 'Text post' }}
      </p>
    </div>

    <div v-if="commentsOpen" class="border-t border-slate-100 bg-slate-50/50 px-5 py-4">
      <form class="mb-3 flex items-start gap-2" @submit.prevent="submitComment">
        <textarea
          v-model="commentDraft"
          rows="2"
          placeholder="Write a comment..."
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
        ></textarea>
        <button
          type="submit"
          :disabled="commentSubmitting"
          class="inline-flex items-center rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
        >
          {{ commentSubmitting ? 'Posting...' : 'Post' }}
        </button>
      </form>

      <p v-if="commentsLoading" class="text-xs text-slate-500">Loading comments...</p>

      <div v-else-if="!comments.length" class="text-xs text-slate-500">
        No comments yet.
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="comment in comments"
          :key="comment.id"
          class="rounded-lg border border-slate-200 bg-white p-3"
        >
          <div class="flex items-start gap-2.5">
            <img
              :src="comment.user?.profile?.avatar || fallbackAvatar"
              alt="Comment user avatar"
              class="h-8 w-8 shrink-0 rounded-full border border-slate-200 object-cover"
            >
            <div class="min-w-0 flex-1">
              <div class="mb-1 flex items-center justify-between gap-2">
                <p class="text-xs font-semibold text-slate-700">{{ comment.user?.name || 'Unknown user' }}</p>
                <div v-if="canDeleteComment(comment)" class="relative">
                  <button
                    type="button"
                    @click.stop="toggleCommentActionsMenu(comment.id)"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                    aria-label="Comment actions"
                    title="Comment actions"
                  >
                    <svg
                      viewBox="0 0 24 24"
                      class="h-3.5 w-3.5"
                      fill="currentColor"
                      aria-hidden="true"
                    >
                      <circle cx="12" cy="5" r="2" />
                      <circle cx="12" cy="12" r="2" />
                      <circle cx="12" cy="19" r="2" />
                    </svg>
                  </button>

                  <div
                    v-if="openCommentActionsMenuId === comment.id"
                    class="absolute right-0 z-20 mt-1 w-32 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                  >
                    <button
                      type="button"
                      @click="startCommentEdit(comment)"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                      <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 20h9" />
                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                      </svg>
                      <span>Edit</span>
                    </button>
                    <button
                      type="button"
                      @click="deleteComment(comment.id)"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                    >
                      <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M3 6h18" />
                        <path d="M8 6V4h8v2" />
                        <path d="M19 6l-1 14H6L5 6" />
                        <path d="M10 11v6" />
                        <path d="M14 11v6" />
                      </svg>
                      <span>Delete</span>
                    </button>
                  </div>
                </div>
              </div>
              <template v-if="editingCommentId === comment.id">
                <textarea
                  v-model="editingCommentContent"
                  rows="2"
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                ></textarea>
                <div class="mt-2 flex justify-end gap-2">
                  <button
                    type="button"
                    @click="cancelCommentEdit"
                    :disabled="commentUpdating"
                    class="inline-flex items-center rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    @click="saveCommentEdit(comment)"
                    :disabled="commentUpdating"
                    class="inline-flex items-center rounded-md border border-cyan-200 bg-cyan-600 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
                  >
                    {{ commentUpdating ? 'Saving...' : 'Save' }}
                  </button>
                </div>
              </template>
              <p v-else class="whitespace-pre-line break-words text-sm text-slate-700">{{ comment.content }}</p>

              <div class="mt-2">
                <button
                  type="button"
                  @click="toggleReplyInput(comment.id)"
                  class="text-[11px] font-semibold text-cyan-700 hover:text-cyan-800"
                >
                  Reply
                </button>
              </div>

              <form
                v-if="isReplyInputOpen(comment.id)"
                class="mt-2 flex items-start gap-2"
                @submit.prevent="submitReply(comment)"
              >
                <textarea
                  v-model="replyDraftByCommentId[comment.id]"
                  rows="2"
                  placeholder="Write a reply..."
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                ></textarea>
                <button
                  type="submit"
                  :disabled="isReplySubmitting(comment.id)"
                  class="inline-flex items-center rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
                >
                  {{ isReplySubmitting(comment.id) ? 'Replying...' : 'Reply' }}
                </button>
              </form>

              <div v-if="comment.replies?.length" class="mt-3 space-y-2 border-l border-slate-200 pl-3">
                <div
                  v-for="reply in comment.replies"
                  :key="reply.id"
                  class="rounded-lg border border-slate-200 bg-slate-50 p-2.5"
                >
                  <div class="flex items-start gap-2.5">
                    <img
                      :src="reply.user?.profile?.avatar || fallbackAvatar"
                      alt="Reply user avatar"
                      class="mt-0.5 h-7 w-7 shrink-0 rounded-full border border-slate-200 object-cover"
                    >
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700">{{ reply.user?.name || 'Unknown user' }}</p>
                        <div v-if="canDeleteComment(reply)" class="relative">
                          <button
                            type="button"
                            @click.stop="toggleCommentActionsMenu(reply.id)"
                            class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                            aria-label="Reply actions"
                            title="Reply actions"
                          >
                            <svg
                              viewBox="0 0 24 24"
                              class="h-3.5 w-3.5"
                              fill="currentColor"
                              aria-hidden="true"
                            >
                              <circle cx="12" cy="5" r="2" />
                              <circle cx="12" cy="12" r="2" />
                              <circle cx="12" cy="19" r="2" />
                            </svg>
                          </button>

                          <div
                            v-if="openCommentActionsMenuId === reply.id"
                            class="absolute right-0 z-20 mt-1 w-32 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                          >
                            <button
                              type="button"
                              @click="startCommentEdit(reply)"
                              class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                              </svg>
                              <span>Edit</span>
                            </button>
                            <button
                              type="button"
                              @click="deleteComment(reply.id)"
                              class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                            >
                              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 6h18" />
                                <path d="M8 6V4h8v2" />
                                <path d="M19 6l-1 14H6L5 6" />
                                <path d="M10 11v6" />
                                <path d="M14 11v6" />
                              </svg>
                              <span>Delete</span>
                            </button>
                          </div>
                        </div>
                      </div>

                      <template v-if="editingCommentId === reply.id">
                        <textarea
                          v-model="editingCommentContent"
                          rows="2"
                          class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                        ></textarea>
                        <div class="mt-2 flex justify-end gap-2">
                          <button
                            type="button"
                            @click="cancelCommentEdit"
                            :disabled="commentUpdating"
                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
                          >
                            Cancel
                          </button>
                          <button
                            type="button"
                            @click="saveCommentEdit(reply)"
                            :disabled="commentUpdating"
                            class="inline-flex items-center rounded-md border border-cyan-200 bg-cyan-600 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
                          >
                            {{ commentUpdating ? 'Saving...' : 'Save' }}
                          </button>
                        </div>
                      </template>
                      <p v-else class="mt-1 whitespace-pre-line break-words text-sm text-slate-700">{{ reply.content }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
  currentUser: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['deleted', 'refresh-posts'])

const isEditing = ref(false)
const editTitle = ref('')
const editContent = ref('')
const editMediaFiles = ref([])
const isSavingEdit = ref(false)
const openPostActionsMenu = ref(false)

const likeSubmitting = ref(false)
const likedByMeOverride = ref(null)
const likesCountOverride = ref(null)

const commentsOpen = ref(false)
const commentsLoading = ref(false)
const comments = ref([])
const commentsCountOverride = ref(null)
const commentDraft = ref('')
const commentSubmitting = ref(false)
const replyDraftByCommentId = ref({})
const replyOpenByCommentId = ref({})
const replySubmittingByCommentId = ref({})

const openCommentActionsMenuId = ref(null)
const editingCommentId = ref(null)
const editingCommentContent = ref('')
const commentUpdating = ref(false)

const isMediaExpanded = ref(false)

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback
const toSafeCount = (value) => Number(value) || 0
const normalizeComment = (comment) => ({
  ...comment,
  replies: Array.isArray(comment?.replies) ? comment.replies : [],
})

const editImageCount = computed(() => editMediaFiles.value.filter((file) => file.type.startsWith('image/')).length)
const editVideoCount = computed(() => editMediaFiles.value.filter((file) => file.type.startsWith('video/')).length)
const authorId = computed(() => props.post?.user?.id ?? props.post?.user_id ?? null)
const shouldCondenseMedia = computed(() => Array.isArray(props.post?.media) && props.post.media.length > 4)
const hiddenMediaCount = computed(() => Math.max((props.post?.media?.length || 0) - 4, 0))

const canDeletePost = computed(() => {
  const currentUserId = Number(props.currentUser?.id)
  const ownerId = Number(props.post?.user_id ?? props.post?.user?.id)
  return Number.isFinite(currentUserId) && Number.isFinite(ownerId) && currentUserId === ownerId
})

const isPostLiked = computed(() => {
  if (likedByMeOverride.value !== null) return Boolean(likedByMeOverride.value)
  return Boolean(props.post?.liked_by_me)
})

const likesCount = computed(() => {
  if (likesCountOverride.value !== null) return toSafeCount(likesCountOverride.value)
  return toSafeCount(props.post?.likes_count)
})

const commentsCount = computed(() => {
  if (commentsCountOverride.value !== null) return toSafeCount(commentsCountOverride.value)
  return toSafeCount(props.post?.comments_count)
})

const formatDate = (value) => {
  if (!value) return 'Unknown time'
  return new Date(value).toLocaleString()
}

const getMediaSrc = (media) => media?.media_url || media?.file_path || ''

const getMediaType = (media) => {
  const explicitType = String(media?.type || '').toLowerCase()
  if (explicitType === 'image' || explicitType === 'video') return explicitType

  const src = getMediaSrc(media).toLowerCase()
  if (/\.(mp4|mov|avi|webm|mkv)(\?|#|$)/.test(src)) return 'video'
  if (/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?|#|$)/.test(src)) return 'image'
  return ''
}

const isImageMedia = (media) => getMediaType(media) === 'image'
const isVideoMedia = (media) => getMediaType(media) === 'video'

const togglePostActionsMenu = () => {
  openPostActionsMenu.value = !openPostActionsMenu.value
}

const handleStartEdit = () => {
  openPostActionsMenu.value = false
  isEditing.value = true
  editTitle.value = props.post?.title || ''
  editContent.value = props.post?.content || ''
  editMediaFiles.value = []
}

const cancelEdit = () => {
  isEditing.value = false
  editTitle.value = ''
  editContent.value = ''
  editMediaFiles.value = []
}

const onEditMediaSelected = (event) => {
  editMediaFiles.value = Array.from(event?.target?.files || [])
}

const saveEdit = async () => {
  const titleValue = editTitle.value.trim()
  const contentValue = editContent.value.trim()
  const editImageFiles = editMediaFiles.value.filter((file) => file.type.startsWith('image/'))
  const editVideoFiles = editMediaFiles.value.filter((file) => file.type.startsWith('video/'))
  const hasSelectedMedia = editMediaFiles.value.length > 0
  const hasExistingMedia = Array.isArray(props.post?.media) && props.post.media.length > 0

  if (!titleValue && !contentValue && !hasSelectedMedia && !hasExistingMedia) {
    alert('Please add title, content, or at least one image/video.')
    return
  }

  isSavingEdit.value = true
  try {
    const formData = new FormData()
    formData.append('title', titleValue)
    formData.append('content', contentValue)
    editImageFiles.forEach((file) => formData.append('images[]', file))
    editVideoFiles.forEach((file) => formData.append('videos[]', file))

    await api.post(`/posts/${props.post.id}`, formData)
    cancelEdit()
    emit('refresh-posts')
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to update post.'))
  } finally {
    isSavingEdit.value = false
  }
}

const handleDeletePost = async () => {
  openPostActionsMenu.value = false
  if (!confirm('Are you sure you want to delete this post?')) return

  try {
    await api.delete(`/posts/${props.post.id}`)
    emit('deleted', props.post.id)
    emit('refresh-posts')
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to delete post.'))
  }
}

const toggleLike = async () => {
  likeSubmitting.value = true
  try {
    const response = await api.post(`/posts/${props.post.id}/like`)
    likedByMeOverride.value = Boolean(response.data?.liked)
    likesCountOverride.value = toSafeCount(response.data?.likes_count)
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to toggle like.'))
  } finally {
    likeSubmitting.value = false
  }
}

const setCommentsCount = (count) => {
  commentsCountOverride.value = toSafeCount(count)
}

const loadComments = async () => {
  commentsLoading.value = true
  try {
    const response = await api.get(`/posts/${props.post.id}/comments`)
    comments.value = (response.data?.data || []).map(normalizeComment)
    setCommentsCount(response.data?.comments_count ?? comments.value.length)
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to load comments.'))
  } finally {
    commentsLoading.value = false
  }
}

const toggleComments = async () => {
  commentsOpen.value = !commentsOpen.value
  if (!commentsOpen.value) return
  if (Array.isArray(comments.value) && comments.value.length) return
  await loadComments()
}

const submitComment = async () => {
  const content = String(commentDraft.value || '').trim()
  if (!content) return

  commentSubmitting.value = true
  try {
    const response = await api.post(`/posts/${props.post.id}/comments`, { content })
    const createdComment = response.data?.comment
    if (createdComment) {
      comments.value = [normalizeComment(createdComment), ...comments.value]
    }
    commentDraft.value = ''
    setCommentsCount(response.data?.comments_count ?? comments.value.length)
    commentsOpen.value = true
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to add comment.'))
  } finally {
    commentSubmitting.value = false
  }
}

const isReplyInputOpen = (commentId) => Boolean(replyOpenByCommentId.value[commentId])

const toggleReplyInput = (commentId) => {
  replyOpenByCommentId.value[commentId] = !replyOpenByCommentId.value[commentId]
}

const isReplySubmitting = (commentId) => Boolean(replySubmittingByCommentId.value[commentId])

const submitReply = async (comment) => {
  const commentId = comment.id
  const content = String(replyDraftByCommentId.value[commentId] || '').trim()
  if (!content) return

  replySubmittingByCommentId.value[commentId] = true
  try {
    const response = await api.post(`/posts/${props.post.id}/comments`, {
      content,
      parent_id: commentId,
    })
    const createdReply = response.data?.comment
    if (createdReply) {
      if (!Array.isArray(comment.replies)) {
        comment.replies = []
      }
      comment.replies = [...comment.replies, normalizeComment(createdReply)]
    }
    replyDraftByCommentId.value[commentId] = ''
    replyOpenByCommentId.value[commentId] = false
    setCommentsCount(response.data?.comments_count ?? comments.value.length)
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to reply to comment.'))
  } finally {
    replySubmittingByCommentId.value[commentId] = false
  }
}

const canDeleteComment = (comment) => {
  const currentUserId = Number(props.currentUser?.id)
  return Number.isFinite(currentUserId) && currentUserId === Number(comment?.user_id)
}

const findCommentLocation = (commentId) => {
  for (let i = 0; i < comments.value.length; i += 1) {
    const parent = comments.value[i]
    if (parent.id === commentId) {
      return { kind: 'parent', parentIndex: i }
    }

    const replies = Array.isArray(parent.replies) ? parent.replies : []
    const replyIndex = replies.findIndex((reply) => reply.id === commentId)
    if (replyIndex !== -1) {
      return { kind: 'reply', parentIndex: i, replyIndex }
    }
  }

  return null
}

const removeCommentById = (commentId) => {
  const location = findCommentLocation(commentId)
  if (!location) return

  if (location.kind === 'parent') {
    comments.value = comments.value.filter((item) => item.id !== commentId)
    return
  }

  const parent = comments.value[location.parentIndex]
  if (!parent || !Array.isArray(parent.replies)) return
  parent.replies = parent.replies.filter((item) => item.id !== commentId)
}

const replaceCommentById = (commentId, updatedComment) => {
  const location = findCommentLocation(commentId)
  if (!location) return

  if (location.kind === 'parent') {
    comments.value[location.parentIndex] = normalizeComment({
      ...comments.value[location.parentIndex],
      ...updatedComment,
    })
    return
  }

  const parent = comments.value[location.parentIndex]
  if (!parent || !Array.isArray(parent.replies)) return
  parent.replies[location.replyIndex] = {
    ...parent.replies[location.replyIndex],
    ...updatedComment,
  }
}

const toggleCommentActionsMenu = (commentId) => {
  openCommentActionsMenuId.value = openCommentActionsMenuId.value === commentId ? null : commentId
}

const deleteComment = async (commentId) => {
  openCommentActionsMenuId.value = null
  try {
    const response = await api.delete(`/comments/${commentId}`)
    removeCommentById(commentId)
    setCommentsCount(response.data?.comments_count ?? comments.value.length)
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to delete comment.'))
  }
}

const startCommentEdit = (comment) => {
  openCommentActionsMenuId.value = null
  editingCommentId.value = comment.id
  editingCommentContent.value = comment.content || ''
}

const cancelCommentEdit = () => {
  editingCommentId.value = null
  editingCommentContent.value = ''
}

const saveCommentEdit = async (comment) => {
  const content = editingCommentContent.value.trim()
  if (!content) {
    alert('Comment content is required.')
    return
  }

  commentUpdating.value = true
  try {
    const response = await api.put(`/comments/${comment.id}`, { content })
    const updatedComment = response.data?.comment
    replaceCommentById(comment.id, updatedComment || { content })
    cancelCommentEdit()
  } catch (error) {
    console.error(error.response?.data || error)
    alert(getApiMessage(error, 'Failed to update comment.'))
  } finally {
    commentUpdating.value = false
  }
}

const closeMenus = () => {
  openPostActionsMenu.value = false
  openCommentActionsMenuId.value = null
}

onMounted(() => {
  window.addEventListener('click', closeMenus)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeMenus)
})
</script>
