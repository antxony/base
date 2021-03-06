/**
* @packageDocumentation
* @module Schedule/Priority
*/

import { SchedulePriorityChangeOptions, DEFAULT_PRIORITY_CHANGE_OPTIONS } from "@scripts/schedulePriority/defs";
import Modal from "@plugins/Modal";
import Axios from "axios";
import { BIG_LOADER, Router, ROUTES } from "@scripts/app";
import ListSelect from "@plugins/ListSelect";
import Alert from "@plugins/Alert";
import Toast from "@plugins/AlertToast";

/**
 * Change a client category
 *
 * @export
 * @class Change
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Change {

    protected options: SchedulePriorityChangeOptions;

    protected modal: Modal;

    protected list: HTMLElement;

    public constructor (options: SchedulePriorityChangeOptions) {
        this.options = { ...DEFAULT_PRIORITY_CHANGE_OPTIONS, ...options };
        this.list = document.createElement("div") as HTMLElement;
        this.modal = new Modal({
            title: "Cambiar de prioridad",
            size: 50,
            onHide: this.options.onClose
        });
        if (this.options.idSchedule == 0) {
            throw new Error("No se ha podido identificar la tarea");
        }
    }

    public load = async () => {
        this.modal.show();
        try {
            const res = await Axios.get(
                Router.generate(ROUTES.schedulePriority.view.changeForm, { 'id': this.options.idSchedule })
            );
            this.modal.updateBody(res.data);
            this.list = document.getElementById('categoryList') as HTMLElement;
            $('[data-toggle="tooltip"]').tooltip();
            this.startEvents();
        } catch (err) {
            this.modal.hide();
            throw new Error(err.response ? err.response.data : err);
        }
    };

    private listchange = async (data: string[]) => {
        if (Array.isArray(data) && data.length) {
            const LIST_BEF = this.list.innerHTML;
            this.list.innerHTML = BIG_LOADER;
            const res = await (new Alert({
                typeText: "Cuidado",
                type: "warning",
            }))
                .updateBody("¿Seguro que desea cambiar la prioridad?")
                .show();
            if (res) {
                try {
                    await Axios.patch(
                        Router.generate(ROUTES.schedulePriority.api.update),
                        {
                            scheduleId: this.options.idSchedule,
                            categoryId: data[ 0 ],
                        }
                    );
                    this.modal.hide();
                } catch (err) {
                    const e = err.response ? err.response.data : err;
                    console.error(e);
                    Toast.error(e);
                }
            }
            this.list.innerHTML = LIST_BEF;
            this.startEvents();
        }
    };

    private startEvents = () => {
        (new ListSelect({
            element: this.list,
            multiple: false,
            attribute: 'priority-id',
            callback: this.listchange
        })).load();
    };
}
