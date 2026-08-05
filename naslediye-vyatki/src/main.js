import '@/styles/tokens.css'
import '@/styles/base.css'
import { createApp } from 'vue'
import { createYmaps } from 'vue-yandex-maps'
import App from '@/App.vue'
import router from '@/router'
import { YANDEX_MAPS_API_KEY } from '@/config/maps'

createApp(App)
  .use(router)
  .use(createYmaps({ apikey: YANDEX_MAPS_API_KEY }))
  .mount('#app')
