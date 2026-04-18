import { PageProps as InertiaPageProps } from "@inertiajs/core";
import { route as ziggyRoute } from "ziggy-js";
import type { PageProps as AppPageProps } from "./";

declare global {
    /* eslint-disable no-var */
    var route: typeof ziggyRoute;
}

declare module "@inertiajs/core" {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
