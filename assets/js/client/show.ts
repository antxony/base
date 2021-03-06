/**
* @packageDocumentation
* @module Client
*/
import { DEFAULT_SHOW_OPTIONS, ShowOptions } from "@scripts/client/defs";
import Modal from "@plugins/Modal";
import Axios from "axios";
import { Router, ROUTES, SPINNER_LOADER } from "@scripts/app";
import $ from "jquery";
import Change from "@scripts/clientCategory/change";
import Toast from "@plugins/AlertToast";
import { evaluateInputs, hideElement, showElement } from "@plugins/Required";
import Alert from "@plugins/Alert";
import { deleteElement, disableRow } from "@plugins/DeleteElement";
import AddAddress from "@scripts/client/address/add";
import AddContact from "@scripts/client/contact/add";
import ShowContact from "@scripts/client/contact/show";
import '@scripts/jquery';

/**
 * Opens client modal with the info and actions
 *
 * @export
 * @class Show
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Show {

    protected options: ShowOptions;

    protected modal: Modal;

    protected control: boolean;

    public constructor (options: ShowOptions) {
        this.options = { ...DEFAULT_SHOW_OPTIONS, ...options };
        this.control = true;
        if (this.options.id === 0) {
            throw new Error("No se pudo obtener información del cliente");
        }
        this.modal = new Modal({
            title: 'Cliente',
            size: 90,
            onHide: this.options.callback
        });
    }

    public load = () => {
        if (this.control) {
            this.control = false;
            this.modal.setAllowCallback(true);
            this.modal.show();
            this.update();
        }
        document.getElementById("client-edit-active")?.addEventListener("click", this.edit);
        document.getElementById("address-add")?.addEventListener("click", this.addAddress);
        document.getElementById("contact-add")?.addEventListener("click", this.addContact);
        Array.from(document.getElementsByClassName("contact-delete")).forEach(el => el.addEventListener('click', this.deleteContact));
        Array.from(document.getElementsByClassName("contact-show")).forEach(el => el.addEventListener('click', this.showContact));
        document.getElementById("client-phone-extra-form")?.addEventListener("submit", this.addExtraPhone);
        document.getElementById("client-email-extra-form")?.addEventListener("submit", this.addExtraEmail);
        Array.from(document.getElementsByClassName("trash-phone")).forEach(el => el.addEventListener('click', this.deletePhone));
        Array.from(document.getElementsByClassName("trash-email")).forEach(el => el.addEventListener('click', this.deleteEmail));
        document.getElementById("changeClientCategory")?.addEventListener("click", () => {
            this.modal.setAllowCallback(false);
            this.modal.hide();
            (new Change({
                idClient: this.options.id,
                onClose: () => {
                    // this.options.callback();
                    this.control = true;
                    this.load();
                }
            })).load();
        });
    };

    private update = async () => {
        try {
            const res = await Axios.get(
                Router.generate(ROUTES.client.view.show, { 'id': this.options.id!.toString() })
            );
            this.modal.updateBody(res.data);
            $('[data-toggle="tooltip"]').tooltip();
            $('.editable-field').editable({
                success: (_res: string) => {
                    Toast.success(_res);
                    this.options.callback();
                },
                error: (_err: any) => {
                    console.error(_err.responseText);
                    Toast.error(_err.responseText);
                },
                disabled: true
            });
            this.load();
        } catch (err) {
            const e = err.response ? err.response.data : err;
            console.error(e);
            Toast.error(e);
        }
    };

    private edit = (e: Event) => {
        const BTN = (e.currentTarget as HTMLElement);
        if (!!(+BTN.getAttribute("active")!)) {
            BTN.setAttribute("active", "0");
            BTN.classList.remove("btn-danger");
            BTN.classList.add("btn-warning");
            BTN.innerHTML = "Editar Cliente";
            Array.from(document.getElementsByClassName("trash-email")).forEach(el => hideElement(el as HTMLElement));
            Array.from(document.getElementsByClassName("trash-phone")).forEach(el => hideElement(el as HTMLElement));
            Array.from(document.getElementsByClassName("trash-contact")).forEach(el => hideElement(el as HTMLElement));
            Array.from(document.getElementsByClassName("plus-btn")).forEach(el => hideElement(el as HTMLElement));
            $('#email-extra-container').collapse('hide');
            $('#phone-extra-container').collapse('hide');
        } else {
            BTN.setAttribute("active", "1");
            BTN.classList.remove("btn-warning");
            BTN.classList.add("btn-danger");
            BTN.innerHTML = "Cancelar";
            Array.from(document.getElementsByClassName("trash-email")).forEach(el => showElement(el as HTMLElement));
            Array.from(document.getElementsByClassName("trash-phone")).forEach(el => showElement(el as HTMLElement));
            Array.from(document.getElementsByClassName("trash-contact")).forEach(el => showElement(el as HTMLElement));
            Array.from(document.getElementsByClassName("plus-btn")).forEach(el => showElement(el as HTMLElement));
        }
        $('.editable-field').editable('toggleDisabled');
    };

    private addAddress = () => {
        this.modal.setAllowCallback(false);
        this.modal.hide();
        this.control = true;
        (new AddAddress({
            id: this.options.id,
            callback: this.load
        })).load();
    };

    private addContact = () => {
        this.modal.setAllowCallback(false);
        this.modal.hide();
        this.control = true;
        (new AddContact({
            id: this.options.id,
            callback: this.load
        })).load();
    };

    private addExtraPhone = async (e: Event) => {
        e.preventDefault();
        if (evaluateInputs([ document.getElementById("phone") as HTMLInputElement ], 1, false)) {
            const BTN = document.getElementById("submit-phone-extra-btn")!;
            const BEF = BTN.innerHTML;
            BTN.innerHTML = SPINNER_LOADER;

            try {
                const res = await Axios.post(
                    Router.generate(ROUTES.client.extra.api.add, { 'id': this.options.id.toString() }),
                    {
                        type: 2,
                        level: (document.getElementById("phoneType") as HTMLInputElement).value,
                        value: (document.getElementById("phone") as HTMLInputElement).value,
                    }
                );
                Toast.success(res.data);
                this.update();
            } catch (err) {
                const e = err.response ? err.response.data : err;
                console.error(e);
                Toast.error(e);
                BTN.innerHTML = BEF;
            }
        }
    };

    private addExtraEmail = async (e: Event) => {
        e.preventDefault();
        if (evaluateInputs([ document.getElementById("email") as HTMLInputElement ], 1, false)) {
            const BTN = document.getElementById("submit-email-extra-btn")!;
            const BEF = BTN.innerHTML;
            BTN.innerHTML = SPINNER_LOADER;
            try {
                const res = await Axios.post(
                    Router.generate(ROUTES.client.extra.api.add, { 'id': this.options.id.toString() }),
                    {
                        type: 1,
                        level: 1,
                        value: (document.getElementById("email") as HTMLInputElement).value,
                    }
                );
                Toast.success(res.data);
                this.update();
            } catch (err) {
                const e = err.response ? err.response.data : err;
                console.error(e);
                Toast.error(e);
                BTN.innerHTML = BEF;
            }
        }
    };

    private deletePhone = async (e: Event) => {
        const ELEMENT = (e.currentTarget as HTMLElement).closest(".phone-container") as HTMLElement;
        const ID = +ELEMENT.getAttribute("phone-id")!;
        const PHONE = ELEMENT.getAttribute("phone-phone")!;
        const ALERT = new Alert({
            type: 'danger',
            typeText: 'Alerta'
        });
        const res = await ALERT.updateBody(`¿Eliminar el número <b>${PHONE}</b>?`).show();
        if (res) {
            disableRow(ELEMENT);
            try {
                const res = await Axios.delete(
                    Router.generate(ROUTES.client.extra.api.delete, { 'id': ID.toString() })
                );
                deleteElement(ELEMENT);
                Toast.success(res.data);
            } catch (err) {
                console.error(err.response ? err.response.data : err);
            }
        }
    };

    private deleteEmail = async (e: Event) => {
        const ELEMENT = (e.currentTarget as HTMLElement).closest(".email-container") as HTMLElement;
        const ID = +ELEMENT.getAttribute("email-id")!;
        const EMAIL = ELEMENT.getAttribute("email-email")!;
        const ALERT = new Alert({
            type: 'danger',
            typeText: 'Alerta'
        });
        const res = await ALERT.updateBody(`¿Eliminar el correo <b>${EMAIL}</b>?`).show();
        if (res) {
            disableRow(ELEMENT);
            try {
                const res = await Axios.delete(
                    Router.generate(ROUTES.client.extra.api.delete, { 'id': ID.toString() })
                );
                deleteElement(ELEMENT);
                Toast.success(res.data);
            } catch (err) {
                console.error(err.response ? err.response.data : err);
            }
        }
    };

    private deleteContact = async (e: Event) => {
        const ELEMENT = (e.currentTarget as HTMLElement).closest(".contact-row") as HTMLElement;
        const NAME = ELEMENT.getAttribute("contactname")!;
        const ID = +ELEMENT.getAttribute("contactid")!;
        const ALERT = new Alert({
            type: 'danger',
            typeText: 'Alerta'
        });
        const res = await ALERT.updateBody(`¿Eliminar el contacto <b>${NAME}</b>?`).show();
        if (res) {
            this.modal.loadingBody();
            try {
                const res = await Axios.delete(
                    Router.generate(ROUTES.client.contact.api.delete, { 'id': ID.toString() })
                );
                Toast.success(res.data);
                this.update();
            } catch (err) {
                console.error(err.response ? err.response.data : err);
            }
        }
    };

    private showContact = (e: Event) => {
        this.modal.setAllowCallback(false);
        this.modal.hide();
        this.control = true;
        (new ShowContact({
            id: +(e.currentTarget as HTMLInputElement).closest(".contact-row")!.getAttribute("contactid")!,
            callback: this.load
        })).load();
    };
}
