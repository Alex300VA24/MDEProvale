require('./bootstrap');

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Chart.js global
import Chart from 'chart.js/auto';
window.Chart = Chart;

// SweetAlert2 global
import Swal from 'sweetalert2';
window.Swal = Swal;

// jQuery global
import $ from 'jquery';
window.$ = window.jQuery = $;

// Select2 (requiere jQuery)
import 'select2';

// Avisos flotantes, modal de confirmación y filtros dinámicos (ver docs en cada archivo)
import './toast';
import './confirm';
import './filter-loading';
import './live-filter';
