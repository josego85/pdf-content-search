import './styles/app.css';
import { createApp } from 'vue';
import SearchComponent from './components/SearchComponent.vue';

const app = createApp({
    components: {
        'search-component': SearchComponent
    }
});

app.mount('#app');