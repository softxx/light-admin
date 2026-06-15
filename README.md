<div align="center">

# Light Admin

<p>
  <strong>English</strong> ·
  <a href="./README.zh-CN.md">简体中文</a>
</p>

<p>
  A modern admin dashboard system that combines the backend capabilities of
  <a href="https://github.com/atseps/speed-admin" target="_blank">SpeedAdmin</a>
  with the frontend features of
  <a href="https://github.com/Daymychen/art-design-pro" target="_blank">Art Design Pro</a>.
</p>
<p>
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/ThinkPHP-8-0052D9?style=flat-square" alt="ThinkPHP 8" />
  <img src="https://img.shields.io/badge/Vue-3-42B883?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3" />
  <img src="https://img.shields.io/badge/TypeScript-5.6-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript" />
  <img src="https://img.shields.io/badge/License-MIT-111111?style=flat-square" alt="MIT License" />
</p>

</div>

## Introduction

> Light Admin is designed for common enterprise admin scenarios, including management dashboards, access control, master data maintenance, audit logs, and system configuration. It can be used directly as a scaffold for building admin and back-office applications.

The project uses a layered frontend and backend architecture:

| Module          | Path           | Description                                                                                             |
| --------------- | -------------- | ------------------------------------------------------------------------------------------------------- |
| Backend service | `./` root      | Built with ThinkPHP 8; handles API services, permissions, business logic, system settings, and data     |
| Admin frontend  | `./web_art`    | Built with Vue 3 + TypeScript; handles admin pages, menu routes, state management, and user interaction |

If you need to quickly build a backend system with permission management, organization management, logs, system settings, and common admin pages, Light Admin provides a clear, lightweight, and extensible starting point.

## Tech Stack

| Layer    | Technologies                                                                                             |
| -------- | -------------------------------------------------------------------------------------------------------- |
| Backend  | PHP 8.2+, ThinkPHP 8, Think ORM 3, JWT                                                                   |
| Frontend | Vue 3, TypeScript, Vite 7, Vue Router 4, Pinia, Element Plus, Tailwind CSS 4, Axios, Vue I18n, ECharts   |

## Key Features

| Feature Area       | Description                                                                                 |
| ------------------ | ------------------------------------------------------------------------------------------- |
| Access control     | Provides RBAC capabilities for roles, menus, permissions, and other core admin requirements |
| Core management    | Includes common admin modules such as users, roles, departments, and dictionaries           |
| Audit logs         | Supports login logs and operation logs for troubleshooting and operational auditing          |
| System settings    | Provides platform configuration capabilities for centralized system management               |
| Frontend project   | Includes an independent admin frontend project for layered development and deployment        |
| Page development   | Built with Vue 3 + Element Plus, making it suitable for extending admin and business pages   |
| Common extensions  | Supports state management, internationalization, charts, rich text editing, and file tools   |
| Architecture       | Separates service and model layers with a clear directory structure for long-term maintenance |

## Directory Structure

```text
light-admin/
├─ app/                 Backend application directory
├─ config/              Backend configuration directory
├─ core/                Core extensions and shared capabilities
├─ public/              Web entry directory
├─ route/               Route definitions
├─ sql/                 Database scripts
├─ web_art/             Admin frontend project
│  ├─ src/
│  │  ├─ api/           API wrappers
│  │  ├─ components/    Shared components
│  │  ├─ router/        Route configuration
│  │  ├─ store/         State management
│  │  ├─ utils/         Utility functions
│  │  └─ views/         Page views
│  └─ package.json      Frontend dependencies and scripts
├─ composer.json        Backend dependency definition
└─ README.md
```

## Quick Start

### Backend Setup

```bash
composer install
php think run
```

Before starting the backend, complete the following setup:

- Configure `.env` and database connection settings
- Import the initialization scripts from the `sql/` directory

### Frontend Development

```bash
cd web_art
pnpm install
pnpm dev
```

### Frontend Build

```bash
cd web_art
pnpm build
```

## Page Modules

The frontend project currently includes these typical admin page areas:

- `auth`: Login and authentication pages
- `dashboard`: Workspace and overview dashboards
- `system`: System management pages
- `exception`: Exception and error pages
- `portal`, `outside`, `index`: Portal pages, external pages, and base entry pages

## License

This project is open-sourced under the [MIT License](./LICENSE). See the [LICENSE](./LICENSE) file for details.

## Credits

In no particular order:

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
