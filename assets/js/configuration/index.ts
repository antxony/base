/**
* @packageDocumentation
* @module Config
*/

import { ConfigTypes, Router, ROUTES } from "@scripts/app";
import Toast from "@scripts/plugins/AlertToast";
import Axios from "axios";

export interface ConfigOptions {
    type: ConfigTypes;
    restore?: boolean;
    callback?: () => void;
}

/**
 * update config
 *
 * @export
 * @class Config
 * @author Antxony <dantonyofcarim@gmail.com>
 */
export default class Config {

    protected options: ConfigOptions;

    public constructor (options: ConfigOptions) {
        this.options = options;
        this.options.restore = options.restore || false;
    }

    public load = () => {
        switch (this.options.type) {
            case ConfigTypes.TaskCommentedBorder:
                this.taskCommentedBorder();
                break;
            case ConfigTypes.TaskDoneColors:
                this.taskDoneColors();
                break;
            case ConfigTypes.TaskRecurrentBorder:
                this.taskrecurrentBorder();
                break;
            default:
                throw new Error("no se encontró la configuración");
                break;
        }
    };

    private taskCommentedBorder = () => {
        let data = {};
        if (!this.options.restore) {
            data = {
                style: (document.getElementById("obsBorderInput") as HTMLInputElement).value
            };
        }
        this.send(data);
    };

    private taskrecurrentBorder = () => {
        let data = {};
        if (!this.options.restore) {
            data = {
                style: (document.getElementById("recBorderInput") as HTMLInputElement).value
            };
        }
        this.send(data);
    };

    private taskDoneColors = () => {
        let data = {};
        if (!this.options.restore) {
            data = {
                "background-color": (document.getElementById("backColor") as HTMLInputElement).value,
                color: (document.getElementById("textColor") as HTMLInputElement).value
            };
        }
        this.send(data);
    };

    private send = async (data: any) => {
        const route = ((this.options.restore) ? ROUTES.configuration.api.restore : ROUTES.configuration.api.update);
        try {
            const res = await Axios.put(Router.generate(route, { 'type': this.options.type }), data);
            Toast.success(res.data);
            this.options.callback && this.options.callback();
        } catch (err) {
            const e = err.response ? err.response.data : err;
            console.error(e);
            Toast.error(e);
        }
    };


}