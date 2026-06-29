import type { CSSProperties } from 'vue'

export type DictTagType = 'primary' | 'success' | 'warning' | 'danger' | 'info'

export interface DictColorPreset {
  label: string
  value: string
  hex: string
}

export interface DictTagColorProps {
  type?: DictTagType
  color?: string
  style?: CSSProperties
}

const DICT_TAG_TYPE_MAP: Record<string, DictTagType> = {
  primary: 'primary',
  success: 'success',
  warning: 'warning',
  danger: 'danger',
  info: 'info',
  blue: 'primary',
  green: 'success',
  red: 'danger',
  yellow: 'warning',
  orange: 'warning',
  gray: 'info',
  grey: 'info'
}

export const DEFAULT_DICT_COLOR = '#409EFF'

export const DICT_COLOR_PRESETS: DictColorPreset[] = [
  { label: '蓝色', value: 'blue', hex: '#409EFF' },
  { label: '绿色', value: 'green', hex: '#67C23A' },
  { label: '红色', value: 'red', hex: '#F56C6C' },
  { label: '黄色', value: 'yellow', hex: '#E6A23C' },
  { label: '灰色', value: 'gray', hex: '#909399' },
  { label: '主色', value: 'primary', hex: '#409EFF' },
  { label: '成功', value: 'success', hex: '#67C23A' },
  { label: '警告', value: 'warning', hex: '#E6A23C' },
  { label: '危险', value: 'danger', hex: '#F56C6C' },
  { label: '信息', value: 'info', hex: '#909399' }
]

export const DICT_COLOR_PICKER_PRESETS = Array.from(
  new Set(DICT_COLOR_PRESETS.map((item) => item.hex))
)

const DICT_COLOR_PICKER_HEX_MAP: Record<string, string> = {
  ...Object.fromEntries(DICT_COLOR_PRESETS.map((item) => [item.value, item.hex])),
  orange: '#E6A23C',
  grey: '#909399'
}

const HEX_COLOR_PATTERN = /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/

export const normalizeDictColor = (color?: unknown) => String(color ?? '').trim()

export const resolveDictTagType = (color?: unknown): DictTagType | '' => {
  const normalized = normalizeDictColor(color).toLowerCase()

  return DICT_TAG_TYPE_MAP[normalized] || ''
}

export const isHexDictColor = (color?: unknown) => HEX_COLOR_PATTERN.test(normalizeDictColor(color))

export const isValidDictColor = (color?: unknown) => {
  const normalized = normalizeDictColor(color)

  return normalized === '' || Boolean(resolveDictTagType(normalized)) || isHexDictColor(normalized)
}

export const normalizeHexDictColor = (color: string) => {
  const normalized = normalizeDictColor(color)
  if (normalized.length !== 4) {
    return normalized.toUpperCase()
  }

  const [, r, g, b] = normalized

  return `#${r}${r}${g}${g}${b}${b}`.toUpperCase()
}

export const resolveDictColorPickerValue = (color?: unknown) => {
  const normalized = normalizeDictColor(color)

  if (isHexDictColor(normalized)) {
    return normalizeHexDictColor(normalized)
  }

  return DICT_COLOR_PICKER_HEX_MAP[normalized.toLowerCase()] || DEFAULT_DICT_COLOR
}

const getReadableTextColor = (color: string) => {
  const hex = normalizeHexDictColor(color).slice(1)
  const red = Number.parseInt(hex.slice(0, 2), 16)
  const green = Number.parseInt(hex.slice(2, 4), 16)
  const blue = Number.parseInt(hex.slice(4, 6), 16)
  const brightness = (red * 299 + green * 587 + blue * 114) / 1000

  return brightness > 165 ? '#1f2937' : '#ffffff'
}

export const resolveDictTagColorProps = (color?: unknown): DictTagColorProps => {
  const normalized = normalizeDictColor(color)
  const type = resolveDictTagType(normalized)

  if (type) {
    return { type }
  }

  if (isHexDictColor(normalized)) {
    const hex = normalizeHexDictColor(normalized)

    return {
      color: hex,
      style: {
        color: getReadableTextColor(hex),
        borderColor: hex
      }
    }
  }

  return {}
}
