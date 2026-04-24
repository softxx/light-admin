/**
 * 离线图标加载器
 *
 * 用于在内网环境下支持 Iconify 图标的离线加载。
 * 通过预加载图标集数据，避免运行时从 CDN 获取图标。
 *
 * 使用方式：
 * 1. 安装所需图标集：pnpm add -D @iconify-json/[icon-set-name]
 * 2. 在此文件中导入并注册图标集
 * 3. 在组件中使用：<ArtSvgIcon icon="ri:home-line" />
 *
 * @module utils/ui/iconify-loader
 * @author Art Design Pro Team
 */

import { addCollection } from '@iconify/vue'

// // 导入离线图标数据

// // 系统必要图标库
import riIcons from '@iconify-json/ri/icons.json'

// // 演示图标库（可选，生产环境可移除）
// import svgSpinners from '@iconify-json/svg-spinners/icons.json'
// import lineMd from '@iconify-json/line-md/icons.json'

import vaadinIcons from '@iconify-json/vaadin/icons.json'
import antDesign from '@iconify-json/ant-design/icons.json'
import iconamoonIcons from '@iconify-json/iconamoon/icons.json'
import systemUicons from '@iconify-json/system-uicons/icons.json'

// // 注册离线图标集

addCollection(riIcons)
addCollection(vaadinIcons)
addCollection(antDesign)
addCollection(iconamoonIcons)
addCollection(systemUicons)
// addCollection(svgSpinners)
// addCollection(lineMd)
