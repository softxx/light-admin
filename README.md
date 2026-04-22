<div align="center">

# Light Admin

  <p>
  一个基于 
  <a href="https://github.com/atseps/speed-admin" target="_blank">SpeedAdmin</a> 的后端能力和 <a href="https://github.com/Daymychen/art-design-pro" target="_blank">Art Design Pro</a> 前端特性的现代化后台管理系统
</p>
<p>
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/ThinkPHP-8-0052D9?style=flat-square" alt="ThinkPHP 8" />
  <img src="https://img.shields.io/badge/Vue-3-42B883?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3" />
  <img src="https://img.shields.io/badge/TypeScript-5.6-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript" />
  <img src="https://img.shields.io/badge/License-MIT-111111?style=flat-square" alt="MIT License" />
</p>

</div>

## 介绍

> Light Admin 面向企业管理后台、权限控制、基础资料维护、日志审计和系统配置等常见场景，适合直接作为中后台项目脚手架进行二次开发。

项目采用前后端分层结构：

| 模块       | 路径        | 说明                                                                  |
| ---------- | ----------- | --------------------------------------------------------------------- |
| 后端服务   | `./` 根目录 | 基于 ThinkPHP 8，负责接口服务、权限体系、业务逻辑、系统配置与数据处理 |
| 前端管理台 | `./web_art` | 基于 Vue 3 + TypeScript，负责管理界面、菜单路由、状态管理与交互展示   |

如果你希望快速搭建一套具备权限管理、组织结构管理、日志记录、系统配置以及基础管理界面的后台系统，Light Admin 可以作为一个清晰、轻量且易于扩展的起点。

## 技术栈

| 层级 | 技术方案                                                                                               |
| ---- | ------------------------------------------------------------------------------------------------------ |
| 后端 | PHP 8.2+、ThinkPHP 8、Think ORM 3、JWT                                                                 |
| 前端 | Vue 3、TypeScript、Vite 7、Vue Router 4、Pinia、Element Plus、Tailwind CSS 4、Axios、Vue I18n、ECharts |

## 主要特性

| 能力模块 | 说明                                                                 |
| -------- | -------------------------------------------------------------------- |
| 权限体系 | 提供 RBAC 权限管理能力，支持角色、菜单、权限控制等后台核心功能       |
| 基础管理 | 内置用户、角色、部门、字典等常见后台基础模块                         |
| 日志审计 | 支持登录日志、操作日志等审计能力，便于问题追踪与运维排查             |
| 系统配置 | 提供系统设置相关能力，便于统一管理平台基础配置                       |
| 前端工程 | 提供独立的前端管理端工程，支持前后端分层开发与部署                   |
| 页面能力 | 前端基于 Vue 3 + Element Plus 构建，适合快速扩展后台页面与业务模块   |
| 常用扩展 | 支持状态管理、国际化、图表展示、富文本编辑、文件处理等常见管理端能力 |
| 架构设计 | 采用服务层与模型层分离方式，目录清晰，便于维护与业务沉淀             |

## 目录结构

```text
light-admin/
├─ app/                 后端应用目录
├─ config/              后端配置目录
├─ core/                核心扩展与公共能力
├─ public/              Web 入口目录
├─ route/               路由定义
├─ sql/                 数据库脚本
├─ web_art/             前端管理台工程
│  ├─ src/
│  │  ├─ api/           接口封装
│  │  ├─ components/    公共组件
│  │  ├─ router/        路由配置
│  │  ├─ store/         状态管理
│  │  ├─ utils/         工具方法
│  │  └─ views/         页面视图
│  └─ package.json      前端依赖与脚本
├─ composer.json        后端依赖定义
└─ README.md
```

## 快速开始

### 后端准备

```bash
composer install
php think run
```

后端启动前建议完成以下准备：

- 配置 `.env` 与数据库连接信息
- 导入 `sql/` 目录中的初始化脚本

### 前端开发

```bash
cd web_art
pnpm install
pnpm dev
```

### 前端构建

```bash
cd web_art
pnpm build
```

## 页面模块

当前前端工程已包含以下典型后台页面分区：

- `auth`：登录与认证相关页面
- `dashboard`：工作台与概览面板
- `system`：系统管理类页面
- `exception`：异常页与错误页面
- `portal`、`outside`、`index`：门户页、外部页面与基础入口页面

## 许可证

本项目采用 [MIT License](./LICENSE) 开源许可证，详细内容请查看 [LICENSE](./LICENSE) 文件。

## 特别鸣谢

排名不分先后

- [SpeedAdmin](https://github.com/atseps/speed-admin)
- [Art Design Pro](https://github.com/Daymychen/art-design-pro)
- [Thinkphp](http://www.thinkphp.cn/)
- [Vue](https://github.com/vuejs/core)
- [Element Plus](https://github.com/element-plus/element-plus)
- [TypeScript](https://github.com/microsoft/TypeScript)
- [vue-router](https://github.com/vuejs/vue-router-next)
- [vite](https://github.com/vitejs/vite)
- [Pinia](https://github.com/vuejs/pinia)
- [Axios](https://github.com/axios/axios)
- [echarts](https://github.com/apache/echarts)
- [eslint](https://github.com/eslint/eslint)
