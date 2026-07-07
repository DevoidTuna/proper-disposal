<template>
    <div class="map-wrapper">
        <div ref="mapEl" class="map"></div>

        <v-expansion-panels v-model="filterPanel" class="filter-panel" elevation="6">
            <v-expansion-panel value="filter">
                <v-expansion-panel-title>
                    <v-icon size="small" icon="mdi-filter-variant" class="mr-2" />
                    Filtrar por resíduo
                    <v-chip v-if="filters.length" size="x-small" color="primary" class="ml-2">
                        {{ filters.length }}
                    </v-chip>
                </v-expansion-panel-title>
                <v-expansion-panel-text class="filter-panel-text">
                    <v-chip-group v-model="filters" multiple column selected-class="text-white">
                        <v-chip
                            v-for="t in availableTypes"
                            :key="t.value"
                            :value="t.value"
                            :color="t.color"
                            :prepend-icon="t.icon"
                            size="small"
                            filter
                            variant="elevated"
                        >
                            {{ t.label }}
                        </v-chip>
                    </v-chip-group>
                    <div v-if="loading" class="text-caption text-medium-emphasis mt-1">
                        Carregando pontos…
                    </div>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>

        <v-btn
            class="locate-btn"
            color="primary"
            icon="mdi-crosshairs-gps"
            size="large"
            elevation="6"
            @click="onLocate"
        />

        <v-alert
            v-if="notice.show"
            class="alert-box"
            :type="notice.type"
            density="compact"
            closable
            @click:close="notice.show = false"
        >
            {{ notice.text }}
        </v-alert>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useDisplay } from 'vuetify';
import L from 'leaflet';
import { getApprovedPoints } from '../api';
import { WASTE_TYPES, wasteLabel } from '../wasteTypes';

const { smAndDown: mobile } = useDisplay();

const CENTER = [-26.96, -48.66];
const INITIAL_ZOOM = 11;

const filterPanel = ref(mobile.value ? undefined : 'filter');

const mapEl = ref(null);
let map = null;
let markersLayer = null;
let userMarker = null;

const points = ref([]);
const filters = ref([]);
const loading = ref(true);

const notice = ref({ show: false, text: '', type: 'error' });
let noticeTimer = null;

function showNotice(text, type = 'error', timeout = 6000) {
    if (noticeTimer) clearTimeout(noticeTimer);
    notice.value = { show: true, text, type };
    noticeTimer = setTimeout(() => {
        notice.value.show = false;
    }, timeout);
}

const lastLocation = ref(null);

const availableTypes = WASTE_TYPES;

function popupHtml(p) {
    const types = (p.waste_types || [])
        .map((t) => `<span style="display:inline-block;background:#E8F5E9;color:#2E7D32;border-radius:10px;padding:1px 8px;margin:2px 2px 0 0;font-size:12px;">${wasteLabel(t)}</span>`)
        .join('');

    const contact = [];
    if (p.contact_phone) contact.push(`📞 ${p.contact_phone}`);
    if (p.contact_email) contact.push(`✉️ ${p.contact_email}`);

    return `
        <div style="min-width:180px;max-width:240px;">
            <strong style="font-size:14px;">${p.name}</strong><br>
            <span style="color:#555;font-size:12px;">${p.address}</span>
            <div style="margin-top:6px;">${types}</div>
            ${contact.length ? `<div style="margin-top:6px;font-size:12px;color:#333;">${contact.join('<br>')}</div>` : ''}
        </div>`;
}

function filteredPoints() {
    if (filters.value.length === 0) return points.value;
    return points.value.filter((p) =>
        (p.waste_types || []).some((t) => filters.value.includes(t)),
    );
}

function renderMarkers() {
    if (!markersLayer) return;
    markersLayer.clearLayers();

    for (const p of filteredPoints()) {
        L.marker([p.latitude, p.longitude])
            .bindPopup(popupHtml(p))
            .addTo(markersLayer);
    }
}

watch(filters, renderMarkers, { deep: true });

function centerOnUser(latlng) {
    map.setView(latlng, 15);
    if (userMarker) map.removeLayer(userMarker);
    userMarker = L.circleMarker(latlng, {
        radius: 8,
        color: '#1565C0',
        fillColor: '#42A5F5',
        fillOpacity: 0.9,
    })
        .addTo(map)
        .bindPopup('Você está aqui');
}

function locateUser({ silent = false } = {}) {
    if (!navigator.geolocation) {
        if (!silent) showNotice('Geolocalização não suportada neste navegador.', 'error');
        return;
    }

    if (lastLocation.value) {
        centerOnUser(lastLocation.value);
    }

    navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
            lastLocation.value = [coords.latitude, coords.longitude];
            centerOnUser(lastLocation.value);
        },
        (err) => {
            if (silent) return;
            if (err.code === err.PERMISSION_DENIED) {
                showNotice(
                    'Acesso à localização negado. Permita o acesso à localização no navegador para usar este recurso.',
                    'warning',
                );
            } else if (err.code === err.TIMEOUT) {
                showNotice('Tempo esgotado ao obter a localização. Tente novamente.', 'error');
            } else if (!lastLocation.value) {
                showNotice(
                    'Não foi possível determinar sua localização. O serviço de localização do navegador não respondeu.',
                    'error',
                );
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    );
}

function onLocate() {
    locateUser();
}

onMounted(async () => {
    map = L.map(mapEl.value, { zoomControl: false }).setView(CENTER, INITIAL_ZOOM);
    L.control.zoom({ position: 'topright' }).addTo(map);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; colaboradores do OpenStreetMap',
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);

    setTimeout(() => map && map.invalidateSize(), 200);

    locateUser({ silent: true });

    try {
        points.value = await getApprovedPoints();
        renderMarkers();
    } catch {
        showNotice('Não foi possível carregar os pontos de coleta.', 'error');
    } finally {
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    if (noticeTimer) clearTimeout(noticeTimer);
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<style scoped>
.map-wrapper {
    position: relative;
    height: calc(100dvh - 64px);
    width: 100%;
}
.map {
    position: absolute;
    inset: 0;
    z-index: 1;
}
.filter-panel {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 1000;
    width: min(340px, calc(100% - 24px));
}
.filter-panel-text {
    max-height: 45vh;
    overflow-y: auto;
}
.locate-btn {
    position: absolute;
    bottom: 24px;
    right: 24px;
    z-index: 1000;
}
.alert-box {
    position: absolute;
    bottom: 24px;
    left: 12px;
    z-index: 1000;
    max-width: min(340px, calc(100% - 24px));
}
</style>
