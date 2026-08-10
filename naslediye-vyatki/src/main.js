import '@/styles/tokens.css'
import '@/styles/base.css'
import { createApp } from 'vue'
import { createYmaps } from 'vue-yandex-maps'
import App from '@/App.vue'
import router from '@/router'
import { YANDEX_MAPS_API_KEY } from '@/config/maps'

createApp(App)
  .use(router)
  // initializeOn: 'onPluginInit' — грузить скрипт ymaps3 сразу при старте
  // приложения, а не по умолчанию (onComponentMount) при первом монтировании
  // компонента карты. У API v3 часть внутренней инициализации завязана на
  // DOMContentLoaded: если добавить <script> в <head> уже после этого события
  // (что при переходе на страницу с картой через vue-router всегда так —
  // событие давно прошло), конструкторы вроде YMapDefaultSchemeLayer не
  // успевают проставиться в window.ymaps3, хотя ymaps3.ready всё равно
  // резолвится — "is not a constructor" при первом заходе не на страницу с
  // картой. При прямой загрузке страницы с картой скрипт добавляется
  // достаточно рано (до DOMContentLoaded), поэтому баг не воспроизводился.
  .use(createYmaps({ apikey: YANDEX_MAPS_API_KEY, initializeOn: 'onPluginInit' }))
  .mount('#app')
