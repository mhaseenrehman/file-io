<template lang="">
    <div class="flex flex-col min-h-screen justify-center items-center dark:bg-gray-800 h-full">
        <form class="flex flex-col justify-center items-center" @submit.prevent="compressImage" enctype="multipart/form-data">
            <CompressionInputs :chosenImages="data.chosenImages"/>
            <div class="p-2 pb-12" @drop.prevent="handleDrop" @dragover.prevent>
                <label for="imagesInput" class="drop-zone-label dark:text-gray-400 border-2 border-dashed p-8" >
                    Drag and Drop images here or Click to select from Directory
                </label>
                <input type="file" multiple name="imagesInput" id="imagesInput" accept="image/*" hidden @change="handleFileChange">
            </div>
            <button type="submit" id="downloadCompressedButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full">Download Compressed</button>
        </form>
        <div v-for="url in data.chosenImages.imageUrls" class="grid grid-cols-2 py-2">
            <PreviewImageCard :chosenImageUrl="url"></PreviewImageCard>
            <!-- <DownloadableImageDetails :fileInformation="data.fileInfo"/> -->
        </div>
    </div>
</template>

<script setup>

    // Imports
    import { reactive } from 'vue';
    import axios from 'axios';
    import { useToast } from 'vue-toastification';
    import { split } from 'postcss/lib/list';

    import CompressionInputs from '@/Components/CompressionInputs.vue';
    import PreviewImageCard from '@/Components/PreviewImageCard.vue';
    import DownloadableImageDetails from '@/Components/DownloadableImageDetails.vue';
    import { useFileValidator } from '@/Composables/useFileValidator';
    
    // Toast Notification
    const toast = useToast();

    // Validators - Used to validate input output
    const {validateFile} = useFileValidator();

    // Reactive Data and Initial State
    const INITIAL_IMAGE_STATE = {
        filesSelected: [],
        imageUrls: [],
        format: "webp",
        quality: 50,
        width: null
    }
    
    const data = reactive({
        chosenImages: {...INITIAL_IMAGE_STATE},
        fileInfo: null,

        addFile(file, url) {
            this.chosenImages.filesSelected.push(file);
            this.chosenImages.imageUrls.push(url);
        }
    });

    // File Methods
    const handleDrop = (event) => {
        displayPreviewImage(event.dataTransfer.files);
    }
    const handleFileChange = (event) => {
        displayPreviewImage(event.target.files);
        event.target.value = '';
    }
    const displayPreviewImage = (files) => {
        for (let i = 0; i < files.length; i++) {
            if (validateFile(files[i])) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    data.addFile(files[i], e.target.result);
                }
                reader.readAsDataURL(files[i]);
            }
        }
    }

    // ==========================================================
    // CHANGING THIS ============================================
    // ==========================================================

    // Compress Method
    const compressImage = async () => {
        //if (validateFile(data.chosenImages.filesSelected)) {}

        const apiClient = axios.create({
            baseURL: '/api/imageCompress',
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const formData = new FormData();
        //formData.append('images', data.chosenImages.filesSelected);
        data.chosenImages.filesSelected.forEach(file => {
            formData.append('images', file);
        });
        formData.append('quality', data.chosenImages.quality);
        formData.append('width', data.chosenImages.width);
        formData.append('format', data.chosenImages.format);

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
                                })
                                .finally(() => {
                                    resetFields();
                                });
        
    }

    // Download Method
    const provideDownloadLink = ({compressed_image_size, filename, image_data, original_image_size}) => {
        console.log(compressed_image_size);
        console.log(filename);
        console.log(image_data);
        
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

    const resetFields = () => {
        data.chosenImages = { ...INITIAL_IMAGE_STATE};
    }

    // BACKUP

    // const INITIAL_IMAGE_STATE = {
    //     filesSelected: null,
    //     imageUrl: null,
    //     format: "webp",
    //     quality: 50,
    //     width: null
    // }

    // const handleDrop = (event) => {
    //     displayPreviewImage(event.dataTransfer.files[0]);
    // }
    // const handleFileChange = (event) => {
    //     displayPreviewImage(event.target.files[0]);
    //     event.target.value = '';
    // }
    // const displayPreviewImage = (file) => {
    //     if (validateFile(file)) {
    //         const reader = new FileReader();
    //         reader.onload = (e) => {
    //             data.chosenImage = {
    //                 ...data.chosenImage,
    //                 filesSelected: file,
    //                 imageUrl: e.target.result,
    //             }
    //         }
    //         reader.readAsDataURL(file);
    //     }
    // }
</script>

<!-- <style scoped></style> -->