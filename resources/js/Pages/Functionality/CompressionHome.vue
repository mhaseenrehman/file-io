<template lang="">
    <div class="flex flex-col min-h-screen justify-center items-center dark:bg-gray-800 h-full">
        <form class="flex flex-col justify-center items-center" @submit.prevent="compressImage">
            <CompressionInputs :chosenImage="data.chosenImage"/>
            <div class="p-2 pb-12" @drop.prevent="handleDrop" @dragover.prevent>
                <label for="imagesInput" class="drop-zone-label dark:text-gray-400 border-2 border-dashed p-8" >
                    Drag and Drop images here or Click to select from Directory
                </label>
                <input type="file" name="imagesInput" id="imagesInput" accept="image/*" hidden @change="handleFileChange">
                <!-- <input type="file" multiple name="imagesInput" id="imagesInput" accept="image/*" hidden> -->
            </div>
            <button type="submit" id="downloadCompressedButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full">Download Compressed</button>
        </form>
        <div class="grid grid-cols-2 py-2">
            <PreviewImageCard :chosenImage="data.chosenImage"></PreviewImageCard>
            <DownloadableImageDetails :fileInformation="data.fileInfo"/>
        </div>
    </div>
</template>

<script setup>
    import CompressionInputs from '@/Components/CompressionInputs.vue';
    import PreviewImageCard from '@/Components/PreviewImageCard.vue';
    import DownloadableImageDetails from '@/Components/DownloadableImageDetails.vue';
    
    import { reactive } from 'vue';
    import axios from 'axios';
    import { useToast } from 'vue-toastification';
import { split } from 'postcss/lib/list';

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
        event.target.value = '';
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

        const response = await apiClient.post('', formData)
                                .then(response => {
                                    console.log("SUCCESS! Image Compressed.");
                                    console.log(response.data);
                                    provideDownloadLink(response.data);
                                    toast.success("Success! Image Compressed.", {timeout: 4000});
                                })
                                .catch(error => {
                                    console.log("ERROR DURING COMPRESSION: ", error.response.data);
                                    toast.error("Error Occurred, Please try again later.", {timeout: 4000});
                                });
    }

    const provideDownloadLink = ({compressed_image_size, filename, image_data, original_image_size}) => {
        if (compressed_image_size && image_data && original_image_size) {
            console.log("doing thsi")
            const fileExtension = filename.split('.').pop();
            const compressedImage = `data:image/${fileExtension};base64,${image_data}`;
            data.fileInfo = { filename, compressed_image_size, original_image_size };
            const link = document.createElement('a');
            link.href = compressedImage;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else{
            toast.error("Error Occurred, Please try again later.", {timeout: 4000});
        }
    }
</script>

<!-- <style scoped></style> -->