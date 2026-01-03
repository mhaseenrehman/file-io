<template>
    <button :class="downloadableLink === 'Pending' ? 'bg-stone-500' : 'bg-green-500'">{{ downloadableLink === 'Pending' ? 'Pending' : 'Download' }}</button>
</template>

<script setup>
    import { VueElement } from 'vue';

    const props = defineProps({
        downloadableLink: {
            type: Object,
            required: true
        }
    });

</script>

<style scoped></style>