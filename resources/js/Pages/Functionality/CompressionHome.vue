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
        <!-- <div v-for="url in data.chosenImages.imageUrls" class="grid grid-cols-2 py-2"> -->
        <div v-for="file in data.chosenImages.files" class="grid grid-cols-2 py-2">
            <PreviewImageCard :chosenImageUrl="file.url"></PreviewImageCard>
            <StatusButton v-if="file.link" :downloadableLink="file.link"></StatusButton>
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
    import StatusButton from '@/Components/statusButton.vue';
    import { useFileValidator } from '@/Composables/useFileValidator';
    
    // Toast Notification
    const toast = useToast();

    // Validators - Used to validate input output
    const {validateFile} = useFileValidator();

    // File Class
    class File {
        constructor(fs, url, link=null) {
            this.fileSelected = fs;
            this.url = url;
            this.link = link;
        }
    };

    // Reactive Data and Initial State
    const INITIAL_IMAGE_STATE = {
        files: [],
        indices: [],
        format: "webp",
        quality: 50,
        width: null
    }
    
    const data = reactive({
        chosenImages: {...INITIAL_IMAGE_STATE},
        fileInfo: null,

        addFile(file, url, index) {
            let f = new File(file, url);
            data.chosenImages.files.push(f);
            data.chosenImages.indices.push(index);
        },

        makePending() {
            this.chosenImages.files.forEach(f => {
                f.link = "Pending";
            });
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
                    data.addFile(files[i], e.target.result, i);
                }
                reader.readAsDataURL(files[i]);
            }
        }
    }

    const startJobClient = axios.create({
            baseURL: '/api/imageCompress',
            headers: {
                'Content-Type': 'multipart/form-data',
            },
    });
    
    const statusPollClient = axios.create({
        baseURL: '/api/imageStatusPing',
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
    
    const downloadPollClient = axios.create({
        baseURL: '/api/imageDownload/',
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        responseType: 'blob'
    });

    // Compress Method
    const compressImage = async () => {
        const formData = new FormData();

        data.chosenImages.files.forEach(f => {
            formData.append('images[]', f.fileSelected);
        });
        data.chosenImages.indices.forEach(i => {
            formData.append('indices[]', i);
        });

        formData.append('quality', data.chosenImages.quality);
        formData.append('width', data.chosenImages.width);
        formData.append('format', data.chosenImages.format);

        data.makePending();

        const response = await startJobClient.post('', formData)
                                .then(response => {
                                    console.log("SUCCESS: Image Queued.");
                                    console.log(response.data);
                                    console.log(response.data.request_id);
                                    pollStatus(response.data.request_id);
                                    toast.success("SUCCESS! Images sent for Compression.", {timeout: 4000});
                                })
                                .catch(error => {
                                    console.log("ERROR: During Compression.", error.response.data);
                                    toast.error("ERROR! Please try again later.", {timeout: 4000});
                                });
                                // .finally(() => {
                                //     resetFields();
                                // });
    }

    // Continuously poll for the compress job status
    const pollStatus = async (imageId) => {
        const intervalRequest = async () => {
            let completed = false;
            const response = await statusPollClient.get('', {params: {id: imageId}})
                                .then(response => {
                                    console.log(response.data);
                                    if (response.data.current_status === "complete") {
                                        console.log("SUCCESS: Image Compressed.");
                                        toast.success("SUCCESS! Images Compressed.", {timeout: 4000});
                                        provideDownloadLink(response.data.id);
                                        completed = true;
                                    }
                                })
                                .catch(error => {
                                    console.log("Error During Compression.", error.response.data);
                                });
            
            if (completed) {
                return;
            } else {
                setTimeout(intervalRequest, 3000);
            }
        };

        intervalRequest();
    };

    // Download Method
    const provideDownloadLink = async (id) => {
        downloadPollClient.defaults.baseURL = `/api/imageDownload`;
        
        const res = await downloadPollClient.get('', {params: {id: id, format: data.chosenImages.format}}).then(response => {
            console.log("doing this");

            const { headers } = response;
            const dadosFilename = headers['content-disposition'].split('filename=');
            const filename = dadosFilename[1];

            console.log(dadosFilename);
            console.log(filename);

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

        }).catch(error => {
            console.log("Error During Compression.", error.response.data);
        });

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