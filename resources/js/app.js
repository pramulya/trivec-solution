import './bootstrap';
import { createApp } from 'vue';
import ExampleComponent from './components/ExampleComponent.vue';
import SmsInbox from './components/SmsInbox.vue';
import MailDashboard from './components/MailDashboard.vue';

// Initialize Vue
const app = createApp({});
app.component('example-component', ExampleComponent);
app.component('sms-inbox', SmsInbox);
app.component('mail-dashboard', MailDashboard);
app.mount('#app');

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
