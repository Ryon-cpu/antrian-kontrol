<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <h2 class="text-xl font-semibold leading-tight text-gray-800">
        {{ title }}
    </h2>
</template>
