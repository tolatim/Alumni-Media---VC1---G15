<script setup lang="ts">
type AnyUser = Record<string, any>

const props = defineProps<{
  user: AnyUser
  status?: string
}>()

const getUserName = () => {
  const user = props.user || {}
  const nameFromFields = `${user.first_name || ''} ${user.last_name || ''}`.trim()
  return user.name || nameFromFields || user.email || `User #${user.id ?? ''}`
}
</script>

<template>
  <tr class="border-b border-slate-200 text-sm text-slate-700">
    <td class="px-4 py-3 font-semibold text-slate-900">
      {{ getUserName() }}
    </td>
    <td class="px-4 py-3">
      {{ user.email || '—' }}
    </td>
    <td class="px-4 py-3 text-xs font-semibold text-slate-500">
      {{ status || (user.is_suspended ? 'Suspended' : 'Active') }}
    </td>
    <td class="px-4 py-3">
      <slot />
    </td>
  </tr>
</template>
