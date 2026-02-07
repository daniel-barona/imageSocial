<script setup>
import { ref, computed, reactive, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, Head, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const showingNavigationDropdown = ref(false);
const page = usePage();
// `profileUser` is the user whose profile page we're viewing (falls back to auth user)
const profileUser = computed(() => page.props.user ?? (page.props.auth ? page.props.auth.user : {}));
const search = ref('')
const timer = ref(null)
const localResults = ref([])
const searchResults = computed(() => {
    return localResults.value.length ? localResults.value : (page.props.searchResults ?? [])
})
const posts = computed(() => {
    // Prefer images provided by the controller for this profile page (images of the profile user)
    if (page.props.images && page.props.images.data) return page.props.images.data
    if (user.value && user.value.images) return user.value.images
    if (page.props.auth && page.props.auth.user && page.props.auth.user.images) return page.props.auth.user.images
    return []
})
const commentForms = reactive({})
const editForms = reactive({})
const editingImageId = ref(null)
const showMenu = reactive({})
const editingComment = reactive({})

function getCommentForm(imageId) {
    if (!commentForms[imageId]) {
        commentForms[imageId] = useForm({ content: '' })
    }
    return commentForms[imageId]
}

function onSearch() {
    if (timer.value) clearTimeout(timer.value)
    timer.value = setTimeout(async () => {
        try {
            const res = await axios.get(route('users.search'), { params: { q: search.value }, headers: { Accept: 'application/json' } })
            localResults.value = Array.isArray(res.data) ? res.data : []
        } catch (e) {
            localResults.value = []
        }
    }, 300)
}

function avatarUrl(user) {
    if (!user) return '/img/avatar-default.png'
    const p = user.profile_photo_path || user.image || null
    if (!p) return '/img/avatar-default.png'
    if (p.startsWith('http') || p.startsWith('/')) return p
    return `/storage/${p}`
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
    if (timer.value) clearTimeout(timer.value)
})
</script>

<template>
    <Head title="Your Profile" />
        <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Back
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:justify-center sm:flex-1">
                            <div class="relative w-full max-w-2xl">
                                <input
                                    v-model="search"
                                    @input="onSearch"
                                    type="text"
                                    placeholder="Buscar usuarios por name, surname o nick..."
                                    class="w-full border rounded-full px-4 py-2"
                                />

                                <div v-if="searchResults.length" class="absolute left-0 right-0 mt-1 bg-white border rounded shadow z-50 max-h-64 overflow-auto">
                                    <div class="divide-y">
                                        <a v-for="u in searchResults" :key="u.id" :href="route('user.profile', u.nick)" class="flex items-center px-3 py-2 hover:bg-gray-100">
                                            <img :src="avatarUrl(u)" class="w-8 h-8 rounded-full mr-3" alt="avatar" />
                                            <div class="text-sm">
                                                <div class="font-medium">{{ u.name }} {{ u.surname }}</div>
                                                <div class="text-xs text-gray-500">@{{ u.nick }}</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex items-center rounded-md">
                                            <img
                                                :src="$page.props.auth.user.image
                                                    ? `/storage/${$page.props.auth.user.image}`
                                                    : '/img/avatar-default.png'"
                                                class="h-8 w-8 rounded-full object-cover me-2"
                                                alt="Avatar"
                                            />
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.nick }}
                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('user.profile', $page.props.auth.user.nick)">
                                            Profile
                                        </DropdownLink>

                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Settings
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                        

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>
            <header class="bg-white shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Profile Page
                    </h2>
                </div>
            </header>
            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>
            
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <h1 class="text-2xl font-bold text-gray-900">
                                                        {{ profileUser.name }} {{ profileUser.surname }}
                                                    </h1>
                                                    <p class="text-sm text-gray-600">@{{ profileUser.nick }}</p>
                                                </div>

                                                <div>
                                                    <img
                                                        :src="profileUser.image ? `/storage/${profileUser.image}` : '/img/avatar-default.png'"
                                                        class="h-20 w-20 rounded-full object-cover"
                                                        alt="Avatar"
                                                    />
                                                </div>
                                            </div>

                        <div>
                            <h2 class="text-lg font-semibold">Posts</h2>
                            <div v-if="posts.length === 0" class="text-sm text-gray-500 mt-2">No posts yet.</div>
                            <div v-else class="mt-4">
                                <div v-for="image in posts" :key="image.id" class="mb-4 p-4 border rounded-lg">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <img v-if="image.user" :src="avatarUrl(image.user)" class="w-12 h-12 rounded-full" alt="avatar" />
                                        </div>
                                        <div class="ml-4 w-full">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="font-semibold">{{ image.user ? image.user.nick : user.value.nick }}</div>
                                                    <div class="text-xs text-gray-500">{{ formatDate(image.created_at) }}</div>
                                                </div>
                                                <div class="relative" data-menu-container>
                                                    <button @click.prevent="toggleMenu(image.id)" class="p-1 rounded hover:bg-gray-100">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>    

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<!-- <template>
    <div class="max-w-3xl mx-auto py-10">
        <h1 class="text-2xl font-bold">
            {{ user.name }} {{ user.surname }}
        </h1>

        <p class="text-gray-600">@{{ user.nick }}</p>
        <p class="mt-4">{{ user.email }}</p>
    </div>
</template> -->
