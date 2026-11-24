// Per-page SEO: title, canonical URL, Open Graph and Twitter tags, hreflang alternates and JSON-LD. Used by transformHead in config.mts.
import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import type { HeadConfig, PageData } from 'vitepress'

export const siteUrl = 'https://tomaj.github.io/omnipay-slsp/'
export const ogImage = `${siteUrl}og.png`
// Slovak pages get the social card with the Slovak tagline.
export const ogImageSk = `${siteUrl}og-sk.png`
const siteName = 'omnipay-slsp'
const repoUrl = 'https://github.com/tomaj/omnipay-slsp'
const author = { '@type': 'Person', '@id': `${siteUrl}#tomaj`, name: 'Tomas Majer', url: 'https://github.com/tomaj' }

export function pageUrl(relativePath: string): string {
  return siteUrl + relativePath.replace(/(^|\/)index\.md$/, '$1').replace(/\.md$/, '.html')
}

// English pages live at the site root, Slovak pages mirror them under sk/.
export function localeOf(relativePath: string): { prefix: string, lang: string, ogLocale: string } {
  return relativePath.startsWith('sk/')
    ? { prefix: 'sk/', lang: 'sk-SK', ogLocale: 'sk_SK' }
    : { prefix: '', lang: 'en-US', ogLocale: 'en_US' }
}

// The same page in the other locale, when it exists, for hreflang links.
export function alternates(relativePath: string, srcDir: string): { lang: string, href: string }[] {
  const { prefix } = localeOf(relativePath)
  const base = relativePath.slice(prefix.length)
  const pairs: [string, string][] = [['en', base], ['sk', `sk/${base}`]]
  const found = pairs.filter(([, path]) => existsSync(resolve(srcDir, path)))
  if (found.length < 2) return []
  return [
    ...found.map(([lang, path]) => ({ lang, href: pageUrl(path) })),
    { lang: 'x-default', href: pageUrl(base) },
  ]
}

// Markdown to plain text, enough for JSON-LD answers.
function plain(markdown: string): string {
  return markdown
    .replace(/```[\s\S]*?```/g, '')
    .replace(/^<<< .*$/gm, '')
    .replace(/<[^>]+>/g, '')
    .replace(/!\[[^\]]*\]\([^)]*\)/g, '')
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/[`*_]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

// Every `##` heading of faq.md is a question.
function faqEntries(source: string): { q: string, a: string }[] {
  return ('\n' + source).split('\n## ').slice(1)
    .map((part) => {
      const [q, ...rest] = part.split('\n')
      return { q: plain(q), a: plain(rest.join('\n')) }
    })
    .filter((e) => e.q && e.a)
}

export function seoHead(page: PageData, srcDir: string, siteDescription: string): HeadConfig[] {
  if (page.isNotFound || !page.relativePath) return []
  const url = pageUrl(page.relativePath)
  const locale = localeOf(page.relativePath)
  const sk = locale.prefix === 'sk/'
  const isHome = page.frontmatter.layout === 'home'
  const image = sk ? ogImageSk : ogImage
  const homeTitle = sk ? 'omnipay-slsp - SporoPay pre Omnipay' : 'omnipay-slsp - SporoPay for Omnipay'
  const title = isHome ? homeTitle : `${page.title} | ${siteName}`
  const description = page.description || page.frontmatter.description || siteDescription
  const modified = page.lastUpdated ? new Date(page.lastUpdated).toISOString() : undefined

  const head: HeadConfig[] = [
    ['link', { rel: 'canonical', href: url }],
    ['meta', { property: 'og:type', content: isHome ? 'website' : 'article' }],
    ['meta', { property: 'og:site_name', content: siteName }],
    ['meta', { property: 'og:locale', content: locale.ogLocale }],
    ['meta', { property: 'og:locale:alternate', content: sk ? 'en_US' : 'sk_SK' }],
    ['meta', { property: 'og:title', content: title }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:url', content: url }],
    ['meta', { property: 'og:image', content: image }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:image:alt', content: sk ? 'omnipay-slsp: SporoPay (Slovenská sporiteľňa) pre Omnipay' : 'omnipay-slsp: SporoPay (Slovenská sporiteľňa) for Omnipay' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: title }],
    ['meta', { name: 'twitter:description', content: description }],
    ['meta', { name: 'twitter:image', content: image }],
    ['meta', { name: 'author', content: author.name }],
  ]
  if (modified && !isHome) head.push(['meta', { property: 'article:modified_time', content: modified }])
  for (const alt of alternates(page.relativePath, srcDir)) {
    head.push(['link', { rel: 'alternate', hreflang: alt.lang, href: alt.href }])
  }

  const graph: Record<string, unknown>[] = [
    author,
    { '@type': 'WebSite', '@id': `${siteUrl}#website`, name: siteName, url: siteUrl, inLanguage: ['en-US', 'sk-SK'], publisher: { '@id': author['@id'] } },
  ]
  if (isHome) {
    graph.push({
      '@type': 'SoftwareSourceCode',
      '@id': `${siteUrl}#software`,
      name: 'tomaj/omnipay-slsp',
      description: siteDescription,
      url: siteUrl + locale.prefix,
      inLanguage: locale.lang,
      codeRepository: repoUrl,
      programmingLanguage: { '@type': 'ComputerLanguage', name: 'PHP' },
      runtimePlatform: 'PHP 8.4',
      license: 'https://opensource.org/licenses/MIT',
      author: { '@id': author['@id'] },
      image,
    })
  } else {
    graph.push({
      '@type': 'TechArticle',
      '@id': `${url}#article`,
      headline: page.title,
      description,
      url,
      inLanguage: locale.lang,
      image,
      author: { '@id': author['@id'] },
      isPartOf: { '@id': `${siteUrl}#website` },
      ...(modified ? { dateModified: modified } : {}),
    })
  }
  if (page.relativePath === 'faq.md' || page.relativePath === 'sk/faq.md') {
    const faq = faqEntries(readFileSync(resolve(srcDir, page.relativePath), 'utf8'))
    graph.push({
      '@type': 'FAQPage',
      '@id': `${url}#faq`,
      mainEntity: faq.map((e) => ({ '@type': 'Question', name: e.q, acceptedAnswer: { '@type': 'Answer', text: e.a } })),
    })
  }
  head.push(['script', { type: 'application/ld+json' }, JSON.stringify({ '@context': 'https://schema.org', '@graph': graph })])
  return head
}
