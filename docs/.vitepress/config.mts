import { defineConfig, type DefaultTheme } from 'vitepress'
import { seoHead, siteUrl } from './seo'

const description = 'SporoPay (Slovenská sporiteľňa, Slovakia) gateway for the Omnipay PHP payment library: 3DES-signed payment requests, verified returns, test mode'
const descriptionSk = 'Brána SporoPay (Slovenská sporiteľňa) pre PHP knižnicu Omnipay: platobné požiadavky podpísané 3DES, overené návraty, testovací režim'
const base = '/omnipay-slsp/'

// Slovak mirror under /sk/: the same pages and order as the English nav and sidebar below.
const navSk: DefaultTheme.NavItem[] = [
  { text: 'Príručka', link: '/sk/guide/getting-started', activeMatch: '/sk/guide/' },
  { text: 'Príklady', link: '/sk/examples/', activeMatch: '/sk/examples/' },
  { text: 'API', link: '/sk/api/reference' },
  { text: 'Časté otázky', link: '/sk/faq' },
  {
    text: '4.x',
    items: [
      { text: 'Zoznam zmien', link: '/sk/changelog' },
      { text: 'Prechod z 3.x', link: '/sk/upgrade' },
      { text: 'Vydania', link: 'https://github.com/tomaj/omnipay-slsp/releases' },
      { text: 'Packagist', link: 'https://packagist.org/packages/tomaj/omnipay-slsp' },
    ],
  },
]

const sidebarSk: DefaultTheme.SidebarItem[] = [
  {
    text: 'Úvod',
    items: [
      { text: 'Začíname', link: '/sk/guide/getting-started' },
      { text: 'Priebeh platby', link: '/sk/guide/payment-flow' },
    ],
  },
  {
    text: 'Príručka',
    items: [
      { text: 'Konfigurácia', link: '/sk/guide/configuration' },
      { text: 'Začatie platby', link: '/sk/guide/purchase' },
      { text: 'Návratová URL', link: '/sk/guide/complete-purchase' },
      { text: 'Podpisy', link: '/sk/guide/signatures' },
      { text: 'Testovací režim', link: '/sk/guide/test-mode' },
      { text: 'Spracovanie chýb', link: '/sk/guide/errors' },
      { text: 'Testovanie integrácie', link: '/sk/guide/testing' },
      { text: 'Bezpečnosť', link: '/sk/guide/security' },
    ],
  },
  {
    text: 'Príklady',
    items: [
      { text: 'Prehľad', link: '/sk/examples/' },
      { text: 'Presmerovanie na platbu', link: '/sk/examples/purchase-redirect' },
      { text: 'Návratová URL', link: '/sk/examples/return-url' },
      { text: 'Formáty sumy', link: '/sk/examples/amount-formats' },
      { text: 'Podpisy cez openssl', link: '/sk/examples/signatures' },
      { text: 'Celý priebeh s presmerovaním', link: '/sk/examples/full-flow' },
    ],
  },
  {
    text: 'Referencia',
    items: [
      { text: 'Referencia API', link: '/sk/api/reference' },
      { text: 'Časté otázky', link: '/sk/faq' },
      { text: 'Prechod z 3.x', link: '/sk/upgrade' },
      { text: 'Zoznam zmien', link: '/sk/changelog' },
      { text: 'Ako prispieť', link: '/sk/contributing' },
    ],
  },
]

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'omnipay-slsp',
  description,
  base,
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
    return seoHead(pageData, siteConfig.srcDir, pageData.relativePath.startsWith('sk/') ? descriptionSk : description)
  },

  // English is the default locale at the site root; Slovak mirrors every page under /sk/.
  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      description,
    },
    sk: {
      label: 'Slovenčina',
      lang: 'sk-SK',
      link: '/sk/',
      description: descriptionSk,
      themeConfig: {
        nav: navSk,
        sidebar: sidebarSk,
        outline: { level: [2, 3], label: 'Na tejto stránke' },
        docFooter: {
          prev: 'Predchádzajúca stránka',
          next: 'Nasledujúca stránka',
        },
        editLink: {
          pattern: 'https://github.com/tomaj/omnipay-slsp/edit/master/docs/:path',
          text: 'Upraviť túto stránku na GitHube',
        },
        lastUpdated: {
          text: 'Naposledy upravené',
        },
        darkModeSwitchLabel: 'Vzhľad',
        lightModeSwitchTitle: 'Prepnúť na svetlý režim',
        darkModeSwitchTitle: 'Prepnúť na tmavý režim',
        sidebarMenuLabel: 'Ponuka',
        returnToTopLabel: 'Späť na začiatok',
        langMenuLabel: 'Zmeniť jazyk',
        skipToContentLabel: 'Preskočiť na obsah',
        footer: {
          message: 'Vydané pod <a href="https://github.com/tomaj/omnipay-slsp/blob/master/LICENSE.md">licenciou MIT</a>. Nie je prepojené so Slovenskou sporiteľňou, a.s.',
          copyright: 'Copyright © 2016-2026 Tomas Majer',
        },
        notFound: {
          code: '404',
          title: 'STRÁNKA SA NENAŠLA',
          quote: 'Táto stránka neexistuje alebo sa presunula. Skúste vyhľadávanie alebo začnite od úvodu.',
          linkLabel: 'prejsť na úvod',
          linkText: 'Späť na úvod',
        },
      },
    },
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
      options: {
        locales: {
          sk: {
            translations: {
              button: {
                buttonText: 'Hľadať',
                buttonAriaLabel: 'Hľadať',
              },
              modal: {
                displayDetails: 'Zobraziť podrobný zoznam',
                resetButtonTitle: 'Vymazať hľadanie',
                backButtonTitle: 'Zavrieť hľadanie',
                noResultsText: 'Žiadne výsledky pre',
                footer: {
                  selectText: 'vybrať',
                  selectKeyAriaLabel: 'enter',
                  navigateText: 'prejsť',
                  navigateUpKeyAriaLabel: 'šípka hore',
                  navigateDownKeyAriaLabel: 'šípka dole',
                  closeText: 'zavrieť',
                  closeKeyAriaLabel: 'escape',
                },
              },
            },
          },
        },
      },
    },


    footer: {
      message: 'Released under the <a href="https://github.com/tomaj/omnipay-slsp/blob/master/LICENSE.md">MIT License</a>. Not affiliated with Slovenská sporiteľňa, a.s.',
      copyright: 'Copyright © 2016-2026 Tomas Majer',
    },
  },
})
