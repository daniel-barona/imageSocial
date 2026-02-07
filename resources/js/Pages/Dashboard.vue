<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, usePage, Head, router } from '@inertiajs/vue3';  
import { reactive, ref, onMounted, onBeforeUnmount, computed } from 'vue';

const props = defineProps({
    images: Object,
})
const form = useForm({
    description: '',
    image: null,
})
const commentForms = reactive({})
const editForms = reactive({})
const editingImageId = ref(null)
const showMenu = reactive({})
const editingComment = reactive({})

const page = usePage()
const search = ref('')
const searchResults = computed(() => page.props.searchResults || [])

function onSearch() {
    router.get(route('users.search'), { q: search.value }, { preserveState: true })
}

function getCommentForm(imageId) {
        if (!commentForms[imageId]) {
                commentForms[imageId] = useForm({ content: '' })
        }
        return commentForms[imageId]
}

function avatarUrl(user) {
    if (!user) return '/img/avatar-default.png';
    // prefer profile_photo_path if provided (might be a full URL)
    const p = user.profile_photo_path || user.image || null;
    if (!p) return '/img/avatar-default.png';
    if (p.startsWith('http') || p.startsWith('/')) return p;
    return `/storage/${p}`;
}

function deleteComment(id) {
    router.delete(route('comments.destroy', id), {}, {
        preserveScroll: true
    })
}

function toggleMenu(imageId) {
    showMenu[imageId] = !showMenu[imageId]
}

function startEditImage(image) {
    editingImageId.value = image.id
    if (!editForms[image.id]) {
        editForms[image.id] = useForm({ description: image.description || '' })
    } else {
        editForms[image.id].description = image.description || ''
    }
}

function cancelEditImage(id) {
    editingImageId.value = null
}

function saveEditImage(id) {
    const f = editForms[id]
    if (!f) return
    f.patch(route('images.update', id), {
        preserveScroll: true,
        onSuccess: () => {
            editingImageId.value = null
            router.reload({ only: ['images'], preserveScroll: true })
        }
    })
}

function deleteImage(id) {
    if (!confirm('¿Eliminar publicación?')) return
    router.delete(route('images.destroy', id), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['images'], preserveScroll: true })
    })
}

function startEditComment(comment) {
    editingComment[comment.id] = true
    if (!editForms[`c${comment.id}`]) {
        editForms[`c${comment.id}`] = useForm({ content: comment.content || '' })
    } else {
        editForms[`c${comment.id}`].content = comment.content || ''
    }
}

function cancelEditComment(id) {
    editingComment[id] = false
}

function saveEditComment(comment) {
    const key = `c${comment.id}`
    const f = editForms[key]
    if (!f) return
    f.patch(route('comments.update', comment.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingComment[comment.id] = false
            router.reload({ only: ['images'], preserveScroll: true })
        }
    })
}

function formatDate(value) {
    if (!value) return ''
    const d = new Date(value)
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
}

function closeMenusOutside(e) {
    // if the click happened inside any menu container, do nothing
    if (e.target && e.target.closest && e.target.closest('[data-menu-container]')) return
    for (const k in showMenu) {
        if (Object.prototype.hasOwnProperty.call(showMenu, k)) showMenu[k] = false
    }
}

onMounted(() => {
    document.addEventListener('click', closeMenusOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', closeMenusOutside)
})
</script>


<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Welcome
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <form
                            @submit.prevent="form.post(route('images.store'), {
                                forceFormData: true,
                                onSuccess: () => {
                                    form.reset(),
                                    router.reload({ only: ['images'] })
                                }
                            })"
                            class="bg-white p-4 rounded-lg shadow"
                        >
                            <textarea
                                v-model="form.description"
                                maxlength="280"
                                placeholder="¿Qué está pasando?"
                                class="w-full border rounded p-2 resize-none"
                            />

                            <div class="flex justify-between items-center mt-2">
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="e => form.image = e.target.files[0]"
                                />

                                <button
                                    type="submit"
                                    class="bg-black text-white px-4 py-2 rounded"
                                    :disabled="form.processing"
                                >
                                    Publicar
                                </button>
                            </div>
                        </form>
                        
                        <!-- Feed: user's images -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium mb-4">Post</h3>

                            <div v-if="images && images.data && images.data.length">
                                <div v-for="image in images.data" :key="image.id" class="mb-4 p-4 border rounded-lg">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <img v-if="image.user" :src="avatarUrl(image.user)" class="w-12 h-12 rounded-full" alt="avatar" />
                                        </div>
                                        <div class="ml-4 w-full">
                                                        <div class="flex justify-between items-start">
                                                            <div>
                                                                <div class="font-semibold">{{ image.user ? image.user.nick : 'Usuario' }}</div>
                                                                <div class="text-xs text-gray-500">{{ formatDate(image.created_at) }}</div>
                                                            </div>
                                                            <div class="relative" data-menu-container>
                                                                <button @click.prevent="toggleMenu(image.id)" class="p-1 rounded hover:bg-gray-100">
                                                                    <!-- simple dots SVG -->
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0z" />
                                                                    </svg>
                                                                </button>
                                                                <div v-if="showMenu[image.id]" class="absolute right-0 mt-2 w-36 bg-white border rounded shadow z-10">
                                                                    <button v-if="$page.props.auth.user && $page.props.auth.user.id === image.user_id" @click.prevent="startEditImage(image)" class="w-full text-left px-3 py-2 hover:bg-gray-100">Editar</button>
                                                                    <button v-if="$page.props.auth.user && $page.props.auth.user.id === image.user_id" @click.prevent="deleteImage(image.id)" class="w-full text-left px-3 py-2 text-red-600 hover:bg-gray-100">Eliminar</button>
                                                                </div>
                                                            </div>
                                                        </div>

                                            <div class="mt-2">
                                                <template v-if="editingImageId === image.id">
                                                    <div class="flex items-start space-x-2">
                                                        <textarea v-model="editForms[image.id].description" class="w-full border rounded p-2" rows="3"></textarea>
                                                        <div class="flex flex-col">
                                                            <button @click.prevent="saveEditImage(image.id)" class="bg-black text-white px-3 py-1 rounded mb-1">Guardar</button>
                                                            <button @click.prevent="cancelEditImage(image.id)" class="px-3 py-1 border rounded">Cancelar</button>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <p class="text-gray-800 whitespace-pre-wrap">{{ image.description }}</p>
                                                </template>
                                            </div>

                                            <img v-if="image.image" :src="`/storage/${image.image}`" class="mt-3 rounded max-w-full h-auto" alt="post image" />
                                            
                                                        <div class="mt-3">
                                                            <button
                                                                @click="router.post(route('images.like', image.id), {}, {
                                                                    preserveScroll: true,
                                                                    only: ['images']
                                                                })"
                                                                class="mt-2 bg-blue-500 text-white px-4 py-2 rounded"
                                                            >
                                                                <span>
                                                                    ❤️ ({{ image.likes_count }})
                                                                    <!-- 🤍 -->
                                                                </span>
                                                            </button>
                                                        </div>
                                                        <div class="mt-4">
                                                            <form
                                                                @submit.prevent="getCommentForm(image.id).post(route('comments.store', image.id), {
                                                                    preserveScroll: true,
                                                                    onSuccess: () => {
                                                                        getCommentForm(image.id).reset();
                                                                        router.reload({ only: ['images'], preserveScroll: true })
                                                                    }
                                                                })"
                                                                class="mt-2">
                                                                <div class="flex">
                                                                    <input
                                                                        v-model="getCommentForm(image.id).content"
                                                                        placeholder="Escribe un comentario..."
                                                                        class="w-full border rounded p-2"
                                                                    />
                                                                    <button type="submit" class="ml-2 bg-black text-white px-4 py-2 rounded">Publicar</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                        <div class="mt-3 space-y-2">
                                                            <div
                                                                v-for="comment in image.comments"
                                                                :key="comment.id"
                                                                class="text-sm"
                                                            >
                                                                <div class="flex items-center space-x-2">
                                                                    <img :src="avatarUrl(comment.user)" class="w-6 h-6 rounded-full" alt="avatar" />
                                                                    <strong>{{ comment.user ? comment.user.nick : 'Usuario' }}:</strong>
                                                                </div>
                                                                <div class="ml-8">
                                                                    <template v-if="editingComment[comment.id]">
                                                                        <div class="flex items-start space-x-2">
                                                                            <textarea v-model="editForms['c' + comment.id].content" class="w-full border rounded p-2" rows="2"></textarea>
                                                                            <div class="flex flex-col">
                                                                                <button @click.prevent="saveEditComment(comment)" class="bg-black text-white px-3 py-1 rounded mb-1">Guardar</button>
                                                                                <button @click.prevent="cancelEditComment(comment.id)" class="px-3 py-1 border rounded">Cancelar</button>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                    <template v-else>
                                                                        <span class="whitespace-pre-wrap">{{ comment.content }}</span>
                                                                        <button
                                                                            v-if="comment.user_id === $page.props.auth.user.id"
                                                                            @click.prevent="startEditComment(comment)"
                                                                            class="text-blue-500 ml-2"
                                                                        >
                                                                            Editar
                                                                        </button>
                                                                        <button
                                                                            v-if="comment.user_id === $page.props.auth.user.id"
                                                                            @click="deleteComment(comment.id)"
                                                                            class="text-red-500 ml-2"
                                                                        >
                                                                            Eliminar
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- pagination controls (if any) -->
                                    <div class="mt-4">
                                        <button v-if="images.prev_page_url" @click.prevent="router.visit(images.prev_page_url)" class="px-3 py-1 mr-2 border rounded">Anterior</button>
                                        <button v-if="images.next_page_url" @click.prevent="router.visit(images.next_page_url)" class="px-3 py-1 border rounded">Siguiente</button>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500">Todavía no hay publicaciones.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
