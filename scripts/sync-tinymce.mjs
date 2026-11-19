import { cp, mkdir, rm } from 'node:fs/promises';
import { resolve } from 'node:path';

const source = resolve('node_modules/tinymce');
const destination = resolve('public/tinymce/js/tinymce');
await rm(destination, { recursive: true, force: true });
await mkdir(destination, { recursive: true });
await cp(source, destination, { recursive: true });
console.log('Installed the locked TinyMCE package for self-hosting.');

const translationDir = resolve('resources/tinymce/langs');
await cp(translationDir, resolve(destination, 'langs'), { recursive: true });
const calendarFolder = resolve('public/vendor/fullcalendar');
await mkdir(calendarFolder, { recursive: true });
await cp(resolve('node_modules/fullcalendar/index.global.min.js'), resolve(calendarFolder, 'index.global.min.js'));
console.log('Installed a locally hosted FullCalendar bundle.');
