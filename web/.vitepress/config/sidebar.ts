import { DefaultTheme } from "vitepress"

export default [
  {
    text: '概述',
    collapsed: false,
    items: [
      { text: '更新记录', link: '/docs/v1/overview/versions' },
      { text: '版本计划', link: '/docs/v1/overview/planning' },
      { text: '捐赠', link: '/docs/v1/overview/donate' },
    ]
  },
  {
    text: '快速入门',
    collapsed: false,
    items: [
      { text: '安装', link: '/docs/v1/quick-start/install' },
      { text: '编写请求插件', link: '/docs/v1/quick-start/writing' },
      { text: '初始化', link: '/docs/v1/quick-start/init' },
      { text: '使用插件', link: '/docs/v1/quick-start/requesting' },
      { text: '返回格式', link: '/docs/v1/quick-start/return-format' }
    ]
  },
  {
    text: '核心架构',
    collapsed: false,
    items: [
      { text: '🚀 Rocket', link: '/docs/v1/kernel/rocket' },
      { text: '🧪 Pipeline', link: '/docs/v1/kernel/pipeline' },
      { text: '🔌 Plugin', link: '/docs/v1/kernel/plugin' },
      { text: '💤 Shortcut', link: '/docs/v1/kernel/shortcut' }
    ]
  },
  {
    text: '其它',
    collapsed: false,
    items: [
      { text: '事件', link: '/docs/v1/others/event' },
      { text: '日志', link: '/docs/v1/others/logger' },
    ]
  },
  {
    text: '升级指南',
    collapsed: false,
    items: [
      { text: 'v1.2.0', link: '/docs/v1/upgrade/v1.2.0' },
    ]
  },
  {
    text: '附录',
    collapsed: false,
    items: [
      { text: '内置特殊参数', link: '/docs/v1/appendix/special-params' },
    ]
  },
] as DefaultTheme.Sidebar
