<script setup>
import { Fieldtype } from '@statamic/cms';
import { Text } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update } = Fieldtype.use(emit, props);
defineExpose(expose);
</script>

<template>
    <Text :text="value" @update:model-value="update" variant="subtle" />
</template>
