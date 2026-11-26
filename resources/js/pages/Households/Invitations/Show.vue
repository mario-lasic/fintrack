<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";

interface Props {
    token: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
});

function submit(): void {
    form.post('/household-invitations/accept');
}
</script>
<template>
    <h1>Household Invitation</h1>
    <p>You are invited to join this household.</p>
    <form @submit.prevent="submit">
        <p v-if="form.errors.token">
            {{ form.errors.token }}
        </p>
        <button type="submit" :disabled="form.processing">
            Accept invitation
        </button>
    </form>
</template>
