<template>
    <v-container v-if="!authenticated" class="d-flex justify-center py-12">
        <v-card width="380" class="pa-2" rounded elevation="6">
            <v-card-title class="d-flex align-center">
                <v-icon icon="mdi-lock" class="mr-2" /> Login Administrador
            </v-card-title>
            <v-card-text>
                <v-form v-model="formValid" @submit.prevent="handleLogin">
                    <v-text-field
                        v-model="credentials.username"
                        label="Usuário"
                        :rules="[(v) => !!v || 'Campo obrigatório.']"
                        prepend-inner-icon="mdi-account"
                        variant="outlined"
                        density="comfortable"
                        color="primary"
                        autofocus
                    />
                    <v-text-field
                        v-model="credentials.password"
                        label="Senha"
                        type="password"
                        :rules="[(v) => !!v || 'Campo obrigatório.']"
                        color="primary"
                        prepend-inner-icon="mdi-key"
                        variant="outlined"
                        density="comfortable"
                    />
                    <v-alert v-if="loginError" type="error" density="compact" class="mb-3">
                        {{ loginError }}
                    </v-alert>
                    <v-btn type="submit" color="primary" block size="large" :loading="loggingIn">Entrar</v-btn>
                </v-form>
            </v-card-text>
        </v-card>
    </v-container>

    <v-container v-else class="py-6">
        <div class="d-flex align-center mb-4">
            <h2 class="text-h5">
                <v-icon icon="mdi-clipboard-check-outline" class="mr-1" />
                Painel Administrativo
            </h2>
            <v-spacer />
            <v-btn variant="text" prepend-icon="mdi-refresh" :loading="loading" @click="load">
                {{ $vuetify.display.smAndDown ? '' : 'Atualizar' }}
            </v-btn>
            <v-btn variant="text" prepend-icon="mdi-logout" @click="handleLogout">Sair</v-btn>
        </div>

        <v-card>
            <v-tabs v-model="tab" color="primary">
                <v-tab value="pending">
                    Pendentes
                    <v-chip v-if="pending.length" size="x-small" color="warning" class="ml-2">
                        {{ pending.length }}
                    </v-chip>
                </v-tab>
                <v-tab value="all">Todos os pontos ({{ points.length }})</v-tab>
            </v-tabs>
            <v-divider />

            <v-window v-model="tab">
                <v-window-item value="pending">
                    <v-data-table
                        :headers="pendingHeaders"
                        :items="pending"
                        :loading="loading"
                        no-data-text="Nenhum ponto pendente. 🎉"
                        items-per-page="10"
                    >
                        <template #[`item.waste_types`]="{ item }">
                            <v-chip
                                v-for="t in item.waste_types"
                                :key="t"
                                size="x-small"
                                class="ma-1"
                                color="primary"
                                variant="tonal"
                            >
                                {{ wasteLabel(t) }}
                            </v-chip>
                        </template>
                        <template #[`item.actions`]="{ item }">
                            <v-btn
                                color="success"
                                variant="flat"
                                size="small"
                                prepend-icon="mdi-check"
                                class="mr-1"
                                :loading="approvingId === item.id"
                                @click="approve(item)"
                            >
                                Aprovar
                            </v-btn>
                            <v-btn
                                icon="mdi-pencil"
                                variant="text"
                                size="small"
                                @click="edit(item)"
                            />
                        </template>
                    </v-data-table>
                </v-window-item>

                <v-window-item value="all">
                    <v-data-table
                        :headers="allHeaders"
                        :items="points"
                        :loading="loading"
                        no-data-text="Nenhum ponto cadastrado."
                        items-per-page="25"
                    >
                        <template #[`item.status`]="{ item }">
                            <v-chip
                                :color="item.status === 'approved' ? 'success' : 'warning'"
                                size="small"
                                variant="flat"
                            >
                                {{ item.status === 'approved' ? 'Aprovado' : 'Pendente' }}
                            </v-chip>
                        </template>
                        <template #[`item.waste_types`]="{ item }">
                            <v-chip
                                v-for="t in item.waste_types"
                                :key="t"
                                size="x-small"
                                class="ma-1"
                                color="primary"
                                variant="tonal"
                            >
                                {{ wasteLabel(t) }}
                            </v-chip>
                        </template>
                        <template #[`item.actions`]="{ item }">
                            <div class="d-flex">
                                <v-btn
                                    icon="mdi-pencil"
                                    variant="text"
                                    size="small"
                                    color="primary"
                                    @click="edit(item)"
                                />
                                <v-btn
                                    icon="mdi-delete"
                                    variant="text"
                                    size="small"
                                    color="error"
                                    @click="confirmDelete(item)"
                                />
                            </div>
                        </template>
                    </v-data-table>
                </v-window-item>
            </v-window>
        </v-card>

        <PointFormDialog v-model="editDialog" :point="editingPoint" @updated="onEditSaved" />

        <v-dialog v-model="deleteDialog" max-width="420">
            <v-card>
                <v-card-title>Excluir ponto</v-card-title>
                <v-card-text>
                    Tem certeza que deseja excluir
                    <strong>{{ pointToDelete?.name }}</strong>? Esta ação não pode ser desfeita.
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" :disabled="deleting" @click="deleteDialog = false">Cancelar</v-btn>
                    <v-btn color="error" variant="flat" :loading="deleting" @click="removePoint">Excluir</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000">
            {{ snackbar.text }}
            <template #actions>
                <v-btn variant="text" @click="snackbar.show = false">Fechar</v-btn>
            </template>
        </v-snackbar>
    </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    isAuthenticated,
    login,
    logout,
    getAllPoints,
    approvePoint,
    deletePoint,
} from '../api';
import { wasteLabel } from '../wasteTypes';
import PointFormDialog from '../components/PointFormDialog.vue';

const authenticated = ref(isAuthenticated());

const credentials = ref({ username: '', password: '' });
const loggingIn = ref(false);
const loginError = ref('');
const formValid = ref(null);

async function handleLogin() {
    if (!formValid.value) return;
    loginError.value = '';
    loggingIn.value = true;
    try {
        await login(credentials.value.username, credentials.value.password);
        authenticated.value = true;
        credentials.value = { username: '', password: '' };
        await load();
    } catch (e) {
        loginError.value = e.body?.message || 'Não foi possível entrar.';
    } finally {
        loggingIn.value = false;
    }
}

async function handleLogout() {
    await logout();
    authenticated.value = false;
    points.value = [];
}

const points = ref([]);
const loading = ref(false);
const approvingId = ref(null);
const tab = ref('pending');
const snackbar = ref({ show: false, text: '', color: 'success' });

const pending = computed(() => points.value.filter((p) => p.status === 'pending'));

function notify(text, color = 'success') {
    snackbar.value = { show: true, text, color };
}

async function load() {
    loading.value = true;
    try {
        points.value = await getAllPoints();
    } catch (e) {
        if (e.status === 401) {
            authenticated.value = false;
        } else {
            notify('Erro ao carregar os pontos.', 'error');
        }
    } finally {
        loading.value = false;
    }
}

async function approve(item) {
    approvingId.value = item.id;
    try {
        await approvePoint(item.id);
        await load();
        notify(`"${item.name}" aprovado e publicado no mapa.`);
    } catch (e) {
        if (e.status === 401) authenticated.value = false;
        else notify('Não foi possível aprovar o ponto.', 'error');
    } finally {
        approvingId.value = null;
    }
}

const editDialog = ref(false);
const editingPoint = ref(null);

function edit(item) {
    editingPoint.value = item;
    editDialog.value = true;
}

async function onEditSaved() {
    editDialog.value = false;
    await load();
    notify('Ponto atualizado.');
}

const deleteDialog = ref(false);
const pointToDelete = ref(null);
const deleting = ref(false);

function confirmDelete(item) {
    pointToDelete.value = item;
    deleteDialog.value = true;
}

async function removePoint() {
    deleting.value = true;
    try {
        await deletePoint(pointToDelete.value.id);
        deleteDialog.value = false;
        await load();
        notify('Ponto excluído.');
    } catch (e) {
        if (e.status === 401) authenticated.value = false;
        else notify('Não foi possível excluir o ponto.', 'error');
    } finally {
        deleting.value = false;
    }
}

const pendingHeaders = [
    { title: 'Nome', key: 'name' },
    { title: 'Endereço', key: 'address' },
    { title: 'Resíduos', key: 'waste_types', sortable: false },
    { title: 'Ações', key: 'actions', sortable: false, align: 'center' },
];

const allHeaders = [
    { title: 'Nome', key: 'name' },
    { title: 'Endereço', key: 'address' },
    { title: 'Status', key: 'status' },
    { title: 'Resíduos', key: 'waste_types', sortable: false },
    { title: 'Ações', key: 'actions', sortable: false, align: 'center' },
];

onMounted(() => {
    if (authenticated.value) load();
});
</script>
