// Копирует прод-сборку (dist/) в папку темы WordPress
// (wp-theme/naslediye-vyatki-theme/dist), откуда её отдаёт functions.php
// темы. Запускать после `vite build` — см. скрипт `build:theme` в package.json.

import { existsSync, rmSync, mkdirSync, readdirSync, copyFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const rootDir = path.dirname(path.dirname(fileURLToPath(import.meta.url)))
const srcDir = path.join(rootDir, 'dist')
const destDir = path.join(rootDir, 'wp-theme', 'naslediye-vyatki-theme', 'dist')

if (!existsSync(srcDir)) {
  console.error('dist/ не найден — сначала выполните `npm run build`.')
  process.exit(1)
}

// Копируем вручную (mkdir + readdir + copyFile), а не fs.cpSync: на Windows
// рекурсивный fs.cpSync периодически падает с EIO/Access denied при создании
// вложенных директорий (похоже на Controlled Folder Access), а поштучные
// файловые операции этой проблемы не имеют.
function copyDir(src, dest) {
  mkdirSync(dest, { recursive: true })
  for (const entry of readdirSync(src, { withFileTypes: true })) {
    const srcPath = path.join(src, entry.name)
    const destPath = path.join(dest, entry.name)
    if (entry.isDirectory()) {
      copyDir(srcPath, destPath)
    } else {
      copyFileSync(srcPath, destPath)
    }
  }
}

// Чистим прошлую сборку в теме: у файлов Vite content-hash в имени,
// без очистки в папке темы копились бы устаревшие чанки.
rmSync(destDir, { recursive: true, force: true })
copyDir(srcDir, destDir)

console.log(`dist/ скопирован в ${path.relative(rootDir, destDir)}`)
