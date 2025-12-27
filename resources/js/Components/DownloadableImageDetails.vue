<template>
    <div class="parent flex flex-col rounded border-2 border-solid max-w-sum w-full lg:max-w-full lg:flex flex dark:text-gray-400" id="results-container" v-if="fileInformation">
        <span>
            Name: <i>{{fileInformation.filename}}</i>
        </span>
        <br>
        <span>
            Original Size: <i>{{fileInformation.original_image_size}}Kb</i>
        </span>
        <br>
        <span>
            Compressed Size: <i>{{fileInformation.compressed_image_size}}Kb</i>
        </span>
    </div>
</template>

<script setup>
    const props = defineProps({
        fileInformation: {
            type: [Object, null],
            required: true
        }
    });
</script>


<style scoped></style>