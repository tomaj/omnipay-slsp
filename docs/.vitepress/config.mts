import { defineConfig } from 'vitepress'
import { seoHead, siteUrl } from './seo'

const description = 'SporoPay (Slovenská sporiteľňa, Slovakia) gateway for the Omnipay PHP payment library: 3DES-signed payment requests, verified returns, test mode'
const base = '/omnipay-slsp/'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'omnipay-slsp',
  description,
  base,
  lang: 'en-US',
  cleanUrls: false,

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: `${base}favicon.svg` }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '32x32', href: `${base}favicon-32.png` }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '16x16', href: `${base}favicon-16.png` }],
    ['link', { rel: 'alternate icon', href: `${base}favicon.ico` }],
    ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: `${base}apple-touch-icon.png` }],
    ['link', { rel: 'manifest', href: `${base}site.webmanifest` }],
    ['meta', { name: 'theme-color', content: '#1f5fd6' }],
  ],

  lastUpdated: true,

  sitemap: {
    hostname: siteUrl,
  },

  transformHead({ pageData, siteConfig }) {
    return seoHead(pageData, siteConfig.srcDir, description)
  },

  themeConfig: {
    logo: {
      light: '/logo.svg',
      dark: '/logo-dark.svg',
      alt: 'omnipay-slsp',
    },

    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'Examples', link: '/examples/', activeMatch: '/examples/' },
      { text: 'API', link: '/api/reference' },
      { text: 'FAQ', link: '/faq' },
      {
        text: '4.x',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Upgrade from 3.x', link: '/upgrade' },
          { text: 'Releases', link: 'https://github.com/tomaj/omnipay-slsp/releases' },
          { text: 'Packagist', link: 'https://packagist.org/packages/tomaj/omnipay-slsp' },
        ],
      },
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'Getting started', link: '/guide/getting-started' },
          { text: 'The payment flow', link: '/guide/payment-flow' },
        ],
      },
      {
        text: 'Guide',
        items: [
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Starting a payment', link: '/guide/purchase' },
          { text: 'The return URL', link: '/guide/complete-purchase' },
          { text: 'Signatures', link: '/guide/signatures' },
          { text: 'Test mode', link: '/guide/test-mode' },
          { text: 'Error handling', link: '/guide/errors' },
          { text: 'Testing your integration', link: '/guide/testing' },
          { text: 'Security', link: '/guide/security' },
        ],
      },
      {
        text: 'Examples',
        items: [
          { text: 'Overview', link: '/examples/' },
          { text: 'Payment redirect', link: '/examples/purchase-redirect' },
          { text: 'Return URL', link: '/examples/return-url' },
          { text: 'Amount formats', link: '/examples/amount-formats' },
          { text: 'Signatures with openssl', link: '/examples/signatures' },
          { text: 'Full redirect flow', link: '/examples/full-flow' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API reference', link: '/api/reference' },
          { text: 'FAQ', link: '/faq' },
          { text: 'Upgrade from 3.x', link: '/upgrade' },
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

    lastUpdated: {
      text: 'Last updated',
    },

    outline: {
      level: [2, 3],
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the <a href="https://github.com/tomaj/omnipay-slsp/blob/master/LICENSE.md">MIT License</a>. Not affiliated with Slovenská sporiteľňa, a.s.',
      copyright: 'Copyright © 2016-2026 Tomas Majer',
    },
  },
})
