<template>
    <a class="flex mx-8 my-4 size-auto text-white font-bold text-blue-100 no-underline rounded-full text-center items-center justify-center" :class="downloadableLink === 'Waiting' ? 'bg-stone-500' : downloadableLink === 'Pending' ? 'bg-stone-500' : 'bg-green-500'" :href="downloadableLink" :download="filename">
        <!-- <button class="text-white font-bold py-2 px-4 rounded-full" :class="downloadableLink === 'Pending' || 'Waiting' ? 'bg-stone-500' : 'bg-green-500'">{{ downloadableLink === 'Waiting' ? 'Waiting' : downloadableLink === 'Pending' ? 'Pending' : 'Download' }}

        </button> -->
        {{ downloadableLink === 'Waiting' ? 'Waiting' : downloadableLink === 'Pending' ? 'Pending' : 'Download' }}
    </a>
</template>

<script setup>
    import { VueElement } from 'vue';

    const props = defineProps({
        downloadableLink: {
            type: Object,
            required: true
        },
        filename: {
            type: Object,
            required: false
        }
    });

</script>

<style scoped></style>