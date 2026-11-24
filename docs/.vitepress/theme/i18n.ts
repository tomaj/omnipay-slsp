// Strings that the custom theme components render. Pages carry their own text;
// these are the labels inside components shared by the English and Slovak trees.
import { computed } from 'vue'
import { useData } from 'vitepress'

const en = {
  // Suffix of the generated SVG files: diagrams/<name><suffix>-light.svg
  svgSuffix: '',
  fullSize: '(full size)',
}

export type Strings = typeof en

const sk: Strings = {
  svgSuffix: '-sk',
  fullSize: '(plná veľkosť)',
}

export function stringsFor(lang: string): Strings {
  return lang.startsWith('sk') ? sk : en
}

export function useStrings() {
  const { lang } = useData()
  return computed(() => stringsFor(lang.value))
}
