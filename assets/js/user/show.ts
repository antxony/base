/**
* @packageDocumentation
* @module User
*/
import { DEFAULT_EDIT_OPTIONS, EditOptions } from "@scripts/user/defs";
import Modal from "@plugins/Modal";
import Axios from "axios";
import { Router, ROUTES } from "@scripts/app";
import Toast from "@plugins/AlertToast";
import '@scripts/jquery';

/**
 * Show an user and edit
 *
 * @export
 * @class Show
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Show {

    protected options: EditOptions;

    protected modal: Modal;

    public constructor (options: EditOptions) {
        this.options = { ...DEFAULT_EDIT_OPTIONS, ...options };
        if (this.options.id === 0) {
            throw new Error("No se ha podido determinar al usuario");
        }
        this.modal = new Modal({
            title: 'Usuario',
            size: 50
        });
    }

    public load = async () => {
        this.modal.show();
        try {
            const res = await Axios.get(Router.generate(ROUTES.user.view.show, { 'id': this.options.id.toString() }));
            this.modal.updateBody(res.data);
            $('.editable-field').editable({
                success: (res: any) => {
                    Toast.success(res);
                    this.options.callback!();
                },
                error: (err: any) => {
                    console.error(err.responseText);
                    Toast.error(err.responseText);
                },
                disabled: true
            });
            document.getElementById("user-edit-active")!.addEventListener("click", this.toggle);
        } catch (err) {
            const e = err.response ? err.response.data : err;
            console.error(e);
            Toast.error(e);
        }
    };

    private toggle = (e: Event) => {
        const BTN = (e.currentTarget as HTMLElement);
        if (!!(+BTN.getAttribute("active")!)) {
            BTN.setAttribute("active", "0");
            BTN.classList.remove("btn-danger");
            BTN.classList.add("btn-warning");
            BTN.innerHTML = "Editar";
        } else {
            BTN.setAttribute("active", "1");
            BTN.classList.remove("btn-warning");
            BTN.classList.add("btn-danger");
            BTN.innerHTML = "Cancelar";
        }
        $('.editable-field').editable('toggleDisabled');
    };
}
