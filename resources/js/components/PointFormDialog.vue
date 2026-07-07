<template>
    <v-dialog v-model="model" max-width="640" scrollable>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon :icon="isEditing ? 'mdi-pencil' : 'mdi-map-marker-plus'" class="mr-2" />
                {{ isEditing ? 'Editar ponto de coleta' : 'Sugerir novo ponto de coleta' }}
            </v-card-title>
            <v-divider />

            <v-card-text>
                <v-form ref="form">
                    <v-text-field
                        v-model="formData.name"
                        label="Nome do local *"
                        :rules="[required]"
                        variant="outlined"
                        density="comfortable"
                    />
                    <v-text-field
                        v-model="formData.address"
                        label="Endereço *"
                        :rules="[required]"
                        variant="outlined"
                        density="comfortable"
                    />
                    <v-select
                        v-model="formData.waste_types"
                        :items="availableTypes"
                        item-title="label"
                        item-value="value"
                        label="Tipos de resíduo aceitos *"
                        :rules="[atLeastOne]"
                        multiple
                        chips
                        closable-chips
                        variant="outlined"
                        density="comfortable"
                    />

                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="formData.contact_phone"
                                label="Telefone de contato"
                                variant="outlined"
                                density="comfortable"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="formData.contact_email"
                                label="E-mail de contato"
                                :rules="[validEmail]"
                                variant="outlined"
                                density="comfortable"
                            />
                        </v-col>
                    </v-row>

                    <v-select
                        v-if="isEditing"
                        v-model="formData.status"
                        :items="statusOptions"
                        label="Status"
                        variant="outlined"
                        density="comfortable"
                    />

                    <div class="text-subtitle-2 mb-1">
                        <v-icon size="small" icon="mdi-cursor-default-click" />
                        Clique no mapa para {{ isEditing ? 'ajustar' : 'marcar' }} o local *
                    </div>
                    <div ref="mapEl" class="mini-map"></div>
                    <div class="text-caption mt-1" :class="formData.latitude ? 'text-success' : 'text-medium-emphasis'">
                        <template v-if="formData.latitude">
                            Local marcado: {{ formData.latitude }}, {{ formData.longitude }}
                        </template>
                        <template v-else>
                            Nenhum local marcado ainda.
                        </template>
                    </div>

                    <v-alert v-if="error" type="error" density="compact" class="mt-3">
                        {{ error }}
                    </v-alert>
                </v-form>
            </v-card-text>

            <v-divider />
            <v-card-actions>
                <v-spacer />
                <v-btn variant="text" :disabled="submitting" @click="model = false">Cancelar</v-btn>
                <v-btn color="primary" variant="flat" :loading="submitting" @click="submit">
                    {{ isEditing ? 'Salvar alterações' : 'Enviar para validação' }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue';
import L from 'leaflet';
import { createPoint, updatePoint } from '../api';
import { WASTE_TYPES } from '../wasteTypes';

const model = defineModel({ type: Boolean, default: false });

const props = defineProps({
    point: { type: Object, default: null },
});

const emit = defineEmits(['created', 'updated']);

const isEditing = computed(() => !!props.point);

const CENTER = [-26.96, -48.66];

const availableTypes = WASTE_TYPES;
const statusOptions = [
    { value: 'pending', title: 'Pendente' },
    { value: 'approved', title: 'Aprovado' },
];

const form = ref(null);
const submitting = ref(false);
const error = ref('');

const formData = reactive({
    name: '',
    address: '',
    waste_types: [],
    contact_phone: '',
    contact_email: '',
    latitude: null,
    longitude: null,
    status: 'approved',
});

const mapEl = ref(null);
let map = null;
let marker = null;

const required = (v) => (!!v && String(v).trim() !== '') || 'Campo obrigatório';
const atLeastOne = (v) => (Array.isArray(v) && v.length > 0) || 'Selecione ao menos um tipo';
const validEmail = (v) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'E-mail inválido';

function fillForm() {
    if (props.point) {
        Object.assign(formData, {
            name: props.point.name ?? '',
            address: props.point.address ?? '',
            waste_types: [...(props.point.waste_types ?? [])],
            contact_phone: props.point.contact_phone ?? '',
            contact_email: props.point.contact_email ?? '',
            latitude: props.point.latitude ?? null,
            longitude: props.point.longitude ?? null,
            status: props.point.status ?? 'approved',
        });
    } else {
        Object.assign(formData, {
            name: '',
            address: '',
            waste_types: [],
            contact_phone: '',
            contact_email: '',
            latitude: null,
            longitude: null,
            status: 'pending',
        });
    }
    error.value = '';
    form.value?.resetValidation?.();
}

function setLocation(latlng) {
    formData.latitude = Number(latlng.lat.toFixed(7));
    formData.longitude = Number(latlng.lng.toFixed(7));
    if (marker) {
        marker.setLatLng(latlng);
    } else {
        marker = L.marker(latlng).addTo(map);
    }
}

function initMap() {
    const hasLocation = formData.latitude !== null && formData.longitude !== null;
    map = L.map(mapEl.value).setView(
        hasLocation ? [formData.latitude, formData.longitude] : CENTER,
        hasLocation ? 15 : 11,
    );
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; colaboradores do OpenStreetMap',
    }).addTo(map);

    if (hasLocation) {
        marker = L.marker([formData.latitude, formData.longitude]).addTo(map);
    }

    map.on('click', (e) => setLocation(e.latlng));

    setTimeout(() => map && map.invalidateSize(), 200);
}

function destroyMap() {
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
}

watch(model, async (open) => {
    if (open) {
        fillForm();
        await nextTick();
        initMap();
    } else {
        destroyMap();
    }
});

async function submit() {
    error.value = '';
    const { valid } = await form.value.validate();
    if (!valid) return;

    if (formData.latitude === null || formData.longitude === null) {
        error.value = 'Clique no mapa para marcar a localização do ponto.';
        return;
    }

    submitting.value = true;
    try {
        if (isEditing.value) {
            await updatePoint(props.point.id, { ...formData });
            emit('updated');
        } else {
            const { status, ...payload } = formData;
            await createPoint(payload);
            emit('created');
        }
    } catch (e) {
        error.value = e.body?.message || 'Não foi possível salvar. Verifique os dados.';
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.mini-map {
    height: 280px;
    width: 100%;
    border-radius: 6px;
    overflow: hidden;
    z-index: 0;
}
</style>
