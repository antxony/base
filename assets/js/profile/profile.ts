/**
* @packageDocumentation
* @module Profile
*/
import '@scripts/jquery';
import Toast from "@plugins/AlertToast";

/**
 * Profile main view to edit and see permissions
 *
 * @export
 * @class Profile
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Profile {

    public load = () => {
        const EDIT_BTN = document.getElementById('profile-edit')!;
        $('.editable-field').editable({
            success: (res: any) => {
                Toast.success(res);
            },
            error: (err: any) => {
                console.error(err.responseText);
                Toast.error(err.responseText);
            },
            disabled: true
        });
        document.getElementById('change-user-password')!.addEventListener('click', async () => {
            const { default: Key } = await import("@scripts/profile/key");
            (new Key()).load();
        });
        EDIT_BTN.addEventListener('click', () => {
            if (!!+EDIT_BTN.getAttribute('active')!) {
                EDIT_BTN.setAttribute('active', '0');
                EDIT_BTN.innerHTML = "Editar";
                EDIT_BTN.classList.remove('btn-danger');
                EDIT_BTN.classList.add('btn-warning');
            } else {
                EDIT_BTN.setAttribute('active', '1');
                EDIT_BTN.innerHTML = "Cancelar";
                EDIT_BTN.classList.remove('btn-warning');
                EDIT_BTN.classList.add('btn-danger');
            }
            $('.editable-field').editable('toggleDisabled');
        });
    };
}
