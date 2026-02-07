<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import Cropper from 'cropperjs';
import { ref, nextTick } from 'vue';


defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const fileInputKey = ref(Date.now());

const form = useForm({
    name: user.name ?? '',
    surname: user.surname ?? '',
    nick: user.nick ?? '',
    image: null,
    remove_image: false,
});

const imagePreview = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];

    if (!file) return;

    form.image = file;

    // preview local
    imagePreview.value = URL.createObjectURL(file);
};

const removeAvatar = () => {
    form.remove_image = true;
    form.image = null;
    imagePreview.value = null;
    fileInputKey.value = Date.now();

    form.post(route('profile.update'), {
        method: 'patch',
        //forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.remove_image = false;
            router.reload({ only: ['auth'] });
        }
    });
};

</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.post(route('profile.update'), {
            method: 'patch',
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['auth'] })
            }
        })"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="surname" value="Surname" />

                <TextInput
                    id="surname"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.surname"
                    required
                    autofocus
                    autocomplete="surname"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="nick" value="nick" />

                <TextInput
                    id="nick"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.nick"
                    required
                    autocomplete="nick"
                />

                <InputError class="mt-2" :message="form.errors.nick" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    :modelValue="user.email"
                    type="email"
                    class="mt-1 block w-full"
                    disabled
                />
            </div>

            
            <div class="mt-4">
                <InputLabel for="image" value="Profile Image" />
                <input
                :key="fileInputKey"
                id="image"
                type="file"
                accept="image/*"
                @change="handleImageChange"
                class="mt-1 block w-full"
                />

                <InputError class="mt-2" :message="form.errors.image" />     
            </div>
            <div v-if="imagePreview" class="mt-4">
                <p class="text-sm text-gray-600 mb-2">Preview</p>

                <img
                    :src="imagePreview"
                    class="h-32 w-32 rounded-full object-cover ring-2 ring-gray-300"
                    alt="Preview avatar"
                />  
            </div>
            
            <div v-if="$page.props.auth.user.image" class="mt-2">
                <button
                    type="button"
                    @click="removeAvatar"
                    class="text-sm text-red-600 hover:underline"
                >
                    Quitar imagen
                </button>
            </div>


            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
