import './bootstrap';
import * as bootstrap from 'bootstrap';
import TomSelect from 'tom-select';

// Preserve the global Bootstrap API used by existing Blade views.
window.bootstrap = bootstrap;
window.TomSelect = TomSelect;
