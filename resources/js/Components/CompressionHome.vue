<template lang="">
    <div>
        <form>
            <section class="controls">

                <div class="control-group">
                    <label for="compressionRange">
                        Compression Level: 10%
                    </label>
                    <input type="range" name="compressionRange" id="compressionRange" min="10" max="90" step="5">
                </div>

                <div class="control-group">
                    <label for="compressionFormat">
                        Format
                    </label>
                    <select name="compressionFormat" id="compressionFormat">
                        <option value="webp"></option>
                        <option value="jpeg"></option>
                        <option value="png"></option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="compressionWidth">
                        Width
                    </label>
                    <input type="number" name="compressionWidth" id="compressionWidth" placeholder="N/A">
                </div>

            </section>
        </form>
    </div>
</template>

<script setup>
</script>

<style scoped>
</style>