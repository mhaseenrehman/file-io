<template>
    <section class="controls p-8">
        <div class="control-group dark:text-gray-400 p-2">
            <label for="compressionRange">
                Compression Level: <span id=compressionLevel>{{ chosenImage.quality }}</span>%
            </label>
            <input class="col-start-1 row-start-1 w-full accent-red-500 bg-transparent rounded-lg" type="range" name="compressionRange" id="compressionRange" min="10" max="90" step="5" v-model="chosenImage.quality">
        </div>

        <div class="control-group dark:text-gray-400 p-2">
            <label for="compressionFormat">
                Format
            </label>
            <select class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-gray-800 py-1.5 pr-7 pl-3 text-base text-gray-400 *:bg-gray-800 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" name="compressionFormat" id="compressionFormat" v-model="chosenImage.format">
                <option value="webp">webp</option>
                <option value="jpg">jpg</option>
                <option value="png">png</option>
            </select>
        </div>

        <div class="control-group dark:text-gray-400 p-2">
            <label for="compressionWidth">
                Width
            </label>
            <input class="block min-w-0 w-full rounded-md grow bg-gray-800 py-1.5 pr-3 pl-1 text-base text-white placeholder:text-gray-500 focus:outline-none sm:text-sm/6" type="number" name="compressionWidth" id="compressionWidth" v-model="chosenImage.width" placeholder="N/A" >
        </div>
    </section>
</template>



<script setup>
    const props = defineProps({
        chosenImage: {
            type: Object,
            required: true
        }
    });
</script>

<!-- <style scoped></style> -->