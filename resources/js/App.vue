<template>
    <v-app>
        <v-navigation-drawer v-if="mobile" v-model="drawer" temporary>
            <v-list nav>
                <v-list-item
                    :to="{ name: 'map' }"
                    prepend-icon="mdi-map-marker-radius"
                    title="Mapa"
                    @click="drawer = false"
                />
                <v-list-item
                    :to="{ name: 'admin' }"
                    prepend-icon="mdi-clipboard-check-outline"
                    title="Equipe"
                    @click="drawer = false"
                />
                <v-divider class="my-2" />
                <v-list-item
                    prepend-icon="mdi-plus-circle"
                    title="Sugerir ponto"
                    base-color="primary"
                    @click="openPointDialog"
                />
            </v-list>
        </v-navigation-drawer>

        <v-app-bar color="primary" flat>
            <template v-if="mobile">
                <v-app-bar-nav-icon @click="drawer = !drawer" />
                <v-spacer />
                <div class="text-h6 font-weight-bold">♻️ EcoDescarte</div>
                <v-spacer />
                <div class="nav-spacer" />
            </template>

            <template v-else>
                <v-app-bar-title>
                    <span class="font-weight-bold">♻️ EcoDescarte</span>
                </v-app-bar-title>

                <div class="d-flex ga-2">
                    <v-btn :to="{ name: 'map' }" variant="outlined" prepend-icon="mdi-map-marker-radius">
                        Mapa
                    </v-btn>
                    <v-btn :to="{ name: 'admin' }" variant="outlined" prepend-icon="mdi-clipboard-check-outline">
                        Equipe
                    </v-btn>
                    <v-btn
                        class="ml-2 mr-2"
                        color="white"
                        variant="flat"
                        prepend-icon="mdi-plus-circle"
                        @click="pointDialog = true"
                    >
                        Sugerir ponto
                    </v-btn>
                </div>
            </template>
        </v-app-bar>

        <v-main>
            <router-view />
        </v-main>

        <PointFormDialog v-model="pointDialog" @created="onPointCreated" />

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="bottom">
            {{ snackbar.text }}
            <template #actions>
                <v-btn variant="text" @click="snackbar.show = false">Fechar</v-btn>
            </template>
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { useDisplay } from 'vuetify';
import PointFormDialog from './components/PointFormDialog.vue';

const { smAndDown: mobile } = useDisplay();

const drawer = ref(false);
const pointDialog = ref(false);
const snackbar = ref({ show: false, text: '', color: 'success' });

function notify(text, color = 'success') {
    snackbar.value = { show: true, text, color };
}

function openPointDialog() {
    drawer.value = false;
    pointDialog.value = true;
}

function onPointCreated() {
    pointDialog.value = false;
    notify('Ponto enviado para validação da equipe. Obrigado! 🙌');
}
</script>

<style scoped>
.nav-spacer {
    width: 48px;
}
</style>
