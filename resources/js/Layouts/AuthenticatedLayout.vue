<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage()
const search = ref('')
const timer = ref(null)
const localResults = ref([])
const searchResults = computed(() => {
    return localResults.value.length ? localResults.value : (page.props.searchResults || [])
})

function avatarUrl(user) {
    if (!user) return '/img/avatar-default.png'
    const p = user.profile_photo_path || user.image || null
    if (!p) return '/img/avatar-default.png'
    if (typeof p === 'string' && (p.startsWith('http') || p.startsWith('/'))) return p
    return `/storage/${p}`
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

onBeforeUnmount(() => {
    if (timer.value) clearTimeout(timer.value)
})
</script>

<template>
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
                                    Dashboard
                                </NavLink>
                            </div>
                        </div>

                        <!-- Centered search (visible on sm+) -->
                        <div class="flex-1 hidden sm:flex sm:items-center sm:justify-center">
                            <div class="relative w-full max-w-2xl">
                                <input
                                    v-model="search"
                                    @input="onSearch"
                                    type="text"
                                    placeholder="Buscar usuarios por nombre, apellido o nick"
                                    class="w-full border rounded-full px-4 py-2"
                                />

                                <div v-if="searchResults.length" class="absolute left-0 right-0 mt-1 bg-white border rounded shadow z-50 max-h-64 overflow-auto">
                                    <a v-for="r in searchResults" :key="r.id" :href="route('user.profile', r.nick)" class="flex items-center px-3 py-2 hover:bg-gray-100">
                                        <img :src="avatarUrl(r)" class="w-8 h-8 rounded-full mr-3" />
                                        <div class="text-sm">
                                            <div class="font-medium">{{ r.name }} {{ r.surname }}</div>
                                            <div class="text-xs text-gray-500">@{{ r.nick }}</div>
                                        </div>
                                    </a>
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

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
