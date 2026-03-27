<template>
  <div class="min-h-screen bg-slate-50">
    <router-view />
    <ToastContainer />
  </div>
</template>

<script setup>
import ToastContainer from '@/components/common/ToastContainer.vue'
</script>
