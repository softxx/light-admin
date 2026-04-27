/**
 * 离线图标加载器
 *
 * 用于在内网环境下支持 Iconify 图标的离线加载。
 * 图标数据由 scripts/generate-iconify-icons.mjs 从源码、SQL 初始化菜单和额外白名单中抽取，
 * 避免把完整 @iconify-json/<collection>/icons.json 打进首屏主包。
 *
 * 使用方式：
 * 1. 在组件中使用：<ArtSvgIcon icon="ri:[icon-name]" />
 * 2. 后端动态菜单新增图标时，优先确保 install.sql 中有初始化数据
 * 3. 无法从源码或 install.sql 扫描到的动态图标，补充到 scripts/iconify-extra-icons.json
 *
 * @module utils/ui/iconify-loader
 * @author Art Design Pro Team
 */

import { addCollection } from '@iconify/vue'
import { iconCollections } from './iconify-icons.generated'

// 注册项目实际使用到的精简离线图标集。
iconCollections.forEach((collection) => {
  addCollection(collection)
})
