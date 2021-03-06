/**
* @packageDocumentation
* @module Schedule/Priority
*/
import '@scripts/jquery';
import Modal from "@plugins/Modal";
import { SchedulePriorityShowOptions, DEFAULT_PRIORITY_SHOW_OPTIONS } from "@scripts/schedulePriority/defs";
import Axios from "axios";
import { ROUTES, Router } from "@scripts/app";
import Toast from "@plugins/AlertToast";

/**
 * Show the priority and allows to edit
 *
 * @export
 * @class Show
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Show {

    protected modal: Modal;

    protected options: SchedulePriorityShowOptions;

    public constructor (options: SchedulePriorityShowOptions) {
        this.options = { ...DEFAULT_PRIORITY_SHOW_OPTIONS, ...options };
        this.modal = new Modal({
            title: 'Prioridad',
            size: 30
        });
    }

    public load = async () => {
        this.modal.show();
        try {
            const res = await Axios.get(
                Router.generate(ROUTES.schedulePriority.view.show, { 'id': this.options.idPriority.toString() })
            );
            this.modal.updateBody(res.data);
            $('.editable-field').editable({
                success: (res: any) => {
                    Toast.success(res);
                    this.options.onClose();
                },
                error: (err: any) => {
                    console.error(err.responseText);
                    Toast.error(err.responseText);
                },
                disabled: false
            });
        } catch (err) {
            const e = err.response ? err.response.data : err;
            console.error(e);
            Toast.error(e);
        }
    };
}
