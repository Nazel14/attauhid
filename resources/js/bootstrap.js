import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Kirim cookie sesi + baca cookie XSRF-TOKEN otomatis.
// Cookie XSRF-TOKEN ditulis ulang Laravel di setiap response, jadi token yang
// dipakai axios SELALU sinkron dengan sesi terbaru — beda dengan
// <meta name="csrf-token"> yang membeku sejak page load pertama.
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true; // wajib di axios 1.x saat withCredentials aktif