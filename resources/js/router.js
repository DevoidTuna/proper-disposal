import { createRouter, createWebHistory } from 'vue-router';
import MapView from './views/MapView.vue';
import AdminView from './views/AdminView.vue';

const routes = [
    { path: '/', name: 'map', component: MapView },
    { path: '/admin', name: 'admin', component: AdminView },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
