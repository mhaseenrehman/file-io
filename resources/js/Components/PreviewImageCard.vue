<template>
    <div v-if="chosenImage.imageUrl" class="rounded border-2 border-solid max-w-sum w-full lg:max-w-full lg:flex flex dark:text-gray-400" id="preview-image-card">
        <img class="h-48 lg:h-auto lg:w-48 flex-none bg-cover rounded-t lg:rounded-t-none lg:rounded-l text-center overflow-hidden" :src="chosenImage.imageUrl" alt="" srcset="">
    </div>
</template>

<script setup>
import { VueElement } from 'vue';

    const props = defineProps({
        chosenImage: {
            type: Object,
            required: true
        }
    });
</script>

<style scoped>
</style>