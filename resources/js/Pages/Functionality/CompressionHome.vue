<template lang="">
    <div class="flex flex-row min-h-screen justify-center items-center dark:bg-gray-800">
        <form>
            <CompressionInputs :chosenImage="data.chosenImage"/>
            <div class="p-2" @drop.prevent="handleDrop">
                <label for="imagesInput" class="drop-zone-label dark:text-gray-400 border-2 border-dashed p-8" >
                    Drag and Drop images here or Click to select from Directory
                </label>
                <!-- <input type="file" multiple name="imagesInput" id="imagesInput" accept="image/*" hidden> -->
                <input type="file" name="imagesInput" id="imagesInput" accept="image/*" hidden @change="handleFileChange">
            </div>
        </form>
    </div>
</template>

<script setup>
    import { reactive } from 'vue';
    import CompressionInputs from '@/Components/CompressionInputs.vue';

    const initial_image_state = {
        fileSelected: null,
        imageUrl: null,
        format: "webp",
        quality: 50,
        width: null
    }

    const data = reactive({
        chosenImage: {...initial_image_state},
        fileInfo: null
    });

    const handleDrop = (event) => {
        console.log(event.dataTransfer.files[0]);
    }

    const handleFileChange = (event) => {
        console.log(event.target.files[0]);
    }
</script>

<!-- <style scoped></style> -->