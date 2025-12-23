<template lang="">
    <div class="flex flex-row min-h-screen justify-center items-center dark:bg-gray-800">
        <form @submit.prevent="compressImage">
            <CompressionInputs :chosenImage="data.chosenImage"/>
            <div class="p-2 pb-12" @drop.prevent="handleDrop" @dragover.prevent>
                <label for="imagesInput" class="drop-zone-label dark:text-gray-400 border-2 border-dashed p-8" >
                    Drag and Drop images here or Click to select from Directory
                </label>
                <!-- <input type="file" multiple name="imagesInput" id="imagesInput" accept="image/*" hidden> -->
                <input type="file" name="imagesInput" id="imagesInput" accept="image/*" hidden @change="handleFileChange">
            </div>
            <PreviewImageCard :chosenImage="data.chosenImage">
            </PreviewImageCard>
            <button type="submit" id="downloadCompressedButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full">Download Compressed</button>
        </form>
    </div>
</template>

<script setup>
    import CompressionInputs from '@/Components/CompressionInputs.vue';
    import PreviewImageCard from '@/Components/PreviewImageCard.vue';
    
    import { reactive } from 'vue';
    import axios from 'axios';
    import { useToast } from 'vue-toastification';

    const initial_image_state = {
        fileSelected: null,
        imageUrl: null,
        format: "webp",
        quality: 50,
        width: null
    }

    const toast = useToast();

    const data = reactive({
        chosenImage: {...initial_image_state},
        fileInfo: null
    });

    const handleDrop = (event) => {
        displayPreviewImage(event.dataTransfer.files[0]);
    }

    const handleFileChange = (event) => {
        displayPreviewImage(event.target.files[0]);
    }

    const displayPreviewImage = (file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            data.chosenImage = {
                ...data.chosenImage,
                fileSelected: file,
                imageUrl: e.target.result,
            }
        }
        reader.readAsDataURL(file);
    }

    const compressImage = async () => {
        const apiClient = axios.create({
            baseURL: '/api/imageCompress',
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const formData = new FormData();
        formData.append('image', data.chosenImage.fileSelected);
        formData.append('quality', data.chosenImage.quality);
        formData.append('width', data.chosenImage.width);
        formData.append('format', data.chosenImage.format);

        console.log(formData);

        const response = await apiClient.post('', formData)
                                .then(response => {
                                    console.log(response.data);
                                    toast.success("Success! Image Compressed.", {timeout: 4000});
                                })
                                .catch(error => {
                                    console.log("ERROR DURING COMPRESSION: ", error.response.data);
                                    toast.error("Error Occurred, Please try again later.", {timeout: 4000});
                                });
    }
</script>

<!-- <style scoped></style> -->