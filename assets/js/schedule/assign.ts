/**
* @packageDocumentation
* @module Schedule
*/
import { BIG_LOADER, Router, ROUTES } from "@scripts/app";
import Alert from "@scripts/plugins/Alert";
import Toast from "@scripts/plugins/AlertToast";
import ListSelect from "@scripts/plugins/ListSelect";
import Modal from "@scripts/plugins/Modal";
import Axios from "axios";

import { DEFAULT_SCHEDULE_ASIGN_OPTIONS, ScheduleAsignOptions, TASK_EDIT_TYPE } from "./defs";

export default class Assign {

    protected options: ScheduleAsignOptions;

    protected modal: Modal;

    protected list: HTMLElement;

    protected reasign: boolean;

    public constructor (options: ScheduleAsignOptions) {
        this.options = { ...DEFAULT_SCHEDULE_ASIGN_OPTIONS, ...options };
        this.list = document.createElement("div") as HTMLElement;
        this.reasign = false;
        if (this.options.id == 0) {
            throw new Error("No se pudo identificar la tarea");
        }
        this.modal = new Modal({
            title: "Asignar tarea",
            size: 40,
            onHide: this.options.callback
        });
    }

    public asign = async (reasign = false) => {
        this.reasign = reasign;
        this.modal.show();
        try {
            const res = await Axios.get(Router.generate(ROUTES.schedule.view.asign));
            this.modal.updateBody(res.data);
            this.list = document.getElementById('userList') as HTMLElement;
            this.startEvents();
        } catch (err) {
            const e = err.response ? err.response.data : err;
            console.error(e);
            Toast.error(e);
            this.modal.hide();
        }
    };

    private listAsign = async (data: string[]) => {
        if (Array.isArray(data) && data.length) {
            let msgText: string;
            if (this.reasign) {
                msgText = "reasignar";
            } else {
                msgText = "asignar";
            }
            const LIST_BEF = this.list.innerHTML;
            this.list.innerHTML = BIG_LOADER;
            const res = await (new Alert({
                typeText: msgText.charAt(0).toUpperCase() + msgText.slice(1),
                type: "info",
            }))
                .updateBody(`¿Seguro que desea <b>${msgText}</b> el usuario a la tarea?`)
                .show();
            if (res) {
                try {
                    await Axios.patch(
                        Router.generate(ROUTES.schedule.api.update),
                        {
                            id: this.options.id,
                            value: data[ 0 ],
                            type: TASK_EDIT_TYPE.ASING,
                        }
                    );
                    this.modal.hide();
                } catch (err) {
                    const e = err.response ? err.response.data : err;
                    console.error(e);
                    Toast.error(e);
                }
                this.list.innerHTML = LIST_BEF;
                this.startEvents();
            } else {
                this.list.innerHTML = LIST_BEF;
                this.startEvents();
            }
        }
    };

    private startEvents = () => {
        (new ListSelect({
            element: this.list,
            multiple: false,
            attribute: 'user-id',
            callback: this.listAsign
        })).load();
    };
}
