import 'bootstrap';
import 'x-editable/dist/bootstrap3-editable/css/bootstrap-editable.css';
import 'x-editable/dist/bootstrap3-editable/js/bootstrap-editable';
/* ------------------ Configuración para bootstrap-editable ----------------- */
//@ts-ignore
$.fn.editable.defaults.ajaxOptions = { type: "PATCH" };
// @ts-ignore
$.fn.editable.defaults.mode = 'inline';
// @ts-ignore
$.fn.editable.defaults.emptytext = 'Vacío';
// @ts-ignore
$.fn.editable.defaults.onblur = 'ignore';
// @ts-ignore
$.fn.editable.defaults.send = 'always';
/* ------------------------------------ . ----------------------------------- */