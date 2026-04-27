import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const scriptDir = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(scriptDir, '..')
const repoRoot = path.resolve(projectRoot, '..')

const iconPackages = {
  ri: 'ri',
  vaadin: 'vaadin',
  'ant-design': 'ant-design',
  iconamoon: 'iconamoon',
  'system-uicons': 'system-uicons'
}

const iconPrefixes = Object.keys(iconPackages)
const iconSets = new Map(iconPrefixes.map((prefix) => [prefix, new Set()]))
const iconPattern = new RegExp(
  `(?:${iconPrefixes.map((prefix) => prefix.replace('-', '\\-')).join('|')}):[a-z0-9][a-z0-9-]*`,
  'g'
)

const sourceExtensions = new Set(['.vue', '.ts', '.tsx', '.js', '.jsx', '.json'])
const extraIconsPath = path.join(scriptDir, 'iconify-extra-icons.json')
const generatedPath = path.join(projectRoot, 'src/utils/ui/iconify-icons.generated.ts')

function walkFiles(dir, files = []) {
  if (!fs.existsSync(dir)) {
    return files
  }

  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const entryPath = path.join(dir, entry.name)

    if (entry.isDirectory()) {
      walkFiles(entryPath, files)
      continue
    }

    if (sourceExtensions.has(path.extname(entry.name))) {
      files.push(entryPath)
    }
  }

  return files
}

function addIcon(iconName, source) {
  const [prefix, name] = iconName.split(':')

  if (!iconSets.has(prefix) || !name) {
    throw new Error(`Unsupported Iconify icon "${iconName}" from ${source}`)
  }

  iconSets.get(prefix).add(name)
}

function stripComments(content) {
  return content
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/(^|[^:])\/\/.*$/gm, '$1')
}

function collectExplicitIconsFromSource() {
  // 扫描源码里直接写出的 Iconify 图标，例如 ri:home-line。
  for (const file of walkFiles(path.join(projectRoot, 'src'))) {
    if (path.resolve(file) === path.resolve(generatedPath)) {
      continue
    }

    const content = stripComments(fs.readFileSync(file, 'utf8'))
    for (const match of content.matchAll(iconPattern)) {
      addIcon(match[0], file)
    }
  }
}

function parseSqlValues(text) {
  const values = []
  let current = ''
  let inQuote = false
  let escaped = false

  for (const char of text) {
    if (inQuote) {
      if (escaped) {
        current += char
        escaped = false
        continue
      }

      if (char === '\\') {
        escaped = true
        continue
      }

      if (char === "'") {
        inQuote = false
        continue
      }

      current += char
      continue
    }

    if (char === "'") {
      inQuote = true
      continue
    }

    if (char === ',') {
      values.push(current.trim())
      current = ''
      continue
    }

    current += char
  }

  values.push(current.trim())
  return values
}

function collectBackendMenuIcons() {
  // 后端菜单 icon 可能没有前缀，前端会按 auth.ts 的逻辑补成 ant-design:*。
  const installSqlPath = path.join(repoRoot, 'sql/install.sql')

  if (!fs.existsSync(installSqlPath)) {
    return
  }

  const content = fs.readFileSync(installSqlPath, 'utf8')

  for (const line of content.split(/\r?\n/)) {
    if (!line.includes('INSERT INTO `light_menu`')) {
      continue
    }

    const valuesMatch = line.match(/VALUES\s*\((.*)\);?$/)
    if (!valuesMatch) {
      continue
    }

    const values = parseSqlValues(valuesMatch[1])
    const icon = values[6]

    if (!icon) {
      continue
    }

    addIcon(icon.includes(':') ? icon : `ant-design:${icon}`, installSqlPath)
  }
}

function collectExtraIcons() {
  // 数据库里运行时新增的图标无法被静态扫描到，可在这里维护兜底白名单。
  if (!fs.existsSync(extraIconsPath)) {
    return
  }

  const extraIcons = JSON.parse(fs.readFileSync(extraIconsPath, 'utf8'))
  if (!Array.isArray(extraIcons)) {
    throw new Error(`${extraIconsPath} must be a JSON array of full icon names`)
  }

  for (const iconName of extraIcons) {
    addIcon(iconName, extraIconsPath)
  }
}

function copyIcon(sourceCollection, targetCollection, iconName, stack = []) {
  if (targetCollection.icons[iconName] || targetCollection.aliases?.[iconName]) {
    return
  }

  const icon = sourceCollection.icons?.[iconName]
  if (icon) {
    targetCollection.icons[iconName] = icon
    return
  }

  const alias = sourceCollection.aliases?.[iconName]
  if (alias) {
    // Iconify 的别名需要连同 parent 一起保留，否则运行时无法解析。
    if (stack.includes(iconName)) {
      throw new Error(`Circular Iconify alias detected: ${[...stack, iconName].join(' -> ')}`)
    }

    targetCollection.aliases ??= {}
    targetCollection.aliases[iconName] = alias
    copyIcon(sourceCollection, targetCollection, alias.parent, [...stack, iconName])
    return
  }

  throw new Error(`Icon "${sourceCollection.prefix}:${iconName}" was not found`)
}

function readCollection(prefix) {
  const packageName = iconPackages[prefix]
  const iconsPath = path.join(projectRoot, 'node_modules/@iconify-json', packageName, 'icons.json')

  if (!fs.existsSync(iconsPath)) {
    throw new Error(`Missing Iconify collection: ${iconsPath}`)
  }

  return JSON.parse(fs.readFileSync(iconsPath, 'utf8'))
}

function buildCollection(prefix, iconNames) {
  const sourceCollection = readCollection(prefix)
  const targetCollection = {
    prefix,
    icons: {}
  }

  for (const key of ['width', 'height', 'left', 'top']) {
    if (sourceCollection[key] !== undefined) {
      targetCollection[key] = sourceCollection[key]
    }
  }

  for (const iconName of [...iconNames].sort()) {
    copyIcon(sourceCollection, targetCollection, iconName)
  }

  if (targetCollection.aliases && Object.keys(targetCollection.aliases).length === 0) {
    delete targetCollection.aliases
  }

  return targetCollection
}

function writeGeneratedFile(collections) {
  // 生成的文件参与生产构建，避免运行时再读取完整图标库。
  const content = `// Generated by scripts/generate-iconify-icons.mjs. Do not edit manually.\nimport type { IconifyJSON } from '@iconify/vue'\n\nexport const iconCollections: IconifyJSON[] = ${JSON.stringify(collections, null, 2)}\n`
  fs.writeFileSync(generatedPath, content)
}

collectExplicitIconsFromSource()
collectBackendMenuIcons()
collectExtraIcons()

const collections = iconPrefixes
  .map((prefix) => buildCollection(prefix, iconSets.get(prefix)))
  .filter((collection) => Object.keys(collection.icons).length > 0)

writeGeneratedFile(collections)

const summary = collections
  .map((collection) => {
    const iconCount =
      Object.keys(collection.icons).length + Object.keys(collection.aliases ?? {}).length
    return `${collection.prefix}:${iconCount}`
  })
  .join(', ')

console.log(`Generated ${path.relative(projectRoot, generatedPath)} (${summary})`)
