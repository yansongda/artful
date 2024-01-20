import { h } from 'vue'
import DefaultTheme from 'vitepress/theme'
import HomeAuthorize from '@components/Home/Authorize.vue'

export default {
  ...DefaultTheme,
  Layout() {
    return h(DefaultTheme.Layout, null, {
      'home-features-after': () => h(HomeAuthorize),
    })
  }
}
