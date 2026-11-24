import DefaultTheme from 'vitepress/theme'
import type { Theme } from 'vitepress'
import Diagram from './Diagram.vue'
import './custom.css'

export default {
  extends: DefaultTheme,
  enhanceApp({ app }) {
    app.component('Diagram', Diagram)
  },
} satisfies Theme
