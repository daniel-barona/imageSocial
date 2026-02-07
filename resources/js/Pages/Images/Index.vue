<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useForm } from '@inertiajs/vue3'

defineProps({
    images: Object,
})

const form = useForm({
    content: '',
    image: null,
})
</script>
<template>
    <form
    @submit.prevent="form.post(route('images.store'), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    })"
    class="bg-white p-4 rounded-lg shadow mb-6"
    >
        <textarea
        v-model="form.content"
        maxlength="280"
        placeholder="¿What happends?"
        class="w-full border rounded p-2 resize-none"
        rows="3"
        />
        <div class="flex justify-between items-center mt-2">
            <input
                type="file"
                accept="image/*"
                @change="e => form.image = e.target.files[0]"
            />
            <button class="bg-black text-white px-4 py-2 rounded">
                Publicar
            </button>
        </div>
    </form>
    <div class="space-y-4">
        <div
            v-for="image in images.data"
            :key="image.id"
            class="bg-white p-4 rounded-lg shadow"
        >
            <div class="flex items-center gap-3 mb-2">
                <img
                    :src="image.user.image
                        ? `/storage/${image.user.image}`
                        : '/img/avatar-default.png'"
                    class="w-10 h-10 rounded-full object-cover"
                />

                <div>
                    <p class="font-semibold">{{ image.user.nick }}</p>
                    <p class="text-xs text-gray-500">
                        {{ new Date(image.created_at).toLocaleString() }}
                    </p>
                </div>
            </div>
            <p class="mb-2 whitespace-pre-line">
                {{ image.content }}
            </p>

            <img
                v-if="image.image"
                :src="`/storage/${image.image}`"
                class="rounded-lg max-h-96"
            />
        </div>
</div>
</template>
