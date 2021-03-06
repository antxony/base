/**
* @packageDocumentation
* @module User
*/
import { DEFAULT_DELETE_REACTIVE_OPTIONS, DeleteReactiveOptions } from "@scripts/user/defs";
import Alert from "@plugins/Alert";
import { deleteElement, disableRow, restoreRow } from "@plugins/DeleteElement";
import Axios from "axios";
import { Router, ROUTES } from "@scripts/app";
import Toast from "@plugins/AlertToast";

/**
 * Open a confirmation alerto to suspend an user
 *
 * @export
 * @class Delete
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Delete {

    protected options: DeleteReactiveOptions;

    public constructor (options: DeleteReactiveOptions) {
        this.options = { ...DEFAULT_DELETE_REACTIVE_OPTIONS, ...options };
        this.options.id = this.options.id || +this.options.element.getAttribute("id")!;
        this.options.name = this.options.name || this.options.element.getAttribute("name")!;
        this.options.username = this.options.username || this.options.element.getAttribute("username")!;
    }

    public delete = async () => {
        const ALERT = new Alert({
            type: 'danger',
            typeText: 'Alerta'
        });
        let res = await ALERT.updateBody(`¿Suspender a <b>${this.options.name}</b>(${this.options.username})?`).show();
        if (res) {
            const BTNS_BEF = disableRow(this.options.element);
            try {
                const res = await Axios.delete(
                    Router.generate(ROUTES.user.api.delete, { 'id': this.options.id!.toString() })
                );
                Toast.success(res.data);
                deleteElement(this.options.element);
                this.options.onSuccess!();
            } catch (err) {
                const e = err.response ? err.response.data : err;
                console.error(e);
                Toast.error(e);
                restoreRow(this.options.element, BTNS_BEF);
                this.options.onError!();
            }
        }
    };

    public reactive = async () => {
        const ALERT = new Alert({
            type: 'danger',
            typeText: 'Alerta'
        });
        let res = await ALERT.updateBody(`¿Reactivar a <b>${this.options.name}</b>(${this.options.username})?`).show();
        if (res) {
            const BTNS_BEF = disableRow(this.options.element);
            try {
                const res = await Axios.patch(
                    Router.generate(ROUTES.user.api.reactive, { 'id': this.options.id!.toString() })
                );
                Toast.success(res.data);
                deleteElement(this.options.element);
                this.options.onSuccess!();
            } catch (err) {
                const e = err.response ? err.response.data : err;
                console.error(e);
                Toast.error(e);
                restoreRow(this.options.element, BTNS_BEF);
                this.options.onError!();
            }
        }
    };
}
