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
        <div v-if="data.uploadedFiles" v-for="(file, index) in data.chosenImages.files" v-bind:key="index" class="grid grid-cols-2 py-2">
            <PreviewImageCard v-if="file" :chosenImageUrl="file.url"></PreviewImageCard>
            <StatusButton v-if="file && file.link" :downloadableLink="file.link" :filename="file.name"></StatusButton>
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
        constructor(fs, url, link='Waiting', filename=null) {
            this.fileSelected = fs;
            this.url = url;
            this.link = link;
            this.name = filename;
        }
    };

    // Reactive Data and Initial State
    const INITIAL_IMAGE_STATE = {
        files: [],
        //indices: [],
        format: "webp",
        quality: 50,
        width: null
    }
    
    const data = reactive({
        chosenImages: {...INITIAL_IMAGE_STATE},
        uploadedFiles: false,
        fileInfo: null,

        makePending() {
            this.chosenImages.files.forEach(f => {
                f.link = "Pending";
            });
        }
    });

    // File Methods
    const handleDrop = async (event) => {
        await displayPreviewImage(event.dataTransfer.files);
    }

    const handleFileChange = async (event) => {
        await displayPreviewImage(event.target.files);
        event.target.value = '';
    }

    const addFile = (file, url, index) => {
        let f = new File(file, url);
        data.chosenImages.files[index] = f;
    }

    const displayPreviewImage = async (files) => {

        // const filePromises = [...files].map((file, i) => {
        //     return new Promise((resolve, reject) => {
        //         if (validateFile(files[i])) {
        //             const reader = new FileReader();
        //             reader.onload = async (e) => {
        //                 data.addFile(file, e.target.result, i);
        //             }
        //             reader.readAsDataURL(file);
        //         }
        //     })
        // });
        for (let i = 0; i < files.length; i++) {
            if (validateFile(files[i])) {
                const reader = new FileReader();
                reader.onload = async (e) => {
                    addFile(files[i], e.target.result, i);
                }
                reader.readAsDataURL(files[i]);
            }
        }
        //await Promise.all(filePromises);
        console.log("completed");
        console.log(data.chosenImages);
        data.uploadedFiles = true;
    }

    // Axios Clients
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

        // data.chosenImages.files.forEach(f => {
        //     formData.append('images[]', f.fileSelected);
        // });
        for (var index in data.chosenImages.files) {
            formData.append('images[]', data.chosenImages.files[index].fileSelected);
            formData.append('indices[]', index);
        }
        // data.chosenImages.indices.forEach(i => {
        //     formData.append('indices[]', i);
        // });

        formData.append('quality', data.chosenImages.quality);
        formData.append('width', data.chosenImages.width);
        formData.append('format', data.chosenImages.format);

        data.makePending();

        console.log(formData);

        const response = await startJobClient.post('', formData)
                                .then(response => {
                                    console.log("SUCCESS: Image Queued.");
                                    console.log(response.data);
                                    //console.log(response.data.request_id)
                                    console.log(response.data.request_ids);

                                    let jobs = response.data.request_ids;
                                    Object.keys(jobs).forEach(function(key) {
                                        console.log("Request poll for: ", jobs[key]);
                                        //pollStatus(response.data.request_id);
                                        pollStatus(jobs[key]);
                                    });
                                           
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
                                        provideDownloadLink(response.data.id, response.data.file_request_index);
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
    const provideDownloadLink = async (id, fileIndex) => {
        downloadPollClient.defaults.baseURL = `/api/imageDownload`;
        
        const res = await downloadPollClient.get('', {params: {id: id, format: data.chosenImages.format}}).then(response => {
            const { headers } = response;
            const dadosFilename = headers['content-disposition'].split('filename=');
            const filename = dadosFilename[1];

            const url = window.URL.createObjectURL(new Blob([response.data]));
            data.chosenImages.files[fileIndex].link = url;
            data.chosenImages.files[fileIndex].name = filename;

            // const link = document.createElement('a');
            // link.href = url;
            // link.download = filename;
            // document.body.appendChild(link);
            // link.click();
            // document.body.removeChild(link);

        }).catch(error => {
            console.log("Error During Compression.", error.response.data);
        });

    }

    const resetFields = () => {
        data.chosenImages = { ...INITIAL_IMAGE_STATE};
    }
</script>

<!-- <style scoped></style> -->