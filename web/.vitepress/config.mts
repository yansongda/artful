import { defineConfig } from 'vitepress'
import nav from './config/nav'
import sidebar from "./config/sidebar"

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'zh-CN',
  title: "Artful",
  description: "Api RequesT Framework U Like - 你喜欢的 API 请求框架",
  lastUpdated: true,
  themeConfig: {
    nav: nav,
    sidebar: sidebar,
    socialLinks: [
      { icon: 'github', link: 'https://github.com/yansongda/artful' }
    ],
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024-present yansongda'
    },
  }
})
