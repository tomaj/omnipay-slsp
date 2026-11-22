import { defineConfig } from 'vitepress'

const description = 'SporoPay (Slovenská sporiteľňa) gateway for the Omnipay payment processing library'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Omnipay SporoPay',
  description,
  base: '/omnipay-slsp/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/omnipay-slsp/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#2870ed' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Omnipay SporoPay' }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:url', content: 'https://tomaj.github.io/omnipay-slsp/' }],
  ],

  lastUpdated: true,

  sitemap: {
    hostname: 'https://tomaj.github.io/omnipay-slsp/',
  },

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/reference' },
      { text: 'FAQ', link: '/faq' },
      {
        text: '4.x',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Upgrade from 3.x', link: 'https://github.com/tomaj/omnipay-slsp/blob/master/UPGRADE-4.0.md' },
          { text: 'Releases', link: 'https://github.com/tomaj/omnipay-slsp/releases' },
        ],
      },
    ],

    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Getting started', link: '/guide/getting-started' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Operations', link: '/guide/operations' },
          { text: 'Signatures', link: '/guide/signatures' },
          { text: 'Test mode', link: '/guide/test-mode' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API reference', link: '/api/reference' },
          { text: 'FAQ', link: '/faq' },
          { text: 'Changelog', link: '/changelog' },
          { text: 'Contributing', link: '/contributing' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/tomaj/omnipay-slsp' },
    ],

    editLink: {
      pattern: 'https://github.com/tomaj/omnipay-slsp/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2016-2026 Tomas Majer',
    },
  },
})
